<?php
/*
 * Copyright 2015 Jack Sleight <http://jacksleight.com/>
 * This source file is subject to the MIT license that is bundled with this package in the file LICENCE. 
 */

namespace Coast\Social\Provider;

use Carbon\Carbon;
use Coast\Url;
use Coast\Social\Provider;
use Coast\Social\Provider\External;
use Facebook\Facebook as FacebookApi;

class Facebook extends External
{
    protected function _api()
    {
        return new FacebookApi([
            'app_id'     => $this->_credentials['appId'],
            'app_secret' => $this->_credentials['appSecret'],
        ]);
    }

    protected function _request($method, array $args = array())
    {
        $res = $this->api()->get(
            "/{$method}?" . http_build_query($args),
            $this->_credentials['accessToken']
        );
        return $res->getDecodedBody();
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
            'fields' => 'id,created_time,name,caption,description,link,picture,properties,type,from{id,username,name},message',
            'limit'  => $params['limit'],
        ] + $params['native']);

        $feed = [];
        foreach ($data['data'] as $post) {
            if (isset($post['from']['username'])) {
                $identifier = $post['from']['username'];
                $username   = $post['from']['username'];
            } else {
                $identifier = $post['from']['id'];
                $username   = null;                
            }
            $text = isset($post['message'])
                ? $post['message']
                : null;
            $feed[] = [
                'id'    => $post['id'],
                'url'   => new Url("https://www.facebook.com/{$identifier}/posts/" . substr($post['id'], strpos($post['id'], '_') + 1)),
                'date'  => new Carbon($post['created_time']),
                'text'  => $text,
                'html'  => $this->textToHtml($text),
                'image' => [
                    'url' => isset($post['picture']) ? new Url($post['picture']) : null,
                ],
                'user'  => [
                    'id'       => $post['from']['id'],
                    'url'      => new Url("https://www.facebook.com/{$identifier}"),
                    'name'     => $post['from']['name'],
                    'username' => $username,
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