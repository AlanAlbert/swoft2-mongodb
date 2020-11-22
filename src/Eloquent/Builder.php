<?php

namespace Anhoder\Mongodb\Eloquent;

use Swoft\Db\Eloquent\Builder as EloquentBuilder;
use MongoDB\Driver\Cursor;
use MongoDB\Model\BSONDocument;

/**
 * Class Builder
 * @package Anhoder\Mongodb\Eloquent
 */
class Builder extends EloquentBuilder
{
    /**
     * The methods that should be returned from query builder.
     * @var array
     */
    protected $passthru = [
        'toSql',
        'insert',
        'insertGetId',
        'pluck',
        'count',
        'min',
        'max',
        'avg',
        'sum',
        'exists',
        'push',
        'pull',
    ];

    /**
     * @inheritdoc
     */
    public function update(array $values, array $options = []): int
    {
        $values = $this->model->getSafeAttributes($values);
        return $this->toBase()->update($this->addUpdatedAtColumn($values), $options);
    }

    /**
     * @inheritdoc
     */
    public function chunkById($count, callable $callback, $column = '_id', $alias = null): bool
    {
        return parent::chunkById($count, $callback, $column, $alias);
    }

    /**
     * @param null $expression
     * @return \Swoft\Db\Eloquent\Collection|\Swoft\Db\Eloquent\Model|\Swoft\Db\Query\Expression
     */
    public function raw($expression = null)
    {
        // Get raw results from the query builder.
        $results = $this->query->raw($expression);

        // Convert MongoCursor results to a collection of models.
        if ($results instanceof Cursor) {
            $results = iterator_to_array($results, false);

            return $this->model->hydrate($results);
        } // Convert Mongo BSONDocument to a single object.
        elseif ($results instanceof BSONDocument) {
            $results = $results->getArrayCopy();

            return $this->model->newFromBuilder((array) $results);
        } // The result is a single object.
        elseif (is_array($results) && array_key_exists('_id', $results)) {
            return $this->model->newFromBuilder((array) $results);
        }

        return $results;
    }

    /**
     * Add the "updated at" column to an array of values.
     * TODO Remove if https://github.com/laravel/framework/commit/6484744326531829341e1ff886cc9b628b20d73e
     * wiil be reverted
     * Issue in laravel frawework https://github.com/laravel/framework/issues/27791
     * @param array $values
     * @return array
     */
    protected function addUpdatedAtColumn(array $values): array
    {
        if (!$this->model->usesTimestamps() || $this->model->getUpdatedAtColumn() === null) {
            return $values;
        }

        $column = $this->model->getUpdatedAtColumn();
        $values = array_merge(
            [$column => $this->model->freshTimestamp($column)],
            $values
        );

        return $values;
    }

    /**
     * @return \Swoft\Db\Connection\Connection
     */
    public function getConnection()
    {
        return $this->query->getConnection();
    }
}
