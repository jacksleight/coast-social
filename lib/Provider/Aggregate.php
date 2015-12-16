<?php
/*
 * Copyright 2015 Jack Sleight <http://jacksleight.com/>
 * This source file is subject to the MIT license that is bundled with this package in the file LICENCE. 
 */

namespace Coast\Social\Provider;

use Carbon\Carbon;
use Coast\Url;
use Coast\Social\Provider;
use Facebook\Facebook as FacebookSdk;

class Aggregate implements Provider
{
    protected $_providers = [];

    public function provider($name, Provider $value = null)
    {
        if (func_num_args() > 1) {
            $this->_providers[$name] = $value;
            return $this;
        }
        return isset($this->_providers[$name])
            ? $this->_providers[$name]
            : null;
    }

    public function providers(array $providers = null)
    {
        if (func_num_args() > 0) {
            foreach ($providers as $name => $value) {
                $this->provider($name, $value);
            }
            return $this;
        }
        return $this->_providers;
    }

    public function feed(array $params, array $extra = array())
    {
        $feed = [];
        foreach ($params as $name => $providerParams) {
            $provider = $this->provider($name);
            $providerExtra = isset($extra[$name])
                ? $extra[$name]
                : [];
            $data = $provider->feed($providerParams, $providerExtra);
            if ($data !== false) {
                foreach ($data as $i => $item) {
                    $data[$i]['provider'] = $name;
                }
                $feed = array_merge($feed, $data);
            }
        }

        usort($feed, function($a, $b) {
            return $a['date'] < $b['date'];
        });

        return $feed;
    }

    public function stats(Url $url)
    {
        $stats = [];
        foreach ($this->_providers as $name => $provider) {
            $data = $provider->stats($url);
            if ($data !== false) {
                $stats[$name] = $data;
            }
        }

        return $stats;
    }
}