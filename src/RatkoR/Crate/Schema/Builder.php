<?php

namespace RatkoR\Crate\Schema;

use Closure;

class Builder extends \Illuminate\Database\Schema\Builder
{
    /**
     * Determine if the given table exists.
     *
     * @param  string  $table
     * @return bool
     */
    public function hasTable($table)
    {
        $sql = $this->grammar->compileTableExists();

        $database = $this->connection->getDatabaseName();

        $table = $this->connection->getTablePrefix().$table;

        return count($this->connection->select($sql, array($database, $table))) > 0;
    }

    /**
     * Get the column listing for a given table.
     *
     * @param  string  $table
     * @return array
     */
    public function getColumns($table)
    {
        $sql = $this->grammar->compileColumnExists();

        $database = $this->connection->getDatabaseName();

        $table = $this->connection->getTablePrefix() . $table;

        $results = $this->connection->select($sql, array($database, $table));

        return $this->connection->getPostProcessor()->processColumns($results);
    }


    /**
     * Create a new command set with a Closure.
     *
     * @param  string  $table
     * @param  \Closure|null  $callback
     * @return \Illuminate\Database\Schema\Blueprint
     */
    protected function createBlueprint($table, ?Closure $callback = null)
    {
        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $table, $callback);
        }

        return new Blueprint($table, $callback);
    }

    /**
     * Create a new blob table on the schema.
     *
     * @param  string    $table
     * @param  \Closure  $callback
     */
    public function createBlob($table, Closure $callback)
    {
        $blueprint = $this->createBlueprint($table);

        $blueprint->createBlob();

        $this->build($blueprint);
    }

    /**
     * Drop a blob table from the schema.
     *
     * @param  string  $table
     * @return \Illuminate\Database\Schema\Blueprint
     */
    public function dropBlob($table)
    {
        $blueprint = $this->createBlueprint($table);

        $blueprint->dropBlob();

        $this->build($blueprint);
    }
}
