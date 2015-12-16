# Coast Social

Coast component for accessing social media APIs.

Coast Social can be used to retrieve latest feeds and URL statistics from various social media platforms.

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

```php
use Coast\Social\Provider\Facebook;
use Coast\Social\Provider\Twitter;
use Coast\Social\Provider\Instagram;
use Coast\Social\Provider\Pinterest;

$provider = new Twitter([
    'credentials' => [
        'consumerKey'       => '',
        'consumerSecret'    => '',
        'accessToken'       => '',
        'accessTokenSecret' => '',
    ],
]);
$feed = $provider->feed([
    'username' => '',
    'limit'    => 10,
]);

$provider = new Instagram([
    'credentials' => [
        'clientId' => '',
    ],
]);
$feed = $provider->feed([
    'username' => '', // or…
    'userId'   => '',
    'limit'    => 10,
]);

$provider = new Facebook([
    'credentials' => [
        'appId'       => '',
        'appSecret'   => '',
        'accessToken' => '', // Get from https://developers.facebook.com/tools/explorer/
    ],
]);
$feed = $provider->feed([
    'objectId' => '',
    'limit'    => 10,
]);
$stats = $provider->stats(new Coast\Url('http://www.example.com/'));

$provider = new Pinterest([
    'credentials' => [
        'appId'       => '',
        'appSecret'   => '',
        'accessToken' => '', // Get from https://developers.pinterest.com/tools/access_token/
    ],
]);
$feed = $provider->feed([
    'boardId' => '',
    'limit'   => 10,
]);
$stats = $provider->stats(new Coast\Url('http://www.example.com/'));
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
