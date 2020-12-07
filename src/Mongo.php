<?php
/**
 * The file is part of the swoft_marketing_engine.
 *
 * (c) alan <alan1766447919@gmail.com>.
 *
 * 2020/11/20 11:44 下午
 */

namespace Anhoder\Mongodb;

use Anhoder\Mongodb\Pool\Pool;
use Anhoder\Mongodb\Connection\ConnectionManager;
use Swoft\Bean\BeanFactory;
use Anhoder\Mongodb\Connection\Connection;
use Throwable;

/**
 * Class Mongo
 * @package Database\Mongo
 *
 * @method static array __debugInfo()
 * @method static \MongoDB\Collection __get($collectionName)
 * @method static string __toString()
 * @method static \Traversable aggregate(array $pipeline, array $options = [])
 * @method static \MongoDB\Driver\Cursor command($command, array $options = [])
 * @method static array|object createCollection($collectionName, array $options = [])
 * @method static array|object drop(array $options = [])
 * @method static array|object dropCollection($collectionName, array $options = [])
 * @method static \MongoDB\Driver\Manager getManager()
 * @method static \MongoDB\Driver\ReadConcern getReadConcern()
 * @method static \MongoDB\Driver\ReadPreference getReadPreference()
 * @method static array getTypeMap()
 * @method static \MongoDB\Driver\WriteConcern getWriteConcern()
 * @method static \Iterator listCollectionNames(array $options = [])
 * @method static \MongoDB\Model\CollectionInfoIterator listCollections(array $options = [])
 * @method static array|object modifyCollection($collectionName, array $collectionOptions, array $options = [])
 * @method static \MongoDB\Collection selectCollection($collectionName, array $options = [])
 * @method static \MongoDB\GridFS\Bucket selectGridFSBucket(array $options = [])
 * @method static \MongoDB\ChangeStream watch(array $pipeline = [], array $options = [])
 * @method static \MongoDB\Database withOptions(array $options = [])
 * @method static \Anhoder\Mongodb\Query\Builder collection($collection)
 * @method static \Anhoder\Mongodb\Query\Builder table($table, $as = null)
 * @method static \MongoDB\Collection getCollection($name)
 * @method static \MongoDB\Database getMongoDB()
 * @method static \MongoDB\Client getMongoClient()
 * @method static string getDatabaseName()
 * @method static string getDefaultDatabaseName($dsn, $config)
 * @method static \MongoDB\Client createConnection($dsn, array $config, array $options)
 * @method static disconnect()
 * @method static float getElapsedTime($start)
 * @method static string getDriverName()
 * @method static void create()
 * @method static bool reconnect()
 * @method static void close()
 * @method static void release(bool $force = false)
 */
class Mongo
{
    /**
     * @param string $pool
     *
     * @return Connection
     * @throws \Anhoder\Mongodb\MongoException
     */
    public static function connection(string $pool = Pool::DEFAULT_POOL): Connection
    {
        try {
            /* @var ConnectionManager $conManager */
            $conManager = BeanFactory::getBean(ConnectionManager::class);

            /* @var Pool $mongoPool */
            $mongoPool  = BeanFactory::getBean($pool);
            if (!$mongoPool instanceof Pool) {
                throw new MongoException(sprintf('%s is not instance of pool', $pool));
            }

            $connection = $mongoPool->getConnection();

            $connection->setPoolName($pool);
            $connection->setRelease(true);
            $conManager->setConnection($connection, $pool);
        } catch (Throwable $e) {
            throw new MongoException(
                sprintf('Mongo pool error is %s file=%s line=%d', $e->getMessage(), $e->getFile(), $e->getLine())
            );
        }

        // Not instanceof Connection
        if (!$connection instanceof Connection) {
            throw new MongoException(
                sprintf('%s is not instanceof %s', get_class($connection), Connection::class)
            );
        }
        return $connection;
    }



    /**
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     * @throws \Anhoder\Mongodb\MongoException
     */
    public static function __callStatic(string $method, array $arguments)
    {
        $connection = self::connection();
        $ret = $connection->{$method}(...$arguments);
        $connection->release();

        return $ret;
    }
}
