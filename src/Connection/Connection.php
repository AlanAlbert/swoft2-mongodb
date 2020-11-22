<?php
/**
 * The file is part of the swoft2-mongodb.
 *
 * (c) alan <alan1766447919@gmail.com>.
 *
 * 2020/11/21 9:17 下午
 */

namespace Anhoder\Mongodb\Connection;

use Anhoder\Mongodb\Contract\ConnectionInterface;
use Mongodb\Collection;
use Anhoder\Mongodb\MongoDb;
use Anhoder\Mongodb\Pool\Pool;
use Anhoder\Mongodb\Query\Builder;
use MongoDB\Client;
use MongoDB\Database;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\BeanFactory;
use Swoft\Connection\Pool\AbstractConnection;
use Swoft\Db\Concern\HasEvent;
use Anhoder\Mongodb\Query\Grammar;
use Anhoder\Mongodb\Query\Processor;
use Swoft\Log\Helper\Log;
use Throwable;

/**
 * Class Connection
 * @package Anhoder\Mongodb
 *
 * @Bean(scope=Bean::PROTOTYPE)
 *
 * @method array __debugInfo()
 * @method \MongoDB\Collection __get($collectionName)
 * @method string __toString()
 * @method \Traversable aggregate(array $pipeline, array $options = [])
 * @method \MongoDB\Driver\Cursor command($command, array $options = [])
 * @method array|object createCollection($collectionName, array $options = [])
 * @method array|object drop(array $options = [])
 * @method array|object dropCollection($collectionName, array $options = [])
 * @method \MongoDB\Driver\Manager getManager()
 * @method \MongoDB\Driver\ReadConcern getReadConcern()
 * @method \MongoDB\Driver\ReadPreference getReadPreference()
 * @method array getTypeMap()
 * @method \MongoDB\Driver\WriteConcern getWriteConcern()
 * @method \Iterator listCollectionNames(array $options = [])
 * @method \MongoDB\Model\CollectionInfoIterator listCollections(array $options = [])
 * @method array|object modifyCollection($collectionName, array $collectionOptions, array $options = [])
 * @method \MongoDB\Collection selectCollection($collectionName, array $options = [])
 * @method \MongoDB\GridFS\Bucket selectGridFSBucket(array $options = [])
 * @method \MongoDB\ChangeStream watch(array $pipeline = [], array $options = [])
 * @method Database withOptions(array $options = [])
 */
class Connection extends AbstractConnection implements ConnectionInterface
{
    use HasEvent;

    /**
     * The MongoDB database handler.
     * @var MongoDb
     */
    protected $database;

    /**
     * The MongoDB connection handler.
     * @var Client
     */
    protected $client;

    /**
     * The query grammar implementation.
     *
     * @var Grammar
     */
    protected $queryGrammar;

    /**
     * @var Processor
     */
    protected $postProcessor;

    /**
     * @var string
     */
    protected $defaultDbName;

    /**
     * @var \MongoDB\Database
     */
    protected $db;

    /**
     * Replace constructor
     *
     * @param Pool $pool
     * @param \Anhoder\Mongodb\MongoDb $database
     */
    public function initialize(Pool $pool, MongoDb $database): void
    {
        $this->pool     = $pool;
        $this->database = $database;
        $this->lastTime = time();

        // We need to initialize a query grammar and the query post processors
        // which are both very important parts of the database abstractions
        // so we initialize these to their default values while starting.
        $this->useDefaultQueryGrammar();

        $this->useDefaultPostProcessor();

        $this->id = $this->pool->getConnectionId();
    }

    /**
     * Set the query grammar to the default implementation.
     *
     * @return void
     */
    public function useDefaultQueryGrammar()
    {
        $this->queryGrammar = $this->getDefaultQueryGrammar();
    }

    /**
     * Get the default query grammar instance.
     *
     * @return Grammar
     */
    protected function getDefaultQueryGrammar(): Grammar
    {
        return new Grammar();
    }

    /**
     * Get the query post processor used by the connection.
     *
     * @return Processor
     */
    public function getPostProcessor(): Processor
    {
        return $this->postProcessor;
    }

    /**
     * Get the default post processor instance.
     *
     * @return Processor
     */
    protected function getDefaultPostProcessor(): Processor
    {
        return bean(Processor::class);
    }

