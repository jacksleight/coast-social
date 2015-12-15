<?php
/*
 * Copyright 2015 Jack Sleight <http://jacksleight.com/>
 * This source file is subject to the MIT license that is bundled with this package in the file LICENCE. 
 */

namespace Coast\Social;

abstract class Provider
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

    protected function _normalizeFeed(array $feed)
    {
        foreach ($feed as $i => $item) {
            $feed[$i] = \Coast\array_merge_smart([
                'provider' => $this->_name,
                'id'       => null,
                'url'      => null,
                'date'     => null,
                'text'     => null,
                'html'     => null,
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

    abstract public function api();

    abstract public function request($method, array $args = array());

    abstract public function feed($username);

    // abstract public function statistics($url);
}