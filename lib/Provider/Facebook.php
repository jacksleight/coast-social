<?php
/*
 * Copyright 2015 Jack Sleight <http://jacksleight.com/>
 * This source file is subject to the MIT license that is bundled with this package in the file LICENCE. 
 */

namespace Coast\Social\Provider;

use DateTime;
use Coast\Url;
use Coast\Social;
use Coast\Social\Provider;
use Coast\Social\Provider\External;

class Facebook extends External
{
    protected $_endpoint = 'https://graph.facebook.com/v2.5/';

    protected function _request($method, array $params = array())
    {
        $url = (new Url("{$this->_endpoint}{$method}"))->queryParams([
            'access_token' => $this->_credentials['accessToken'],
        ] + $params);
           
        $res = $this->_http->get($url);
        if (strpos($res->header('content-type'), 'application/json') === false) {
            throw new Social\Exception('Non JSON response');
        }
        $data = $res->json();
        if ($data === false) {
            throw new Social\Exception('Malformed JSON response');
        }
        if (!$res->isSuccess() || isset($data['error'])) {
            throw new Social\Exception($data['error']['message']);
        }

        return $data;
    }

    protected function _feed(array $params)
    {
        if (!isset($params['id']) && !isset($params['username'])) {
            throw new Social\Exception('No ID or username specified');
        }

        $id = isset($params['id'])
            ? $params['id']
            : $params['username'];
        $data = $this->fetch("{$id}/feed", [
            'fields' => 'id,created_time,name,caption,description,link,picture,properties,type,from{id,name},message',
            'limit'  => $params['limit'],
        ] + $params['native']);

        $feed = [];
        foreach ($data['data'] as $post) {
            $text = isset($post['message'])
                ? $post['message']
                : null;
            $feed[] = [
                'id'    => $post['id'],
                'url'   => new Url("https://www.facebook.com/{$post['from']['id']}/posts/" . substr($post['id'], strpos($post['id'], '_') + 1)),
                'date'  => new DateTime($post['created_time']),
                'text'  => $text,
                'html'  => $this->textToHtml($text),
                'image' => [
                    'url' => isset($post['picture']) ? new Url($post['picture']) : null,
                ],
                'user'  => [
                    'id'       => $post['from']['id'],
                    'url'      => new Url("https://www.facebook.com/{$post['from']['id']}"),
                    'name'     => $post['from']['name'],
                ],
                'native' => $post,
            ];
        }

        return $feed;
    }

    protected function _urlStats(Url $url)
    {
        $data = $this->fetch('', [
            'id'     => $url->toString(),
            'fields' => 'share',
        ]);

        $stats = [
            'shares'   => $data['share']['share_count'],
            'comments' => $data['share']['comment_count'],
        ];

        return $stats;
    }
}