<?php

namespace Anhoder\Mongodb;

use Exception;
use MongoDB\BSON\ObjectID;
use MongoDB\Collection as MongoCollection;

/**
 * Class Collection
 * @package Anhoder\Mongodb
 *
 * @method array __debugInfo()
 * @method string __toString()
 * @method \Traversable aggregate(array $pipeline, array $options = [])
 * @method \MongoDB\BulkWriteResult bulkWrite(array $operations, array $options = [])
 * @method int count($filter = [], array $options = [])
 * @method int countDocuments($filter = [], array $options = [])
 * @method string createIndex($key, array $options = [])
 * @method string[] createIndexes(array $indexes, array $options = [])
 * @method \MongoDB\DeleteResult deleteMany($filter, array $options = [])
 * @method \MongoDB\DeleteResult deleteOne($filter, array $options = [])
 * @method mixed distinct($fieldName, $filter = [], array $options = [])
 * @method array|object drop(array $options = [])
 * @method array|object dropIndex($indexName, array $options = [])
 * @method array|object dropIndexes(array $options = [])
 * @method int estimatedDocumentCount(array $options = [])
 * @method array|object explain(\MongoDB\Operation\Explainable $explainable, array $options = [])
 * @method \MongoDB\Driver\Cursor find($filter = [], array $options = [])
 * @method array|object|null findOne($filter = [], array $options = [])
 * @method array|object|null findOneAndDelete($filter, array $options = [])
 * @method array|object|null findOneAndReplace($filter, $replacement, array $options = [])
 * @method array|object|null findOneAndUpdate($filter, $update, array $options = [])
 * @method string getCollectionName()
 * @method string getDatabaseName()
 * @method \MongoDB\Driver\Manager getManager()
 * @method string getNamespace()
 * @method \MongoDB\Driver\ReadConcern getReadConcern()
 * @method \MongoDB\Driver\ReadPreference getReadPreference()
 * @method array getTypeMap()
 * @method \MongoDB\Driver\WriteConcern getWriteConcern()
 * @method \MongoDB\InsertManyResult insertMany(array $documents, array $options = [])
 * @method \MongoDB\InsertOneResult insertOne($document, array $options = [])
 * @method \MongoDB\Model\IndexInfoIterator listIndexes(array $options = [])
 * @method \MongoDB\MapReduceResult mapReduce(\MongoDB\BSON\JavascriptInterface $map, \MongoDB\BSON\JavascriptInterface $reduce, $out, array $options = [])
 * @method \MongoDB\UpdateResult replaceOne($filter, $replacement, array $options = [])
 * @method \MongoDB\UpdateResult updateMany($filter, $update, array $options = [])
 * @method \MongoDB\UpdateResult updateOne($filter, $update, array $options = [])
 * @method \MongoDB\ChangeStream watch(array $pipeline = [], array $options = [])
 * @method \MongoDB\Collection withOptions(array $options = [])
 */
class Collection
{
    /**
     * The connection instance.
     * @var Connection
     */
    protected $connection;

    /**
     * The MongoCollection instance..
     * @var MongoCollection
     */
    protected $collection;

    /**
     * @param Connection $connection
     * @param MongoCollection $collection
     */
    public function __construct(Connection $connection, MongoCollection $collection)
    {
        $this->connection = $connection;
        $this->collection = $collection;
    }

    /**
     * Handle dynamic method calls.
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $start = microtime(true);
        $result = call_user_func_array([$this->collection, $method], $parameters);

        // Once we have run the query we will calculate the time that it took to run and
        // then log the query, bindings, and execution time so we will report them on
        // the event that the developer needs them. We'll log time in milliseconds.
        $time = $this->connection->getElapsedTime($start);

        $query = [];

        // Convert the query parameters to a json string.
        array_walk_recursive($parameters, function (&$item, $key) {
            if ($item instanceof ObjectID) {
                $item = (string) $item;
            }
        });

        // Convert the query parameters to a json string.
        foreach ($parameters as $parameter) {
            try {
                $query[] = json_encode($parameter);
            } catch (Exception $e) {
                $query[] = '{...}';
            }
        }

        $queryString = $this->collection->getCollectionName() . '.' . $method . '(' . implode(',', $query) . ')';

        $this->connection->logQuery($queryString, [], $time);

        return $result;
    }
}
