<?php
/*
 * Copyright 2015 Jack Sleight <http://jacksleight.com/>
 * This source file is subject to the MIT license that is bundled with this package in the file LICENCE. 
 */

namespace Coast\Social\Provider;

use Carbon\Carbon;
use Coast\Url;
use Coast\Social\Provider;
use Facebook\Facebook as FacebookSdk;

class Facebook extends Provider
{
    protected $_name = 'facebook';

    public function api()
    {
        if (!isset($this->_api)) {
            $this->_api = new FacebookSdk([
                'app_id'     => $this->_credentials['appId'],
                'app_secret' => $this->_credentials['appSecret'],
            ]);
        }
        return $this->_api;
    }

    public function request($method, array $args = array())
    {
        $res = $this->api()->get(
            "/{$method}?" . http_build_query($args),
            $this->_credentials['accessToken']
        );
        return $res->getDecodedBody();
    }

    public function feed($username)
    {
        $data = $this->fetch("{$username}/feed", [
            'fields' => 'id,created_time,from{id,username,name},message',
        ]);

        $feed = [];
        foreach ($data['data'] as $post) {
            $subId = substr($post['id'], strpos($post['id'], '_') + 1);
            $feed[] = [
                'id'    => $post['id'],
                'url'   => new Url("https://www.facebook.com/{$post['from']['username']}/posts/{$subId}"),
                'date'  => new Carbon($post['created_time']),
                'text'  => isset($post['message']) ? $post['message'] : null,
                'html'  => isset($post['message']) ? $post['message'] : null,
                'user'   => [
                    'id'       => $post['from']['id'],
                    'url'      => new Url("https://www.facebook.com/{$post['from']['username']}"),
                    'name'     => $post['from']['name'],
                    'username' => $post['from']['username'],
                ],
                'source' => $post,
            ];
        }

        $feed = $this->_normalizeFeed($feed);
        return $feed;
    }

    public function stats(Url $url)
    {
        $data = $this->fetch('', [
            'id' => $url->toString(),
            'fields' => 'share',
        ]);

        $stats = [
            'comments' => $data['share']['comment_count'],
            'shares'   => $data['share']['share_count'],
        ];

        // $stats = $this->_normalizestats($stats);
        return $stats;
    }
}