<?php
/*
 * Copyright 2015 Jack Sleight <http://jacksleight.com/>
 * This source file is subject to the MIT license that is bundled with this package in the file LICENCE. 
 */

namespace Coast\Social\Provider;

use Carbon\Carbon;
use Coast\Social;
use Coast\Url;
use Coast\Social\Provider;
use Coast\Social\Provider\External;
use Instaphp\Instaphp;

class Instagram extends External
{
    protected $_name = 'instagram';

    protected function _api()
    {
        return new Instaphp([
            'client_id' => $this->_credentials['clientId'],
        ]);
    }

    protected function _request($method, array $args = array())
    {
        $parts  = explode('/', $method);
        $method = array_pop($parts);
        $object = $this->api();
        foreach ($parts as $part) {
            $object = $object->{$part};
        }

        $res = call_user_func_array([$object, $method], $args);
        if (!isset($res->data)) {
            throw new Social\Exception('Received invalid response data');
        }

        return $res;
    }

    protected function _feed(array $params, array $extra = array())
    {
        $params = $params + [
            'username' => null,
            'userId'   => null,
        ];
        if (!isset($params['userId']) && !isset($params['username'])) {
            throw new Social\Exception('No user ID or username specified');
        }

        if (!isset($params['userId'])) {
            $data = $this->fetch('users/search', [$params['username']], null);
            foreach ($data->data as $user) {
                if ($user['username'] == $params['username']) {
                    $params['userId'] = $user['id'];
                    break;
                }
            }
            if (!isset($params['userId'])) {
                throw new Social\Exception("Username '{$params['username']}' does not exist");
            }
        }

        $data = $this->fetch('users/recent', [$params['userId'], [
            'count' => $params['limit'],
        ] + $extra]);

        $feed = [];
        foreach ($data->data as $post) {
            $feed[] = [
                'id'    => $post['id'],
                'url'   => new Url($post['link']),
                'date'  => new Carbon("@{$post['created_time']}"),
                'text'  => $post['caption']['text'],
                'html'  => $this->textToHtml($post['caption']['text']),
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

        return $feed;
    }

    public function textToHtml($text)
    {
        $text = preg_replace("/@(\w+)/", '<a href="https://www.instagram.com/$1" target="_blank">@$1</a>', $text); 
        $text = preg_replace("/\#(\w+)/", '<a href="https://www.instagram.com/explore/tags/$1" target="_blank">#$1</a>', $text); 

        return parent::textToHtml($text);
    }
}