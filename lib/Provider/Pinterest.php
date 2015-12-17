<?php
/*
 * Copyright 2015 Jack Sleight <http://jacksleight.com/>
 * This source file is subject to the MIT license that is bundled with this package in the file LICENCE. 
 */

namespace Coast\Social\Provider;

use Carbon\Carbon;
use Coast\Social;
use Coast\Url;
use Coast\Http;
use Coast\Social\Provider;
use Coast\Social\Provider\External;
use DirkGroenen\Pinterest\Pinterest as PinterestApi;

class Pinterest extends External
{
    protected function _api()
    {
        $api = new PinterestApi(
            $this->_credentials['appId'],
            $this->_credentials['appSecret']
        );
        $api->auth->setOAuthToken($this->_credentials['accessToken']);
        return $api;
    }

    protected function _request($method, array $args = array())
    {
        if ($method == 'urls/count') {
            $url = (new Url('https://api.pinterest.com/v1/urls/count.json'))
                ->queryParams($args);
            $http = new Http();
            $res  = $http->get(new Url($url));
            if (!$res->isSuccess()) {
                throw new Social\Exception($res->status());
            }
            $res = substr($res->body(), 13, strlen($res->body()) - 14);
            $res = json_decode($res, true);
            return $res;
        }

        $parts  = explode('/', $method);
        $method = array_pop($parts);
        $object = $this->api();
        foreach ($parts as $part) {
            $object = $object->{$part};
        }

        $res = call_user_func_array([$object, $method], $args);
        return $res;
    }

    protected function _feed(array $params)
    {
        if (!isset($params['id'])) {
            throw new Social\Exception('No ID specified');
        }

        $data = $this->fetch('pins/fromBoard', [$params['boardId'], [
            'fields' => 'id,link,url,creator(id,username,first_name,last_name,bio,created_at,counts,image,url),board,created_at,note,color,counts,media,attribution,image,metadata',
            'limit'  => $params['limit'],
        ] + $params['native']]);

        $feed = [];
        foreach ($data as $pin) {
            $feed[] = [
                'id'    => $pin->id,
                'url'   => new Url($pin->url),
                'date'  => new Carbon($pin->created_at),
                'text'  => $pin->note,
                'html'  => $this->textToHtml($pin->note),
                'image' => [
                    'url'    => new Url($pin->image['original']['url']),
                    'width'  => $pin->image['original']['width'],
                    'height' => $pin->image['original']['height'],
                ],
                'user'  => [
                    'id'       => $pin->creator['id'],
                    'url'      => new Url($pin->creator['url']),
                    'name'     => "{$pin->creator['first_name']} {$pin->creator['last_name']}",
                    'username' => $pin->creator['username'],
                ],
                'native' => $pin,
            ];
        }

        return $feed;
    }

    public function textToHtml($text)
    {
        $text = preg_replace("/@(\w+)/", '<a href="https://pinterest.com/$1" target="_blank">@$1</a>', $text); 
        $text = preg_replace("/\#(\w+)/", '<a href="https://pinterest.com/explore/$1" target="_blank">#$1</a>', $text); 

        return parent::textToHtml($text);
    }

    protected function _urlStats(Url $url)
    {
        $data = $this->fetch('urls/count', [
            'url' => $url->toString(),
        ]);

        $stats = [
            'shares' => $data['count'],
        ];

        return $stats;
    }
}