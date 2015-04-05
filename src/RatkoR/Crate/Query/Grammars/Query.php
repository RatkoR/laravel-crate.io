<?php namespace RatkoR\Crate\Query\Grammars;

use Illuminate\Database\Query\Builder;

class Grammar extends \Illuminate\Database\Query\Grammars\Grammar {

	/**
	 * All of the available clause operators.
	 *
	 * @var array
	 */
	protected $operators = array(
		'=', '<', '>', '<=', '>=', '<>', '!=',
		'like', 'not like',
	);

	/**
	 * Compile a truncate table statement into SQL.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @return array
	 */
	public function compileTruncate(Builder $query)
	{
		$table = $this->wrapTable($query->from);
		return trim("delete from $table");
	}
}
