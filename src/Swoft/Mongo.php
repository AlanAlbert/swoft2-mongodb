<?php
/**
 * The file is part of the swoft_marketing_engine.
 *
 * (c) alan <alan1766447919@gmail.com>.
 *
 * 2020/11/20 11:44 下午
 */

namespace Anhoder\Mongodb\Swoft;

use Anhoder\Mongodb\Swoft\Contract\Connection;
use Swoft\Bean\BeanFactory;
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
 */
class Mongo
{
    /**
     * @param string $pool
     *
     * @return Connection
     * @throws \Anhoder\Mongodb\Swoft\MongoException
     */
    public static function connection(string $pool = MongoPool::DEFAULT_POOL): Connection
    {
        try {
            /* @var ConnectionManager $conManager */
            $conManager = BeanFactory::getBean(ConnectionManager::class);

            /* @var MongoPool $redisPool */
            $redisPool  = BeanFactory::getBean($pool);
            $connection = $redisPool->getConnection();

            $connection->setRelease(true);
            $conManager->setConnection($connection);
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
     * @throws \Anhoder\Mongodb\Swoft\MongoException
     */
    public static function __callStatic(string $method, array $arguments)
    {
        $connection = self::connection();
        return $connection->{$method}(...$arguments);
    }
}
