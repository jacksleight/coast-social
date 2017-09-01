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

class Twitter extends External
{
    protected $_endpoint = 'https://api.twitter.com/1.1/';

    protected function _request($method, array $params = array())
    {
        $req = new Http\Request([
            'url' => (new Url("{$this->_endpoint}{$method}.json"))->queryParams($params),
        ]);
        $this->_oauthHeader($req);
        $res = $this->_http->execute($req);

        if (!$res->isJson()) {
            throw new Social\Exception('Non JSON response');
        }
        $data = $res->json(true);
        if ($data === false) {
            throw new Social\Exception('Malformed JSON response');
        }
        if (!$res->isSuccess() || isset($data['errors'])) {
            throw new Social\Exception($data['errors'][0]['message']);
        }

        return $data;
    }

    protected function _oauthHeader(Http\Request $req)
    {
        $params = $req->url()->queryParams();
        $url    = $req->url()->toPart(Url::PART_PATH);

        $oauthParams = [
            'oauth_consumer_key'     => $this->_credentials['consumerKey'],
            'oauth_nonce'            => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp'        => time(),
            'oauth_token'            => $this->_credentials['oauthAccessToken'],
            'oauth_version'          => '1.0'
        ];
        $compositeSecret =
            rawurlencode($this->_credentials['consumerSecret']) . '&' .
            rawurlencode($this->_credentials['oauthAccessTokenSecret']);

        $signatureParams = $oauthParams + $params;
        ksort($signatureParams);
        $signatureParts = [];
        foreach($signatureParams as $name => $value) {
            $signatureParts[] =
                rawurlencode($name) . '=' .
                rawurlencode($value);
        }

        $signatureData =
            'GET&' .
            rawurlencode($url->toString()) . '&' .
            rawurlencode(implode('&', $signatureParts));

        $signature = hash_hmac('sha1', $signatureData, $compositeSecret, true);
        $signature = base64_encode($signature);
        $oauthParams['oauth_signature'] = $signature;

        $headerParts = [];
        foreach($oauthParams as $name => $value) {
            $headerParts[] = $name . '="' . rawurlencode($value) . '"';
        }
        $header = 'OAuth ' . implode(', ', $headerParts);

        $req->header('Authorization', $header);
    }

    protected function _feed(array $params)
    {
        if (!isset($params['username'])) {
            throw new Social\Exception('No username specified');
        }

        $data = $this->fetch('statuses/user_timeline', [
            'screen_name' => $params['username'],
            'count'       => $params['limit'],
        ] + $params['raw']);

        $feed = [];
        foreach ($data as $tweet) {
            $item = [
                'id'    => $tweet['id_str'],
                'url'   => new Url("https://twitter.com/{$tweet['user']['screen_name']}/status/{$tweet['id_str']}"),
                'date'  => new DateTime($tweet['created_at']),
                'text'  => $tweet['text'],
                'html'  => $this->tweetToHtml($tweet),
                'image' => [],
                'user' => [
                    'id'       => $tweet['user']['id_str'],
                    'url'      => new Url("https://twitter.com/{$tweet['user']['screen_name']}"),
                    'name'     => $tweet['user']['name'],
                    'username' => $tweet['user']['screen_name'],
                ],
                'raw' => $tweet,
            ];
            if (isset($tweet['entities']['media'])) {
                foreach ($tweet['entities']['media'] as $media) {
                    if ($media['type'] == 'photo') {
                        $item['image'] = [
                            'url'    => new Url($media['media_url_https']),
                            'width'  => $media['sizes']['large']['w'],
                            'height' => $media['sizes']['large']['h'],
                        ];
                        break;
                    }
                }
            }
            $feed[] = $item;
        }

        return $feed;
    }

    public function textToHtml($text)
    {
        $text = preg_replace("/@(\w+)/", '<a href="http://www.twitter.com/$1" target="_blank">@$1</a>', $text); 
        $text = preg_replace("/\#(\w+)/", '<a href="http://search.twitter.com/search?q=$1" target="_blank">#$1</a>', $text); 

        return parent::textToHtml($text);
    }

    public function tweetToHtml(array $tweet)
    {
        $entities = [];
        foreach ($tweet['entities']['urls'] as $entity) {
            $entities[] = [
                'indices' => $entity['indices'],
                'href'    => $entity['expanded_url'],
            ];
        }
        foreach ($tweet['entities']['user_mentions'] as $entity) {
            $entities[] = [
                'indices' => $entity['indices'],
                'href'    => "https://twitter.com/{$entity['screen_name']}",
            ];
        }
        foreach ($tweet['entities']['hashtags'] as $entity) {
            $entities[] = [
                'indices' => $entity['indices'],
                'href'    => "https://twitter.com/search?q=%23{$entity['text']}&src=hash",
            ];
        }
        if (isset($tweet['entities']['media'])) {
            foreach ($tweet['entities']['media'] as $entity) {
                $entities[] = [
                    'indices' => $entity['indices'],
                    'href'    => $entity['expanded_url'],
                ];
            }
        }
        $html = $tweet['text'];
        foreach ($entities as $entity ) {
            $search  = mb_substr($tweet['text'], $entity['indices'][0], ($entity['indices'][1] - $entity['indices'][0]), 'utf-8');
            $replace = "<a href=\"{$entity['href']}\" target=\"_blank\">{$search}</a>";
            $html    = str_replace($search, $replace, $html);
        }
        return $html;
    }
}