<?php namespace RatkoR\Crate\Query;

use Illuminate\Database\Query\Builder as BaseBuilder;
use RatkoR\Crate\NotImplementedException;

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

}
