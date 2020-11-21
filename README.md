Swoft2 MongoDB
===============

针对Swoft2，基于[jenssegers/laravel-mongodb](https://github.com/jenssegers/laravel-mongodb)进行改造的MongoDB包（不支持协程）。

- [Swoft2 MongoDB](#swoft2-mongodb)
  - [Installation](#installation)
  - [Configuration](#configuration)

Installation
------------
Make sure you have the MongoDB PHP driver installed. You can find installation instructions at http://php.net/manual/en/mongodb.installation.php

Install the package via Composer:

```bash
$ composer require anhoder/swoft2-mongodb
```

Configuration
-------------
Add a new `mongodb` connection to `app/bean.php`:

```php
return [
    // MongoDB
    'mongoDb'           => [
        'class'         => \Anhoder\Mongodb\Swoft\MongoDb::class,
        'host'          => '127.0.0.1',
        'port'          => 27017,
        'username'      => null,
        'password'      => null,
        'database'      => 'db1',
        'uriOptions'    => [],
        'driverOptions' => [],
    ],
    'mongodb.pool'      => [
        'class'       => \Anhoder\Mongodb\Swoft\MongoPool::class,
        'mongoDb'     => bean('mongoDb'),
        'minActive'   => 5,
        'maxActive'   => 10,
        'maxWait'     => 0,
        'maxWaitTime' => 0,
        'maxIdleTime' => 60,
    ],
];

```
