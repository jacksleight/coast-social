<?php
/*
 * Copyright 2015 Jack Sleight <http://jacksleight.com/>
 * This source file is subject to the MIT license that is bundled with this package in the file LICENCE. 
 */

namespace Coast\Social\Provider;

use Carbon\Carbon;
use Coast\Url;
use Coast\Social\Provider;
use TwitterAPIExchange;

class Twitter extends Provider
{
    protected $_name = 'twitter';

    public function api()
    {
        if (!isset($this->_api)) {
            $this->_api = new \TwitterAPIExchange([
                'consumer_key'              => $this->_credentials['consumerKey'],
                'consumer_secret'           => $this->_credentials['consumerSecret'],
                'oauth_access_token'        => $this->_credentials['accessToken'],
                'oauth_access_token_secret' => $this->_credentials['accessTokenSecret']
            ]);
        }
        return $this->_api;
    }

    public function request($method, array $args = array())
    {
        $res = $this->api()
            ->setGetfield('?' . http_build_query($args))
            ->buildOauth("https://api.twitter.com/1.1/{$method}.json", 'GET')
            ->performRequest();

        $res = json_decode($res, true);
        if (!$res) {
            throw new \Exception();
        }
        if (isset($res->errors)) {
            throw new \Exception();
        }

        return $res;
    }

    public function feed($username)
    {
        $tweets = $this->fetch('statuses/user_timeline', [
            'screen_name' => $username,
            'count'       => 100,
        ]);

        $feed = [];
        foreach ($tweets as $tweet) {
            $feed[] = [
                'id'     => $tweet['id_str'],
                'url'    => new Url("https://twitter.com/{$tweet['user']['screen_name']}/status/{$tweet['id_str']}"),
                'date'   => new Carbon($tweet['created_at']),
                'text'   => $tweet['text'],
                'html'   => $this->_tweetToHtml($tweet),
                'user'   => [
                    'id'       => $tweet['user']['id_str'],
                    'url'      => new Url("https://twitter.com/{$tweet['user']['screen_name']}"),
                    'name'     => $tweet['user']['name'],
                    'username' => $tweet['user']['screen_name'],
                ],
                'source' => $tweet,
            ];
        }

        $feed = $this->_normalizeFeed($feed);
        return $feed;
    }

    protected function _tweetToHtml($tweet)
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
            $replace = "<a href=\"{$entity['href']}\">{$search}</a>";
            $html    = str_replace($search, $replace, $html);
        }

        return $html;
    }
}