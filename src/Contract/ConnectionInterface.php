<?php
/**
 * The file is part of the swoft_marketing_engine.
 *
 * (c) alan <alan1766447919@gmail.com>.
 *
 * 2020/11/20 6:31 下午
 */

namespace Anhoder\Mongodb\Contract;


use Mongodb\Collection;
use MongoDB\Client;
use MongoDB\Database;

/**
 * Interface ConnectionInterface
 * @package Database\Mongo
 */
interface ConnectionInterface
{
    /**
     * @param string $dbname
     * @return \Anhoder\Mongodb\Contract\ConnectionInterface
     */
    public function db(string $dbname): ConnectionInterface;

    /**
     * @param string $collection
     * @return mixed
     */
    public function collection(string $collection);

    /**
     * @param string $table
     * @return mixed
     */
    public function table(string $table);

    /**
     * @param string $name
     * @return \Mongodb\Collection
     */
    public function getCollection(string $name): Collection;

    /**
     * @return mixed
     */
    public function getSchemaBuilder();

    /**
     * @return \MongoDB\Database
     */
    public function getMongoDB(): Database;

    /**
     * @return \MongoDB\Client
     */
    public function getMongoClient(): Client;

    /**
     * @return string
     */
    public function getDatabaseName(): string;
}
