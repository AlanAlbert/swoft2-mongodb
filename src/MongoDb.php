<?php
/**
 * The file is part of the swoft_marketing_engine.
 *
 * (c) alan <alan1766447919@gmail.com>.
 *
 * 2020/11/20 5:19 下午
 */

namespace Anhoder\Mongodb;

use Anhoder\Mongodb\Connector\Connector;
use Anhoder\Mongodb\Contract\ConnectorInterface;
use Anhoder\Mongodb\Connection\Connection;
use Anhoder\Mongodb\Pool\Pool;

/**
 * MongoDb配置信息
 * Class MongoDb
 * @package Database\Mongo
 */
class MongoDb
{
    public const MONGODB = 'mongodb';

    /**
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     * @var int
     */
    protected $port = 27017;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $database;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var array
     */
    protected $uriOptions = [];

    /**
     * @var array
     */
    protected $driverOptions = [];

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @return string
     */
    public function getDriver(): string
    {
        return static::MONGODB;
    }

    /**
     * @return array
     */
    public function getUriOptions(): array
    {
        return $this->uriOptions;
    }

    /**
     * @return array
     */
    public function getDriverOptions(): array
    {
        return $this->driverOptions;
    }

    /**
     * Get connector.
     * @return ConnectorInterface
     */
    public function getConnector(): ConnectorInterface
    {
        return bean(Connector::class);
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return bean(Connection::class);
    }

    /**
     * @param \Anhoder\Mongodb\Pool\Pool $pool
     * @return \Anhoder\Mongodb\Connection\Connection
     */
    public function createConnection(Pool $pool): Connection
    {
        $connection = $this->getConnection();
        $connection->initialize($pool, $this);
        $connection->create();

        return $connection;
    }
}
