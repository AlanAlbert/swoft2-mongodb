<?php

namespace Anhoder\Mongodb\Eloquent;

use Anhoder\Mongodb\Mongo;
use Anhoder\Mongodb\Swoft\MongoException;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use MongoDB\BSON\UTCDateTimeInterface;
use Anhoder\Mongodb\Connection\Connection;
use Swoft\Db\Eloquent\Model as BaseModel;
use Swoft\Stdlib\Helper\Arr;
use Swoft\Stdlib\Helper\Str;
use Anhoder\Mongodb\Query\Builder as QueryBuilder;
use MongoDB\BSON\Binary;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDateTime;
use Swoft\Db\EntityRegister;

/**
 * Class Model
 * @package Anhoder\Mongodb\Eloquent
 */
abstract class Model extends BaseModel
{
    /**
     * The collection associated with the model.
     * @var string
     */
    protected $collection;

    /**
     * The primary key for the model.
     * @var string
     */
    protected $primaryKey = '_id';

    /**
     * The primary key type.
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Custom accessor for the model's id.
     * @param mixed $value
     * @return mixed
     */
    public function getIdAttribute($value = null)
    {
        // If we don't have a value for 'id', we will use the Mongo '_id' value.
        // This allows us to work with models in a more sql-like way.
        if (!$value && array_key_exists('_id', $this->modelAttributes)) {
            $value = $this->modelAttributes['_id'];
        }

        // Convert ObjectID to string.
        if ($value instanceof ObjectID) {
            return (string) $value;
        } elseif ($value instanceof Binary) {
            return (string) $value->getData();
        }

        return $value;
    }

    /**
     * @return mixed|string
     */
    public function getKeyName()
    {
        $keyName = parent::getKeyName();
        if (empty($keyName)) {
            $keyName = $this->getIdAttribute();
        }

        return $keyName;
    }

    /**
     * @inheritdoc
     */
    public function qualifyColumn(string $column): string
    {
        return $column;
    }

    /**
     * @param $value
     * @return \MongoDB\BSON\UTCDateTime
     */
    public function fromDateTime($value)
    {
        // If the value is already a UTCDateTime instance, we don't need to parse it.
        if ($value instanceof UTCDateTime) {
            return $value;
        }

        // Let Eloquent convert the value to a DateTime instance.
        if (!$value instanceof DateTime) {
            $value = $this->asDateTime($value);
        }

        return new UTCDateTime($value->format('Uv'));
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * @param mixed $value
     * @return \Carbon\Carbon|false
     */
    protected function asDateTime($value)
    {
        // If this value is already a Carbon instance, we shall just return it as is.
        // This prevents us having to re-instantiate a Carbon instance when we know
        // it already is one, which wouldn't be fulfilled by the DateTime check.
        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value);
        }

        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
        if ($value instanceof DateTimeInterface) {
            return Carbon::parse(
                $value->format('Y-m-d H:i:s.u'), $value->getTimezone()
            );
        }

        // Convert UTCDateTime instances.
        if ($value instanceof UTCDateTimeInterface) {
            $date = $value->toDateTime();

            $seconds = $date->format('U');
            $milliseconds = abs($date->format('v'));
            $timestampMs = sprintf('%d%03d', $seconds, $milliseconds);

            return Carbon::createFromTimestampMs($timestampMs);
        }

        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your date fields as they might be UNIX timestamps here.
        if (is_numeric($value)) {
            return Carbon::createFromTimestamp($value);
        }

