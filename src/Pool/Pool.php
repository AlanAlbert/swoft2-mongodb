<?php
/**
 * The file is part of the swoft_marketing_engine.
 *
 * (c) alan <alan1766447919@gmail.com>.
 *
 * 2020/11/20 8:47 下午
 */

namespace Anhoder\Mongodb\Pool;

use Swoft\Connection\Pool\AbstractPool;
use Swoft\Connection\Pool\Contract\ConnectionInterface;

/**
 * Class MongoPool
 * @package Database\Mongo
 */
class Pool extends AbstractPool
{
    public const DEFAULT_POOL = 'mongodb.pool';

    /**
     * @var \Anhoder\Mongodb\MongoDb
     */
    private $mongoDb;

    /**
     * @return \Swoft\Connection\Pool\Contract\ConnectionInterface
     */
    public function createConnection(): ConnectionInterface
    {
        return $this->mongoDb->createConnection($this);
    }

    /**
     * @return \Anhoder\Mongodb\MongoDb
     */
    public function getDatabase()
    {
        return $this->mongoDb;
    }
}
