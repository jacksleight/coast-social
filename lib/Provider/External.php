<?php
/*
 * Copyright 2015 Jack Sleight <http://jacksleight.com/>
 * This source file is subject to the MIT license that is bundled with this package in the file LICENCE. 
 */

namespace Coast\Social\Provider;

use Coast\Social;
use Coast\Social\Provider;
use Coast\Url;

abstract class External extends Provider
{
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

    abstract protected function _api();

    abstract protected function _request($method, array $args = array());
}