        // If the value is in simply year, month, day format, we will instantiate the
        // Carbon instances from that format. Again, this provides for simple date
        // fields on the database, while still supporting Carbonized conversion.
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value)) {
            return Carbon::instance(Carbon::createFromFormat('Y-m-d', $value)->startOfDay());
        }

        $format = $this->getDateFormat();

        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
        try {
            $date = Carbon::createFromFormat($format, $value);
        } catch (InvalidArgumentException $e) {
            $date = false;
        }

        return $date ?: Carbon::parse($value);
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        return $this->modelDateFormat ?: 'Y-m-d H:i:s';
    }

    /**
     * @inheritdoc
     */
    public function freshTimestamp()
    {
        return new UTCDateTime(Carbon::now()->format('Uv'));
    }

    /**
     * @inheritdoc
     */
    public function getTable()
    {
        return $this->collection ?: parent::getTable();
    }

    /**
     * @inheritdoc
     */
    public function getModelAttribute($key)
    {
        // Dot notation support.
        if (Str::contains($key, '.') && Arr::has($this->modelAttributes, $key)) {
            return $this->getAttributeValue($key);
        }

        return parent::getModelAttribute($key);
    }

    /**
     * @inheritdoc
     * @throws \Anhoder\Mongodb\Swoft\MongoException
     */
    public function setModelAttribute($key, $value)
    {
        // Convert _id to ObjectID.
        if ($key == '_id' && is_string($value)) {
            $builder = $this->newBaseQueryBuilder();

            $value = $builder->convertKey($value);
        } // Support keys in dot notation.
        elseif (Str::contains($key, '.')) {
            if (in_array($key, $this->getDates()) && $value) {
                $value = $this->fromDateTime($value);
            }

            Arr::set($this->modelAttributes, $key, $value);

            return $this;
        }

        return parent::setModelAttribute($key, $value);
    }

    /**
     * Get the attributes that should be converted to dates.
     *
     * @return array
     */
    public function getDates()
    {
        if (! $this->usesTimestamps()) [];

        return [
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        // Because the original Eloquent never returns objects, we convert
        // MongoDB related objects to a string representation. This kind
        // of mimics the SQL behaviour so that dates are formatted
        // nicely when your models are converted to JSON.
        foreach ($attributes as $key => &$value) {
            if ($value instanceof ObjectID) {
                $value = (string) $value;
            } elseif ($value instanceof Binary) {
                $value = (string) $value->getData();
            }
        }

        // Convert dot-notation dates.
        foreach ($this->getDates() as $key) {
            if (Str::contains($key, '.') && Arr::has($attributes, $key)) {
                Arr::set($attributes, $key, (string) $this->asDateTime(Arr::get($attributes, $key)));
            }
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function originalIsEquivalent($key)
    {
        if (!array_key_exists($key, $this->modelOriginal)) {
            return false;
        }

        $attribute = Arr::get($this->modelAttributes, $key);
        $original = Arr::get($this->modelOriginal, $key);

        if ($attribute === $original) {
            return true;
        }

        if (null === $attribute) {
            return false;
        }

        if (in_array($key, $this->getDates(), true)) {
            $attribute = $attribute instanceof UTCDateTime ? $this->asDateTime($attribute) : $attribute;
            $original = $original instanceof UTCDateTime ? $this->asDateTime($original) : $original;

            return $attribute == $original;
        }

        return is_numeric($attribute) && is_numeric($original)
            && strcmp((string) $attribute, (string) $original) === 0;
    }

    /**
     * Remove one or more fields.
     * @param mixed $columns
     * @return int
     */
    public function drop($columns)
    {
        $columns = Arr::wrap($columns);

        // Unset attributes
        foreach ($columns as $column) {
            $this->__unset($column);
        }

        // Perform unset only on current document
        return $this->newQuery()->where($this->getKeyName(), $this->getKey())->unset($columns);
    }

    /**
     * @inheritdoc
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * @return \Swoft\Db\Connection\Connection
     * @throws \Anhoder\Mongodb\Swoft\MongoException
     */
    public function getConnection(): \Swoft\Db\Connection\Connection
    {
        throw new MongoException('请使用getMongoConnection获取连接');
    }

    /**
     * @return \Anhoder\Mongodb\Connection\Connection
     * @throws \Anhoder\Mongodb\Swoft\MongoException
     */
    public function getMongoConnection(): Connection
    {
        $pool = EntityRegister::getPool($this->getClassName());

        return Mongo::connection($pool);
    }

    /**
     * @param array $options
     * @return bool
     */
    public function saveOrFail(array $options = [])
    {
        return $this->save();
    }

    /**
     * @inheritdoc
     * @throws \Anhoder\Mongodb\Swoft\MongoException
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getMongoConnection();

        return new QueryBuilder($connection, $connection->getPostProcessor());
    }

    /**
     * Checks if column exists on a table.  As this is a document model, just return true.  This also
     * prevents calls to non-existent function Grammar::compileColumnListing()
     * @param string $key
     * @return bool
     */
    protected function isGuardableColumn($key)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function __call($method, $parameters)
    {
        // Unset method
        if ($method == 'unset') {
            return call_user_func_array([$this, 'drop'], $parameters);
        }

        return parent::__call($method, $parameters);
    }
}
