<?php
/*
 * Copyright 2017 Jack Sleight <http://jacksleight.com/>
 * This source file is subject to the MIT license that is bundled with this package in the file LICENCE. 
 */

namespace Coast\Social;

use Coast\Url;
use Coast\Social\Provider;

class Social extends Provider
{
    const VERSION = '0.2.0';

    protected $_providers = [];

    public function provider($name, Provider $value = null)
    {
        if (func_num_args() > 1) {
            if (isset($value)) {
                $this->_providers[$name] = $value;
            } else {
                unset($this->_providers[$name]);
            }
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

    public function feed(array $params)
    {
        $feed = [];
        foreach ($params as $name => $providerParams) {
            $provider = $this->provider($name);
            $data = $provider->feed($providerParams);
            if ($data !== false) {
                foreach ($data as $i => $item) {
                    $data[$i]['provider'] = $name;
                }
                $feed[$name] = $data;
            }
        }

        return $feed;
    }

    public function feedFlat(array $params)
    {
        $data = $this->feed($params);

        $feed = [];
        foreach ($data as $inner) {
            $feed = array_merge($feed, $inner);
        }
        usort($feed, function($a, $b) {
            return $a['date'] < $b['date'];
        });

        return $feed;
    }

    public function urlStats(Url $url)
    {
        $stats = [];
        foreach ($this->_providers as $name => $provider) {
            $data = $provider->urlStats($url);
            if ($data !== false) {
                $stats[$name] = $data;
            }
        }

        return $stats;
    }

    public function urlStatsFlat(Url $url)
    {
        $data = $this->urlStats($url);

        $stats = [
            'shares'   => 0,
            'comments' => 0,
        ];
        foreach ($data as $inner) {
            $stats = [
                'shares'   => $stats['shares']   + $inner['shares'],
                'comments' => $stats['comments'] + $inner['comments'],
            ];
        }

        return $stats;
    }

    /**
     * Set a provider
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function __set($name, $value)
    {
        return $this->provider($name, $value);
    }

    /**
     * Get a provider
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->provider($name);
    }

    /**
     * Check if a providerexists.
     * @param  string  $name
     * @return boolean
     */
    public function __isset($name)
    {
        return $this->provider($name) !== null;
    }

    /**
     * Unset a provider
     * @param  string  $name
     * @return boolean
     */
    public function __unset($name)
    {
        return $this->provider($name, null);
    }
}