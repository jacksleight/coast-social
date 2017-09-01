# Coast Social

[![Packagist](http://img.shields.io/packagist/v/jacksleight/coast-social.svg?style=flat-square)](https://packagist.org/packages/jacksleight/coast-social)
[![License](http://img.shields.io/packagist/l/jacksleight/coast-social.svg?style=flat-square)](https://packagist.org/packages/jacksleight/coast-social)

Coast Social can retrieve latest post feeds and URL statistics from various social media platforms in a standardised format. It is currently a work in progress, there are not yet any stable releases.

## What's Supported?

:black_circle: = Supported, always present  
:white_circle: = Supported, when present

Method        | Facebook       | Twitter        | Instagram      | Pinterest
--------------| ---------------|----------------|----------------|---------------
feed          | :black_circle: | :black_circle: | :black_circle: | :black_circle:
urlStats      | :black_circle: |                |                | :black_circle:

#### feed

###### Input

Parameter     | Default | Facebook       | Twitter        | Instagram      | Pinterest
--------------| --------|----------------|----------------|----------------|---------------
id            |         | :black_circle: |                | :black_circle: | :black_circle:
username      |         | :black_circle: | :black_circle: | :black_circle: | 
limit         | 10      | :black_circle: | :black_circle: | :black_circle: | :black_circle:
raw           | []      | :black_circle: | :black_circle: | :black_circle: | :black_circle:

* The raw parameter allows you to parse additional parameters to each providers API, refer to the relevant API documentation for more information.

###### Output

Attribute     | Facebook       | Twitter        | Instagram      | Pinterest
--------------| ---------------|----------------|----------------|---------------
id            | :black_circle: | :black_circle: | :black_circle: | :black_circle: 
url           | :black_circle: | :black_circle: | :black_circle: | :black_circle:
date          | :black_circle: | :black_circle: | :black_circle: | :black_circle:
text          | :black_circle: | :black_circle: | :black_circle: | :black_circle:
html          | :black_circle: | :black_circle: | :black_circle: | :black_circle:
image.url     | :white_circle: | :white_circle: | :black_circle: | :black_circle: 
image.width   |                | :white_circle: | :black_circle: | :black_circle: 
image.height  |                | :white_circle: | :black_circle: | :black_circle: 
user.id       | :black_circle: | :black_circle: | :black_circle: | :black_circle:
user.url      | :black_circle: | :black_circle: | :black_circle: | :black_circle:
user.name     | :black_circle: | :black_circle: | :black_circle: | :black_circle:
user.username |                | :black_circle: | :black_circle: | :black_circle:
raw           | :black_circle: | :black_circle: | :black_circle: | :black_circle:

* The raw parameter contains the unmodified source from the API response.

#### urlStats

###### Input

A `Coast\Url` object.

###### Output

Stat          | Facebook       | Twitter        | Instagram      | Pinterest
--------------| ---------------|----------------|----------------|---------------
shares        | :black_circle: |                |                | :black_circle: 
comments      | :black_circle: |                |                | 

## Installation

The easiest way to install Coast Social is through [Composer](https://getcomposer.org/doc/00-intro.md), by creating a file called `composer.json` containing:

```json
{
    "require": {
        "jacksleight/coast-social": "dev-master"
    }
}
```

And then running:

```bash
composer.phar install
```

## Usage

#### Facebook

```php
$facebook = new Coast\Social\Provider\Facebook([
    'credentials' => [
        'appId'       => '',
        'appSecret'   => '',
        'accessToken' => '', // Get from https://developers.facebook.com/tools/explorer/
    ],
]);
$feed = $facebook->feed([
    'id'       => '', // User/page ID
    'username' => '', // User/page username
]);
$stats = $facebook->stats(new Coast\Url('http://www.example.com/'));
```

#### Twitter

```php
$twitter = new Coast\Social\Provider\Twitter([
    'credentials' => [
        'consumerKey'       => '',
        'consumerSecret'    => '',
        'accessToken'       => '',
        'accessTokenSecret' => '',
    ],
]);
$feed = $twitter->feed([
    'username' => '',
]);
```

#### Instagram

```php
$instagram = new Coast\Social\Provider\Instagram([
    'credentials' => [
        'accessToken' => '',
    ],
]);
$feed = $instagram->feed([
    'id'       => '', // User ID
    'username' => '',
]);
```

To get an Instagram access token create a client at [https://www.instagram.com/developer/](https://www.instagram.com/developer/), enable implicit OAuth in the security settings, and then request `https://api.instagram.com/oauth/authorize/?client_id=[CLIENTID]&redirect_uri=[REDIRECTURI]&response_type=token&scope=basic+public_content
`. The access token will appear in the redirect URI's query parameters.

#### Pinterest

```php
$pinterest = new Coast\Social\Provider\Pinterest([
    'credentials' => [
        'appId'       => '',
        'appSecret'   => '',
        'accessToken' => '', // Get from https://developers.pinterest.com/tools/access_token/
    ],
]);
$feed = $pinterest->feed([
    'id'      => '', // Board ID
]);
$stats = $pinterest->stats(new Coast\Url('http://www.example.com/'));
```

#### Aggregate

```php
$social = new Coast\Social([
    'providers' => [
        'twitter'  => $twitter,
        'facebook' => $facebook,
    ],
]);
$feed = $social->feed([ // Feeds from all providers
    'twitter'  => [
        'username' => '',
    ],
    'facebook' => [
        'id' => '',
    ],
]);
$feed = $social->feedFlat([ // Feeds from all providers merged and ordered by date
    'twitter'  => [
        'username' => '',
    ],
    'facebook' => [
        'id' => '',
    ],
]);
$stats = $social->stats(new Coast\Url('http://www.example.com/'));     // Stats from all providers
$stats = $social->statsFlat(new Coast\Url('http://www.example.com/')); // Stats from all providers added together
```

## Licence

The MIT License

Copyright (c) 2015 Jack Sleight <http://jacksleight.com/>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
