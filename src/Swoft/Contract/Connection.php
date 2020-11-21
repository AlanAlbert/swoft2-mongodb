<?php
/**
 * The file is part of the swoft_marketing_engine.
 *
 * (c) alan <alan1766447919@gmail.com>.
 *
 * 2020/11/20 5:46 下午
 */

namespace Anhoder\Mongodb\Swoft\Contract;

use Anhoder\Mongodb\Swoft\ConnectionManager;
use Anhoder\Mongodb\Swoft\MongoDb;
use Anhoder\Mongodb\Swoft\MongoPool;
use Swoft\Connection\Pool\AbstractConnection;
use Swoft\Log\Helper\Log;
use Throwable;

/**
 * Class Connection
 * @package Database\Mongo
 *
 */
abstract class Connection extends AbstractConnection implements ConnectionInterface
{
    /**
     * @var \Anhoder\Mongodb\Connection
     */
    protected $connection;

    /**
     * @var MongoDb
     */
    protected $config;

    /**
     * @param \Anhoder\Mongodb\Swoft\MongoPool $pool
     * @param \Anhoder\Mongodb\Swoft\MongoDb $mongoDb
     */
    public function initialize(MongoPool $pool, MongoDb $mongoDb): void
    {
        $this->pool     = $pool;
        $this->config   = $mongoDb;
        $this->lastTime = time();

        $this->id = $this->pool->getConnectionId();
    }

    /**
     * Create Connection.
     */
    public function create(): void
    {
        $config = [
            'host'           => $this->config->getHost(),
            'port'           => $this->config->getPort(),
            'username'       => $this->config->getUsername(),
            'password'       => $this->config->getPassword(),
            'database'       => $this->config->getDatabase(),
            'uriOptions'     => $this->config->getUriOptions(),
            'driverOptions'  => $this->config->getDriverOptions(),
        ];
        $creator = $this->config->getConnector();
        $this->connection = $creator->connect($config);
    }

    /**
     * @return bool
     */
    public function reconnect(): bool
    {
        try {
            $this->create();
        } catch (Throwable $e) {
            Log::error("MongoDB重连失败({$e->getMessage()})");
            return false;
        }

        return true;
    }

    /**
     * Close connection.
     */
    public function close(): void
    {
        $this->connection->disconnect();
    }

    /**
     * @param bool $force
     */
    public function release(bool $force = false): void
    {
        /**
         * @var $connectionManager ConnectionManager
         */
        $connectionManager = bean(ConnectionManager::class);
        $connectionManager->releaseConnection($this->id);
        parent::release($force);
    }

    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->connection->{$method}(...$parameters);
    }

}
