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
    protected $_name = 'facebook';

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

    protected function _feed(array $params, array $extra = array())
    {
        $params = $params + [
            'objectId' => null,
        ];
        if (!isset($params['objectId'])) {
            throw new Social\Exception('No object ID specified');
        }

        $data = $this->fetch("{$params['objectId']}/feed", [
            'fields' => 'id,created_time,name,caption,description,link,picture,properties,type,from{id,username,name},message',
            'limit'  => $params['limit'],
        ] + $extra);

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
                'image' => isset($post['picture']) ? new Url($post['picture']) : null,
                'user'   => [
                    'id'       => $post['from']['id'],
                    'url'      => new Url("https://www.facebook.com/{$identifier}"),
                    'name'     => $post['from']['name'],
                    'username' => $username,
                ],
                'source' => $post,
            ];
        }

        return $feed;
    }

    protected function _stats(Url $url)
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