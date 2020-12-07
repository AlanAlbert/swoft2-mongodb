<?php
/**
 * The file is part of the laravel-mongodb.
 *
 * (c) alan <alan1766447919@gmail.com>.
 *
 * 2020/11/21 12:49 下午
 */

return [
    // MongoDB
    'mongoDb'           => [
        'class'         => \Anhoder\Mongodb\MongoDb::class,
        'host'          => '127.0.0.1',
        'port'          => 27017,
        'username'      => null,
        'password'      => null,
        'database'      => 'db1',
        'uriOptions'    => [],
        'driverOptions' => [],
    ],
    'mongodb.pool'      => [
        'class'       => \Anhoder\Mongodb\Pool\Pool::class,
        'mongoDb'     => bean('mongoDb'),
        'minActive'   => 5,
        'maxActive'   => 10,
        'maxWait'     => 0,
        'maxWaitTime' => 0,
        'maxIdleTime' => 60,
    ],
];
