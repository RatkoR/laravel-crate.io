<?php namespace RatkoR\Crate\Schema;

use Closure;
use Illuminate\Database\Connection;

class Blueprint extends \Illuminate\Database\Schema\Blueprint {

	protected $indexes = [];

	public function getIndexes()
	{
		return $this->indexes;
	}

	/**
	 * Create a new date column on the table.
	 *
	 * @param  string  $column
	 * @return \Illuminate\Support\Fluent
	 */
	public function date($column)
	{
		return $this->addColumn('timestamp', $column);
	}

	/**
	 * Create a new date-time column on the table.
	 *
	 * @param  string  $column
	 * @return \Illuminate\Support\Fluent
	 */
	public function dateTime($column)
	{
		return $this->addColumn('timestamp', $column);
	}

	/**
	 * Create a new binary column on the table.
	 *
	 * @param  string  $column
	 * @return \Illuminate\Support\Fluent
	 */
	public function binary($column)
	{
		return $this;
	}

	/**
	 * Create a new enum column on the table.
	 *
	 * @param  string  $column
	 * @param  array   $allowed
	 * @return \Illuminate\Support\Fluent
	 */
	public function enum($column, array $allowed)
	{
		return $this->addColumn('string', $column);
	}

	/**
	 * Create a new time column on the table.
	 *
	 * @param  string  $column
	 * @return \Illuminate\Support\Fluent
	 */
	public function time($column)
	{
		return $this->addColumn('timestamp', $column);
	}

	/**
	 * Returns true if options value starts with 'fulltext'
	 */
	protected function isFulltextIndex($options)
	{
		if (!$options)
			return false; // default as plain index

		return stripos($options,'fulltext') !== false;
	}

	protected function addIndex($columns, $options)
	{
		/**
		 * PLAIN and INDEX OFF are only with column names and are not
		 * stored into indexes array - we will not be adding them as 
		 * names index columns.
		 * Fulltext indexes are generated as named index columns.
		 */
		if ($this->isFulltextIndex($options)) {
			$this->indexes[] = ['columns' => $columns, 'options' => $options];
		}
	}

	/**
	 * Specify an index for the collection.
	 *
	 * @param  string|array  $columns
	 * @param  array         $options
	 * @return Blueprint
	 */
	public function index($columns = null, $options = array())
	{
		return $this->addIndex($columns, $options);
	}

	/**
	 * Indicate that the given index should be dropped.
	 *
	 * @param  string|array  $columns
	 * @return Blueprint
	 */
	public function dropIndex($columns = null)
	{
		return $this;
	}

	/**
	 * Specify the primary key(s) for the table.
	 *
	 * @param  string|array  $columns
	 * @param  string  $name
	 * @return \Illuminate\Support\Fluent
	 */
	public function primary($columns, $name = null)
	{
		return $this;
	}

	/**
	 * Specify a unique index for the table.
	 *
	 * @param  string|array  $columns
	 * @param  string  $name
	 * @return \Illuminate\Support\Fluent
	 */
	public function unique($columns, $name = null)
	{
		return $this;
	}

	/**
	 * Specify a foreign key for the table.
	 *
	 * @param  string|array  $columns
	 * @param  string  $name
	 * @return \Illuminate\Support\Fluent
	 */
	public function foreign($columns, $name = null)
	{
		return $this;
	}

	/**
	 * Create a new array column on the table.
	 *
	 * @param  string  $column
	 * @param  int  $length
	 * @return \Illuminate\Support\Fluent
	 */
	public function arrayField($column, $arrayElements = 'string')
	{
		return $this->addColumn('array', $column, compact('arrayElements'));
	}

	/**
	 * Create a new object column on the table.
	 *
	 * @param  string  $column
	 * @param  int  $length
	 * @return \Illuminate\Support\Fluent
	 */
	public function objectField($column, $attributes = '')
	{
		return $this->addColumn('object', $column, compact('attributes'));
	}

	/**
	 * Allows the use of unsupported schema methods.
	 *
	 * @return Blueprint
	 */
	public function __call($method, $args)
	{
		// Dummy.
		return $this;
	}

}
