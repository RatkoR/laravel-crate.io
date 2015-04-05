<?php namespace RatkoR\Crate\Processors;

class Processor extends \Illuminate\Database\Query\Processors\Processor
{
	/**
	 * Process the results of a column listing query.
	 *
	 * @param  array  $results
	 * @return array
	 */
	public function processColumnListing($results)
	{
		$mapping = function($r)
		{
			$r = (object) $r;

			return $r->column_name;
		};

		return array_map($mapping, $results);
	}

	/**
	 * Process an "insert get ID" query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  string  $sql
	 * @param  array   $values
	 * @param  string  $sequence
	 * @return int
	 */
	public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
	{
		return null;
	}
}