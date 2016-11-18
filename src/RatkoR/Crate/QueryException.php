<?php

namespace RatkoR\Crate;

use Illuminate\Database\QueryException as BaseQueryException;
use Illuminate\Support\Str;

class QueryException extends BaseQueryException
{

    /**
     * Format the SQL error message.
     * 
     * Overriden to support extended types like arrays and objects.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @param  \Exception $previous
     * @return string
     */
    protected function formatMessage($sql, $bindings, $previous)
    {
        $preparedBindings = $this->prepareBindings($bindings);

        return $previous->getMessage().' (SQL: '.Str::replaceArray('?', $preparedBindings, $sql).')';
    }

    /**
     * Non scalar fields are json_encoded to string values.
     */
    protected function prepareBindings($bindings)
    {
        foreach ($bindings as $key => $binding) {
            $bindings[$key] = is_scalar($binding) ?
                                $binding : json_encode($binding);
        }

        return $bindings;
    }
}
