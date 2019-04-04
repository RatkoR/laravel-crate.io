<?php

namespace RatkoR\Crate\Eloquent;

use Illuminate\Database\Eloquent\Model as BaseModel;
use RatkoR\Crate\Query\Builder as QueryBuilder;

class Model extends BaseModel
{
    /**
    * Crate.io does not have self incrementing fields,
    * just plain integers. You have to set primary keys on
    * your own.
    */
    public $incrementing = false;

    /**
    * created_at, updated_at and similar fields are
    * timespamp fields, not datetime.
    */
    public $dateFormat = 'c';

    /**
     * Get a new query builder instance for the connection.
     *
     * @return Builder
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        // Check the connection type
        if ($connection instanceof \RatkoR\Crate\Connection) {
            $grammar = $connection->getQueryGrammar();
            return new QueryBuilder($connection, $grammar, $connection->getPostProcessor());
        }

        return parent::newBaseQueryBuilder();
    }

    /**
     * Get the attributes that have been changed since last sync.
     *
     * This function tests array fields if their values have changed.
     * This is extra to the tests that laravel original code does.
     *
     * @return array
     */
    public function getDirty()
    {
        $dirty = parent::getDirty();

        foreach ($this->attributes as $key => $value) {
            if (is_array($value) && !array_key_exists($key, $dirty)) {
                if (json_encode($value) !== json_encode($this->original[$key]))
                    $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }
}
