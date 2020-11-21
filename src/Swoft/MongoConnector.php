<?php
/**
 * The file is part of the swoft_marketing_engine.
 *
 * (c) alan <alan1766447919@gmail.com>.
 *
 * 2020/11/20 5:49 下午
 */

namespace Anhoder\Mongodb\Swoft;

use Anhoder\Mongodb\Connection;
use Anhoder\Mongodb\Swoft\Contract\ConnectorInterface;
use Swoft\Bean\Annotation\Mapping\Bean;

/**
 * Class MongoConnector
 * @package Database\Mongo
 *
 * @Bean()
 */
class MongoConnector implements ConnectorInterface
{
    /**
     * @param array $config
     * @return \Anhoder\Mongodb\Connection
     */
    public function connect(array $config): Connection
    {
        $uri  =sprintf('mongodb://%s', $config['host']);

        if (isset($config['port'])) {
            $uri .= ":{$config['port']}";
        }

        if (isset($config['database'])) {
            $uri .= "/{$config['database']}";
        }

        $uriOptions = $config['uriOptions'] ?? [];
        $driverOptions = $config['driverOptions'] ?? [];

        if (isset($config['username'])) {
            $uriOptions['username'] = $config['username'];
        }
        if (isset($config['password'])) {
            $uriOptions['password'] = $config['password'];
        }

        $config = [
            'dsn'           => $uri,
            'options'       => $uriOptions,
            'driverOptions' => $driverOptions,
        ];

        return new Connection($config);
    }
}
