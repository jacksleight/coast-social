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
    protected $_endpoint = 'https://api.instagram.com/v1/';

    protected function _request($method, array $params = array())
    {
        $url = (new Url("{$this->_endpoint}{$method}"))->queryParams([
            'client_id' => $this->_credentials['clientId'],
        ] + $params);
           
        $res = $this->_http->get($url);
        if (strpos($res->header('content-type'), 'application/json') === false) {
            throw new Social\Exception('Non JSON response');
        }
        $data = $res->json();
        if ($data === false) {
            throw new Social\Exception('Malformed JSON response');
        }
        if (!$res->isSuccess() || $data['meta']['code'] != 200) {
            throw new Social\Exception($data['meta']['error_message']);
        }

        return $data['data'];
    }

    protected function _feed(array $params)
    {
        if (!isset($params['id']) && !isset($params['username'])) {
            throw new Social\Exception('No ID or username specified');
        }

        if (!isset($params['id'])) {
            $data = $this->fetch("users/search", [
                'q' => $params['username'],
            ], null);
            foreach ($data as $user) {
                if ($user['username'] == $params['username']) {
                    $params['id'] = $user['id'];
                    break;
                }
            }
            if (!isset($params['id'])) {
                throw new Social\Exception("Username '{$params['username']}' does not exist");
            }
        }

        $data = $this->fetch("users/{$params['id']}/media/recent", [
            'count' => $params['limit'],
        ] + $params['native']);

        $feed = [];
        foreach ($data as $post) {
            $feed[] = [
                'id'    => $post['id'],
                'url'   => new Url($post['link']),
                'date'  => new Carbon("@{$post['created_time']}"),
                'text'  => $post['caption']['text'],
                'html'  => $this->textToHtml($post['caption']['text']),
                'image' => [
                    'url'    => new Url($post['images']['standard_resolution']['url']),
                    'width'  => $post['images']['standard_resolution']['width'],
                    'height' => $post['images']['standard_resolution']['height'],
                ],
                'user'  => [
                    'id'       => $post['user']['id'],
                    'url'      => new Url("https://www.instagram.com/{$post['user']['username']}/"),
                    'name'     => $post['user']['full_name'],
                    'username' => $post['user']['username'],
                ],
                'native' => $post,
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