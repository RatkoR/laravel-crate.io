<?php namespace RatkoR\Crate\Schema;

use Closure;
use Illuminate\Database\Connection;

class Blueprint extends \Illuminate\Database\Schema\Blueprint {

	/**
	 * Array of all fulltext indexes.
	 * 
	 * Fulltext indexes are created as named index fields. used if a field
	 * has fulltext index attached to it as (for example):
	 *   $table->string('myString')->index('fulltext')
	 * In this case we store it in $indexes array so that we later create
	 * named index fields as:
	 *   INDEX ind_myString using fulltext (myString)
	 */
	protected $indexes = [];

	/**
	 * Returns all fulltext indexes.
	 * 
	 * @return array
	 */
	public function getIndexes()
	{
		return $this->indexes;
	}

	/**
	 * In crate.io we do timestamps.
	 *
	 * @param  string  $column
	 * @return \Illuminate\Support\Fluent
	 */
	public function date($column)
	{
		return $this->addColumn('timestamp', $column);
	}

	/**
	 * In crate.io we do timestamps.
	 *
	 * @param  string  $column
	 * @return \Illuminate\Support\Fluent
	 */
	public function dateTime($column)
	{
		return $this->addColumn('timestamp', $column);
	}

	/**
	 * Not implemented yet....
	 *
	 * @param  string  $column
	 * @return \Illuminate\Support\Fluent
	 */
	public function binary($column)
	{
		return $this;
	}

	/**
	 * Enum -> string in Crate.io
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
	 * We do timestamps for all date fields
	 *
	 * @param  string  $column
	 * @return \Illuminate\Support\Fluent
	 */
	public function time($column)
	{
		return $this->addColumn('timestamp', $column);
	}

	/**
	 * Returns true for fulltext indexes - if options value
	 * starts with string 'fulltext'.
	 * 
	 * This is true if index is created as:
	 *   $table->string('myString')->index('fulltext') or
	 *   $table->string('myString')->index('fulltext:english')
	 */
	protected function isFulltextIndex($options)
	{
		if (!$options)
			return false; // default as plain index

		return stripos($options,'fulltext') !== false;
	}

	/**
	 * Specify an index
	 *
	 * @param  string|array  $columns
	 * @param  array         $options
	 * @return Blueprint
	 */
	public function index($columns = null, $options = array())
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

		return $this->addIndex($columns, $options);
	}

	/**
	 * Not used in Crate.io
	 *
	 * @param  string|array  $columns
	 * @return Blueprint
	 */
	public function dropIndex($columns = null)
	{
		return $this;
	}

	/**
	 * Not used in Crate.io
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
	 * Not used in Crate.io
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
	 * Not used in Crate.io
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
	 * Array is a special field type for crate.io. In migration
	 * it is referenced as $table->arrayField('name', 'options'),
	 * where name is field name and options are elements that
	 * will be in this array. 
	 * 
	 * Examples:
	 *   $table->arrayField('myField1', 'integer');
	 *   For array of integers
	 * 
	 *   $table->arrayField('myField2', 'object (dynamic) as (age integer, name string)');
	 *   For array of objects
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
	 * Object is a field specific to Crate.io. Can be defined
	 * as $table->objectField('name', 'options'). Options can be a string
	 * with all object properties. Like:
	 *   $table->objectField('f_object', '(dynamic) as (a integer)');
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
