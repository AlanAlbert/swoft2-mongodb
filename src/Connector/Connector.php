<?php
/**
 * The file is part of the swoft_marketing_engine.
 *
 * (c) alan <alan1766447919@gmail.com>.
 *
 * 2020/11/20 5:49 下午
 */

namespace Anhoder\Mongodb\Connector;

use Anhoder\Mongodb\Contract\ConnectorInterface;
use MongoDB\Client;
use Swoft\Bean\Annotation\Mapping\Bean;

/**
 * Class MongoConnector
 * @package Database\Mongo
 *
 * @Bean()
 */
class Connector implements ConnectorInterface
{
    /**
     * @param array $config
     * @return \MongoDB\Client
     */
    public function connect(array $config): Client
    {
        $uri = sprintf('mongodb://%s', $config['host']);

        if (!empty($config['port'])) {
            $uri .= ":{$config['port']}";
        }

        if (!empty($config['database'])) {
            $uri .= "/{$config['database']}";
        }

        $uriOptions = $config['uriOptions'] ?? [];
        $driverOptions = $config['driverOptions'] ?? [];

        if (!empty($config['username'])) {
            $uriOptions['username'] = $config['username'];
        }
        if (!empty($config['password'])) {
            $uriOptions['password'] = $config['password'];
        }

        return new Client($uri, $uriOptions, $driverOptions);
    }
}
