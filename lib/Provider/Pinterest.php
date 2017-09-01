<?php
/*
 * Copyright 2017 Jack Sleight <http://jacksleight.com/>
 * This source file is subject to the MIT license that is bundled with this package in the file LICENCE. 
 */

namespace Coast\Social\Provider;

use DateTime;
use Coast\Social;
use Coast\Url;
use Coast\Http;
use Coast\Social\Provider;
use Coast\Social\Provider\External;

class Pinterest extends External
{
    protected $_endpoint = 'https://api.pinterest.com/v1/';

    protected function _request($method, array $params = array())
    {
        $jsonpMethods = [
            'urls/count'
        ];
        if (in_array($method, $jsonpMethods)) {
            $method .= '.json';
        }

        $req = new Http\Request([
            'url' => (new Url("{$this->_endpoint}{$method}"))->queryParams([
                'access_token' => $this->_credentials['accessToken'],
            ] + $params),
        ]);
        $res = $this->_http->execute($req);

        if (!$res->isJson() && !$res->isJavascript()) {
            throw new Social\Exception('Non JSON response');
        }
        $data = $res->json(true);
        if ($data === false) {
            throw new Social\Exception('Malformed JSON response');
        }
        if (!$res->isSuccess() || isset($data['status']) && $data['status'] == 'failure') {
            throw new Social\Exception($data['message']);
        }

        return isset($data['data'])
            ? $data['data']
            : $data;
    }

    protected function _feed(array $params)
    {
        if (!isset($params['id'])) {
            throw new Social\Exception('No ID specified');
        }

        $data = $this->fetch("boards/{$params['id']}/pins", [
            'fields' => 'id,link,url,creator(id,username,first_name,last_name,bio,created_at,counts,image,url),board,created_at,note,color,counts,media,attribution,image,metadata',
            'limit'  => $params['limit'],
        ] + $params['raw']);

        $feed = [];
        foreach ($data as $pin) {
            $feed[] = [
                'id'    => $pin['id'],
                'url'   => new Url($pin['url']),
                'date'  => new DateTime($pin['created_at']),
                'text'  => $pin['note'],
                'html'  => $this->textToHtml($pin['note']),
                'image' => [
                    'url'    => new Url($pin['image']['original']['url']),
                    'width'  => $pin['image']['original']['width'],
                    'height' => $pin['image']['original']['height'],
                ],
                'user'  => [
                    'id'       => $pin['creator']['id'],
                    'url'      => new Url($pin['creator']['url']),
                    'name'     => "{$pin['creator']['first_name']} {$pin['creator']['last_name']}",
                    'username' => $pin['creator']['username'],
                ],
                'raw' => $pin,
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
            'raw'    => $data,
        ];

        return $stats;
    }
}