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
use TwitterAPIExchange;

class Twitter extends External
{
    protected $_name = 'twitter';

    protected function _api()
    {
        return new TwitterAPIExchange([
            'consumer_key'              => $this->_credentials['consumerKey'],
            'consumer_secret'           => $this->_credentials['consumerSecret'],
            'oauth_access_token'        => $this->_credentials['accessToken'],
            'oauth_access_token_secret' => $this->_credentials['accessTokenSecret']
        ]);
    }

    protected function _request($method, array $args = array())
    {
        $res = $this->api()
            ->setGetfield('?' . http_build_query($args))
            ->buildOauth("https://api.twitter.com/1.1/{$method}.json", 'GET')
            ->performRequest();

        $res = json_decode($res, true);
        if (!$res) {
            throw new Social\Exception('Received invalid response data');
        }
        if (isset($res['errors'])) {
            throw new Social\Exception($res['errors'][0]['message']);
        }

        return $res;
    }

    protected function _feed(array $params, array $extra = array())
    {
        $params = $params + [
            'username' => null,
        ];
        if (!isset($params['username'])) {
            throw new Social\Exception('No username specified');
        }

        $data = $this->fetch('statuses/user_timeline', [
            'screen_name' => $params['username'],
            'count'       => $params['limit'],
        ] + $extra);

        $feed = [];
        foreach ($data as $tweet) {
            $feed[] = [
                'id'     => $tweet['id_str'],
                'url'    => new Url("https://twitter.com/{$tweet['user']['screen_name']}/status/{$tweet['id_str']}"),
                'date'   => new Carbon($tweet['created_at']),
                'text'   => $tweet['text'],
                'html'   => $this->tweetToHtml($tweet),
                'user'   => [
                    'id'       => $tweet['user']['id_str'],
                    'url'      => new Url("https://twitter.com/{$tweet['user']['screen_name']}"),
                    'name'     => $tweet['user']['name'],
                    'username' => $tweet['user']['screen_name'],
                ],
                'source' => $tweet,
            ];
        }

        return $feed;
    }

    public function textToHtml($text)
    {
        $text = preg_replace("/@(\w+)/", '<a href="http://www.twitter.com/$1" target="_blank">@$1</a>', $text); 
        $text = preg_replace("/\#(\w+)/", '<a href="http://search.twitter.com/search?q=$1" target="_blank">#$1</a>', $text); 
        
        return parent::textToHtml($text);
    }

    public function tweetToHtml($tweet)
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