<?php namespace RatkoR\Crate\Schema;

use RatkoR\Crate\Schema\Blueprint;

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
	 * Create a new command set with a Closure.
	 *
	 * @param  string  $table
	 * @param  \Closure|null  $callback
	 * @return \Illuminate\Database\Schema\Blueprint
	 */
	protected function createBlueprint($table, Closure $callback = null)
	{
		if (isset($this->resolver))
		{
			return call_user_func($this->resolver, $table, $callback);
		}

		return new Blueprint($table, $callback);
	}
}