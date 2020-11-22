<?php

namespace Anhoder\Mongodb\Schema;

use Anhoder\Mongodb\MongoDb;
use Anhoder\Mongodb\Pool\Pool;
use Closure;
use Anhoder\Mongodb\Connection\Connection;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\Exception\DbException;
use Swoft\Db\Schema\Builder as BaseBuilder;
use Swoft\Db\Schema\Grammars\Grammar;

/**
* Class Builder
 *
 * @Bean(scope=Bean::PROTOTYPE)
*
 * @since 2.0
*/
class Builder extends BaseBuilder
{
    /**
     * @var array
     */
    public $grammars = [
        MongoDb::MONGODB => \Anhoder\Mongodb\Schema\Grammar::class
    ];

    /**
     * @var array
     */
    public static $builders = [
        MongoDb::MONGODB => Builder::class
    ];

    /**
     * New builder instance
     *
     * @param mixed ...$params
     *
     * @return \Swoft\Db\Schema\Builder
     * @throws DbException
     */
    public static function new(...$params): BaseBuilder
    {
        /**
         * @var string|null  $poolName
         * @var Grammar|null $grammar
         */
        if (empty($params)) {
            $poolName = Pool::DEFAULT_POOL;
            $grammar  = null;
        } else {
            $poolName = $params[0];
            $grammar  = $params[1] ?? null;
        }
        // The driver builder
        $static = self::getBuilder($poolName);
        // Set schema config
        $static->setSchemaGrammar($grammar);

        return $static;
    }

    /**
     * @inheritdoc
     */
    public function hasColumn($table, $column): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function hasColumns($table, array $columns)
    {
        return true;
    }

    /**
     * Determine if the given collection exists.
     * @param $name
     * @return bool
     */
    public function hasCollection($name)
    {
        /**
         * @var $connection \Anhoder\Mongodb\Connection\Connection
         */
        $connection = $this->getConnection();
        $db = $connection->getMongoDB();

        $collections = iterator_to_array($db->listCollections([
            'filter' => [
                'name' => $name,
            ],
        ]), false);

        return count($collections) ? true : false;
    }

    /**
     * @inheritdoc
     */
    public function hasTable($table): bool
    {
        return $this->hasCollection($table);
    }

    /**
     * Modify a collection on the schema.
     * @param $collection
     * @param Closure $callback
     */
    public function collection($collection, Closure $callback)
    {
        $blueprint = $this->createBlueprint($collection);

        if ($callback) {
            $callback($blueprint);
        }
    }

    /**
     * @inheritdoc
     */
    public function table($table, Closure $callback)
    {
        $this->collection($table, $callback);
    }

    /**
     * @inheritdoc
     */
    public function create($table, Closure $callback = null, array $options = [])
    {
        $blueprint = $this->createBlueprint($table);

        $blueprint->create($options);

        if ($callback) {
            $callback($blueprint);
        }
    }

    /**
     * @inheritdoc
     */
    public function dropIfExists($table)
    {
        if ($this->hasCollection($table)) {
            return $this->drop($table);
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function drop($table)
    {
        $blueprint = $this->createBlueprint($table);

        return $blueprint->drop();
    }

    /**
     * @inheritdoc
     */
    public function dropAllTables()
    {
        foreach ($this->getAllCollections() as $collection) {
            $this->drop($collection);
        }
    }

    /**
     * @inheritdoc
     */
    protected function createBlueprint($table, Closure $callback = null)
    {
        /**
         * @var Connection $connection
         */
        $connection = $this->getConnection();
        return new Blueprint($connection, $table);
    }

    /**
     * Get collection.
     * @param $name
     * @return bool|\MongoDB\Model\CollectionInfo
     */
    public function getCollection($name)
    {
        /**
         * @var Connection $connection
         */
        $connection = $this->getConnection();
        $db = $connection->getMongoDB();

        $collections = iterator_to_array($db->listCollections([
            'filter' => [
                'name' => $name,
            ],
        ]), false);

        return count($collections) ? current($collections) : false;
    }

    /**
     * Get all of the collections names for the database.
     * @return array
     */
    protected function getAllCollections()
    {
        $collections = [];
        /**
         * @var Connection $connection
         */
        $connection = $this->getConnection();
        foreach ($connection->getMongoDB()->listCollections() as $collection) {
            $collections[] = $collection->getName();
        }

        return $collections;
    }
}
