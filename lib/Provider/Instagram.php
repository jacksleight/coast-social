<?php
/*
 * Copyright 2017 Jack Sleight <http://jacksleight.com/>
 * This source file is subject to the MIT license that is bundled with this package in the file LICENCE. 
 */

namespace Coast\Social\Provider;

use DateTime;
use Coast\Social;
use Coast\Url;
use Coast\Social\Provider;
use Coast\Social\Provider\External;
use Coast\Http;

class Instagram extends External
{
    protected $_endpoint = 'https://graph.instagram.com/';

    // protected function _refresh()
    // {
    //     $data = $this->_request("refresh_access_token", [
    //         'grant_type' => 'ig_refresh_token',
    //     ]);
    //     var_dump($data);
    //     die;
    // }

    protected function _request($method, array $params = array())
    {
        $req = new Http\Request([
            'url' => (new Url("{$this->_endpoint}{$method}"))->queryParams([
                'access_token' => $this->_credentials['accessToken'],
            ] + $params),
        ]);
        $res = $this->_http->execute($req);

        if (!$res->isJson()) {
            throw new Social\Exception('Non JSON response');
        }
        $data = $res->json(true);
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
        if (!isset($params['id'])) {
            $params['id'] = 'me';
        }

        $user = $this->fetch("{$params['id']}", [
            'fields' => 'id,username',
        ]);
        $data = $this->fetch("{$params['id']}/media", [
            'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,children{id,media_url}',
        ] + $params['raw']);

        $feed = [];
        foreach ($data['data'] as $post) {
            $post = $post + [
                'caption' => null,
            ];
            if ($post['media_type'] == 'IMAGE') {
                $image = [
                    'url' => new Url($post['media_url']),
                ];
            } else if ($post['media_type'] == 'VIDEO') {
                $image = [
                    'url' => new Url($post['thumbnail_url']),
                ];
            } else if ($post['media_type'] == 'CAROUSEL_ALBUM') {
                $image = [
                    'url' => new Url($post['children']['data'][0]['media_url']),
                ];
            }
            $feed[] = [
                'id'    => $post['id'],
                'url'   => new Url($post['permalink']),
                'date'  => new DateTime($post['timestamp']),
                'text'  => $post['caption'],
                'html'  => $this->textToHtml($post['caption']),
                'image' => $image,
                'user'  => [
                    'id'       => $user['id'],
                    'url'      => new Url("https://www.instagram.com/{$user['username']}/"),
                    'username' => $user['username'],
                ],
                'raw' => $post,
            ];
        }

        if (isset($params['limit'])) {
            $feed = array_slice($feed, 0, $params['limit']);
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