<?php
/*
 * Copyright 2015 Jack Sleight <http://jacksleight.com/>
 * This source file is subject to the MIT license that is bundled with this package in the file LICENCE. 
 */

namespace Coast\Social\Provider;

use Carbon\Carbon;
use Coast\Url;
use Coast\Social\Provider;
use Instaphp\Instaphp;

class Instagram extends Provider
{
    protected $_name = 'instagram';

    public function api()
    {
        if (!isset($this->_api)) {
            $this->_api = new Instaphp([
                'client_id' => $this->_credentials['clientId'],
            ]);
        }
        return $this->_api;
    }

    public function request($method, array $args = array())
    {
        $parts  = explode('/', $method);
        $method = array_pop($parts);
        $object = $this->api();
        foreach ($parts as $part) {
            $object = $object->{$part};
        }

        $res = call_user_func_array([$object, $method], $args);
        if (!$res->data) {
            throw new \Exception();
        }

        return $res;
    }

    public function feed($username)
    {
        $data = $this->fetch('users/search', [$username], null);
        foreach ($data->data as $user) {
            if ($user['username'] == $username) {
                $id = $user['id'];
                break;
            }
        }
        if (!isset($id)) {
            throw new Exception();
        }

        $data = $this->fetch('users/recent', [$id, [
            'count' => 10,
        ]]);

        $feed = [];
        foreach ($data->data as $post) {
            $feed[] = [
                'id'    => $post['id'],
                'url'   => new Url($post['link']),
                'date'  => new Carbon("@{$post['created_time']}"),
                'text'  => $post['caption']['text'],
                'html'  => $post['caption']['text'],
                'image' => new Url($post['images']['standard_resolution']['url']),
                'user'   => [
                    'id'       => $post['user']['id'],
                    'url'      => new Url("https://www.instagram.com/{$post['user']['username']}/"),
                    'name'     => $post['user']['full_name'],
                    'username' => $post['user']['username'],
                ],
                'source' => $post,
            ];
        }

        $feed = $this->_normalizeFeed($feed);
        return $feed;
    }
}