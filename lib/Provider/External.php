<?php
/*
 * Copyright 2015 Jack Sleight <http://jacksleight.com/>
 * This source file is subject to the MIT license that is bundled with this package in the file LICENCE. 
 */

namespace Coast\Social\Provider;

use Coast\Social;
use Coast\Social\Provider;
use Coast\Url;

abstract class External implements Provider
{
    protected $_name;

    protected $_credentials = [];

    protected $_api;

    public function __construct(array $options = array())
    {
        foreach ($options as $name => $value) {
            if ($name[0] == '_') {
                throw new \Coast\Exception("Access to '{$name}' is prohibited");  
            }
            $this->$name($value);
        }
    }

    public function name()
    {
        return $this->_name;
    }

    public function credentials($credentials = null)
    {
        if (func_num_args() > 0) {
            $this->_credentials = $credentials;
            return $this;
        }
        return $this->_credentials;
    }

    public function fetch($method, array $args = array(), $lifetime = 3600)
    {
        $key = md5(
            get_class($this) .
            serialize($this->_credentials) .
            $method .
            serialize($args)
        );

        $cache = new \Memcached();
        $cache->addServer('localhost', 11211);

        $data = $cache->get($key);
        if ($data === false) {
            $data = $this->request($method, $args);
            $cache->set($key, $data, $lifetime);
        }

        return $data;
    }

    public function api()
    {
        if (!isset($this->_api)) {
            $this->_api = $this->_api();
        }
        return $this->_api;
    }

    public function request($method, array $args = array())
    {
        try {
            return $this->_request($method, $args);
        } catch (Social\Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new Social\Exception($e->getMessage());
        }
    }

    public function feed(array $params, array $extra = array())
    {
        $params = $params + [
            'limit' => 10,
        ];

        $feed = $this->_feed($params, $extra);

        foreach ($feed as $i => $item) {
            $feed[$i] = \Coast\array_merge_smart([
                'provider' => $this->_name,
                'id'       => null,
                'url'      => null,
                'date'     => null,
                'text'     => null,
                'html'     => null,
                'image'    => null,
                'user' => [
                    'id'       => null,
                    'url'      => null,
                    'name'     => null,
                    'username' => null,
                ],
                'source' => null,
            ], $item);
        }

        return $feed;
    }

    public function stats(Url $url)
    {
        $stats = $this->_stats($url);

        return $stats;
    }

    abstract protected function _api();

    abstract protected function _request($method, array $args = array());

    protected function _feed(array $params, array $extra = array())
    {
        return false;
    }

    protected function _stats(Url $url)
    {
        return false;
    }

    public function textToHtml($text)
    {
        $text = preg_replace("/(^|[\n ])([\w]*?)((ht|f)tp(s)?:\/\/[\w]+[^ \,\"\n\r\t<]*)/is", "$1$2<a href=\"$3\" target=\"_blank\">$3</a>", $text);
        $text = preg_replace("/(^|[\n ])([\w]*?)((www|ftp)\.[^ \,\"\t\n\r<]*)/is", "$1$2<a href=\"http://$3\" target=\"_blank\">$3</a>", $text);
        $text = preg_replace("/(^|[\n ])([a-z0-9&\-_\.]+?)@([\w\-]+\.([\w\-\.]+)+)/i", "$1<a href=\"mailto:$2@$3\" target=\"_blank\">$2@$3</a>", $text);

        if (strlen($text) == 0) {
            return null;
        }

        $html = preg_split('/[(\r\n?|\n)]{2,}/is', $text);
        $html = '<p>' . implode('</p><p>', $html) . '</p>';
        $html = preg_replace('/(\r\n?|\n)/', '<br>', $html);

        return $html;
    }
}