    /**
     * Set the query post processor to the default implementation.
     *
     * @return void
     */
    public function useDefaultPostProcessor(): void
    {
        $this->postProcessor = $this->getDefaultPostProcessor();
    }

    /**
     * @inheritDoc
     */
    public function create(): void
    {
        $config = [
            'host'           => $this->database->getHost(),
            'port'           => $this->database->getPort(),
            'username'       => $this->database->getUsername(),
            'password'       => $this->database->getPassword(),
            'database'       => $this->database->getDatabase(),
            'uriOptions'     => $this->database->getUriOptions(),
            'driverOptions'  => $this->database->getDriverOptions(),
        ];
        $this->client = $this->database->getConnector()->connect($config);

        $this->defaultDbName = $this->database->getDatabase();
        if (!empty($this->defaultDbName)) $this->db = $this->client->selectDatabase($this->defaultDbName);
    }

    /**
     * @inheritDoc
     */
    public function reconnect(): bool
    {
        try {
            $this->create();
        } catch (Throwable $e) {
            Log::error("MongoDB reconnect fail: {$e->getMessage()}");
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        $this->client = null;
    }

    /**
     * @param bool $force
     */
    public function release(bool $force = false): void
    {
        $cm = $this->getConManager();
        $cm->releaseConnection($this->id, $this->poolName);

        // Reset select db name
        $this->resetDb();

        // Release connection
        parent::release($force);
    }

    /**
     * @return ConnectionManager
     *
     */
    protected function getConManager(): ConnectionManager
    {
        return BeanFactory::getBean(ConnectionManager::class);
    }

    /**
     * Reset db name
     *
     */
    private function resetDb(): void
    {
        if ($this->db->getDatabaseName() == $this->defaultDbName) {
            return;
        }

        $this->db = $this->client->selectDatabase($this->defaultDbName);
    }

    /**
     * @return Grammar
     */
    public function getQueryGrammar(): Grammar
    {
        return $this->queryGrammar;
    }

    /**
     * Set the table prefix and return the grammar.
     *
     * @param Grammar $grammar
     *
     * @return Grammar
     */
    public function withTablePrefix(Grammar $grammar): Grammar
    {
        $grammar->setTablePrefix($this->database->getPrefix());

        return $grammar;
    }

    /**
     * Set the table prefix in use by the connection.
     *
     * @param string $prefix
     *
     * @return static
     */
    public function setTablePrefix(string $prefix): self
    {
        $this->getQueryGrammar()->setTablePrefix($prefix);

        return $this;
    }

    /**
     * @param string $dbname
     *
     * @return static
     */
    public function db(string $dbname): ConnectionInterface
    {
        if ($this->db->getDatabaseName() === $dbname) {
            return $this;
        }

        $this->db = $this->client->selectDatabase($dbname);

        return $this;
    }

    /**
     * Begin a fluent query against a database collection.
     * @param string $collection
     * @return \Swoft\Db\Query\Builder
     */
    public function collection(string $collection)
    {
        $query = Builder::new($this->poolName, $this->getQueryGrammar(), $this->getPostProcessor());

        return $query->from($collection);
    }

    /**
     * Begin a fluent query against a database collection.
     * @param string $table
     * @return \Swoft\Db\Query\Builder
     */
    public function table(string $table)
    {
        return $this->collection($table);
    }

    /**
     * Get a MongoDB collection.
     * @param string $name
     * @return Collection
     */
    public function getCollection(string $name): Collection
    {
        return $this->db->selectCollection($name);
    }

    /**
     * @return \Swoft\Db\Schema\Builder
     */
    public function getSchemaBuilder()
    {
        return \Anhoder\Mongodb\Schema\Builder::new($this->poolName);
    }

    /**
     * Get the MongoDB database object.
     * @return Database
     */
    public function getMongoDB(): Database
    {
        return $this->db;
    }

    /**
     * return MongoDB object.
     * @return Client
     */
    public function getMongoClient(): Client
    {
        return $this->client;
    }

    /**
     * @return string
     */
    public function getDatabaseName(): string
    {
        return $this->getMongoDB()->getDatabaseName();
    }

    /**
     * Dynamically pass methods to the connection.
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->db->{$method}(...$parameters);
    }
}
