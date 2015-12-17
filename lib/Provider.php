<?php
/*
 * Copyright 2015 Jack Sleight <http://jacksleight.com/>
 * This source file is subject to the MIT license that is bundled with this package in the file LICENCE. 
 */

namespace Coast\Social;

use Coast\Url;

abstract class Provider
{
    public function feed(array $params)
    {
        $params = $params + [
            'id'       => null,
            'username' => null,
            'limit'    => 10,
            'native'   => [],
        ];

        $feed = $this->_feed($params);
        if ($feed == false) {
            return false;
        }

        foreach ($feed as $i => $item) {
            $feed[$i] = \Coast\array_merge_smart([
                'id'       => null,
                'url'      => null,
                'date'     => null,
                'text'     => null,
                'html'     => null,
                'image' => [
                    'url'    => null,
                    'width'  => null,
                    'height' => null,
                ],
                'user' => [
                    'id'       => null,
                    'url'      => null,
                    'name'     => null,
                    'username' => null,
                ],
                'native' => null,
            ], $item);
        }

        return $feed;
    }

    public function urlStats(Url $url)
    {
        $stats = $this->_urlStats($url);
        if ($stats == false) {
            return false;
        }

        $stats = \Coast\array_merge_smart([
            'shares'   => null,
            'comments' => null,
        ], $stats);

        return $stats;
    }

    protected function _feed(array $params)
    {
        return false;
    }

    protected function _urlStats(Url $url)
    {
        return false;
    }

    public function textToHtml($value)
    {
        $value = preg_replace("/(^|[\n ])([\w]*?)((ht|f)tp(s)?:\/\/[\w]+[^ \,\"\n\r\t<]*)/is", "$1$2<a href=\"$3\" target=\"_blank\">$3</a>", $value);
        $value = preg_replace("/(^|[\n ])([\w]*?)((www|ftp)\.[^ \,\"\t\n\r<]*)/is", "$1$2<a href=\"http://$3\" target=\"_blank\">$3</a>", $value);
        $value = preg_replace("/(^|[\n ])([a-z0-9&\-_\.]+?)@([\w\-]+\.([\w\-\.]+)+)/i", "$1<a href=\"mailto:$2@$3\" target=\"_blank\">$2@$3</a>", $value);

        if (strlen($value) == 0) {
            return null;
        }

        $value = preg_replace('/(\r\n?|\n)/', '<br>', $value);

        return $value;
    }
}