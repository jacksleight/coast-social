<?php
/*
 * Copyright 2017 Jack Sleight <http://jacksleight.com/>
 * This source file is subject to the MIT license that is bundled with this package in the file LICENCE. 
 */

namespace Coast\Social\Provider;

use Coast\Social;
use Coast\Social\Provider;
use Coast\Url;
use Coast\Http as Http;

abstract class External extends Provider
{
    protected $_credentials = [];

    protected $_http;

    protected $_endpoint;

    protected $_isRefreshed = false;

    public function __construct(array $options = array())
    {
        parent::__construct($options);
        $this->_http = new Http();
    }

    public function credentials($credentials = null)
    {
        if (func_num_args() > 0) {
            $this->_credentials = $credentials;
            return $this;
        }
        return $this->_credentials;
    }

    public function fetch($method, array $params = array(), $lifetime = 3600)
    {
        // @todo Implement proper caching
        // $key = md5(
        //     get_class($this) .
        //     serialize($this->_credentials) .
        //     $method .
        //     serialize($params)
        // );
        // $data = apcu_fetch($key);
        // if ($data === false) {
        //     $data = $this->request($method, $params);
        //     apcu_add($key, $data, $lifetime);
        // }

        $data = $this->request($method, $params);

        return $data;
    }

    public function api()
    {
        if (!isset($this->_api)) {
            $this->_api = $this->_api();
        }
        return $this->_api;
    }

    public function request($method, array $params = array())
    {
        if (!$this->_isRefreshed) {
            $this->_refresh();
            $this->_isRefreshed = true;
        }
        try {
            return $this->_request($method, $params);
        } catch (Social\Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new Social\Exception($e->getMessage());
        }
    }

    protected function _refresh()
    {}

    abstract protected function _request($method, array $params = array());
}