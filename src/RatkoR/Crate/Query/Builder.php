<?php

namespace RatkoR\Crate\Query;

use Illuminate\Database\Query\Builder as BaseBuilder;
use RatkoR\Crate\NotImplementedException;
use Closure;

class Builder extends BaseBuilder
{
    /**
     * Run a truncate statement on the table.
     *
     * @return void
     */
    public function truncate()
    {
        foreach ($this->grammar->compileTruncate($this) as $sql => $bindings)
        {
            $this->connection->statement($sql, $bindings);
        }
    }

    /**
     * Joins are not supported in Crate.io
     *
     * @param  string  $table
     * @param  string  $one
     * @param  string  $operator
     * @param  string  $two
     * @param  string  $type
     * @param  bool    $where
     * @return $this
     */
    public function join($table, $one, $operator = null, $two = null, $type = 'inner', $where = false)
    {
        throw new NotImplementedException('Joins are not implemented in Crate');
    }

    /**
     * unions are not supported in Crate.io
     *
     * @param  \Illuminate\Database\Query\Builder|\Closure  $query
     * @param  bool  $all
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function union($query, $all = false)
    {
        throw new NotImplementedException('Joins are not implemented in Crate');
    }

    /**
     * Not available in crate.io
     *
     * @param  string  $column
     * @param  array   $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function whereBetween($column, array $values, $boolean = 'and', $not = false)
    {
        throw new NotImplementedException('whereBetween is not implemented in Crate');
    }

    /**
     * Not available in Crate.io
     *
     * @param  \Closure $callback
     * @param  string   $boolean
     * @param  bool     $not
     * @return $this
     */
    public function whereExists(Closure $callback, $boolean = 'and', $not = false)
    {
        throw new NotImplementedException('whereExists is not implemented in Crate');
    }

    /**
     * Not implemented in Crate.io
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param  string  $as
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function selectSub($query, $as)
    {
        throw new NotImplementedException('Subselects are not implemented in Crate');
    }

    /**
     * Update a record in the database.
     *
     * @param  array  $values
     * @return int
     */
    public function update(array $values)
    {

        $bindings = array_values(array_merge($values, $this->getBindings()));

        $sql = $this->grammar->compileUpdate($this, $values);

        return $this->connection->update($sql, $this->cleanBindings($bindings));
    }
}
