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
    protected function getDateFormat()
    {
        return 'U';
    }

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
}
