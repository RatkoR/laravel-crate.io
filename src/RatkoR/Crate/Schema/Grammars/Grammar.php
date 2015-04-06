<?php namespace RatkoR\Crate\Schema\Grammars;

use RatkoR\Crate\Schema\Blueprint;
use Illuminate\Support\Fluent;
use RatkoR\Crate\Connection;

class Grammar extends \Illuminate\Database\Schema\Grammars\Grammar
{
	/**
	 * The possible column modifiers.
	 *
	 * @var array
	 */
	protected $modifiers = array('Index');

	/**
	 * The possible column serials.
	 *
	 * @var array
	 */
	protected $serials = array('integer', 'long', 'short');

	/**
	 * Get the SQL for indexOff column modifier
	 *
	 * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string|null
	 */
	protected function modifyIndex(Blueprint $blueprint, Fluent $column)
	{
		if (is_null($column->index))
			return;

		if (($column->index === true) || ($column->index === 'plain'))
			return ' INDEX using plain';

		if ($column->index === 'off')
			return ' INDEX OFF';
	}

	/**
	 * Compile the query to determine the list of tables.
	 *
	 * @return string
	 */
	public function compileTableExists()
	{
		return 'select * from information_schema.tables where schema_name = ? and table_name = ?';
	}

	/**
	 * Compile the query to determine the list of columns.
	 *
	 * @return string
	 */
	public function compileColumnExists()
	{
		return "select column_name from information_schema.columns where schema_name = ? and table_name = ?";
	}

	protected function getIndexName($attributes)
	{
		if (isset($attributes['name']))
			return $attributes['name'];

		if (!is_array($attributes['columns']))
			return 'ind_'.$attributes['columns'];

		return 'ind_'.implode('_',$attributes['columns']);
	}

	protected function getFulltextAnalyzer($options)
	{
		if (strpos($options,':') === false)
			return false;

		list($index, $analyzer) = explode(':', $options);

		return $analyzer;
	}

	protected function createIndexSql($attributes)
	{
		$indexName = $this->getIndexName($attributes);
		$analyzer = $this->getFulltextAnalyzer($attributes['options']);

		$columns = is_array($attributes['columns']) ? 
						implode(',',$attributes['columns']) :
						$attributes['columns'];

		$sql = "INDEX {$indexName} using fulltext($columns)";

		if ($analyzer) {
			$sql .= " with (analyzer = '{$analyzer}')";
		}

		return $sql;
	}

	protected function getIndexes(Blueprint $blueprint)
	{
		$allIndexes = $blueprint->getIndexes();
		$compiled = [];

		foreach ($allIndexes as $index) {
			$compiled[] = $this->createIndexSql($index);
		}

		return $compiled;
	}

	/**
	 * Compile a create table command.
	 *
	 * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  \Illuminate\Support\Fluent  $command
	 * @param  \Illuminate\Database\Connection  $connection
	 * @return string
	 */
	public function compileCreate(Blueprint $blueprint, Fluent $command, Connection $connection)
	{
		$columns = implode(', ', $this->getColumns($blueprint));

		$indexes = implode(', ', $this->getIndexes($blueprint));
		$indexes = $indexes ? ", $indexes" : '';

		$sql = 'create table '.$this->wrapTable($blueprint)." ($columns $indexes)";

		return $sql;
	}


	/**
	 * Compile an add column command.
	 *
	 * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  \Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileAdd(Blueprint $blueprint, Fluent $command)
	{
		$table = $this->wrapTable($blueprint);

		$columns = $this->prefixArray('add', $this->getColumns($blueprint));

		return 'alter table '.$table.' '.implode(', ', $columns);
	}

	/**
	 * Compile a primary key command.
	 *
	 * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  \Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compilePrimary(Blueprint $blueprint, Fluent $command)
	{
		$command->name(null);

		return $this->compileKey($blueprint, $command, 'primary key');
	}

	/**
	 * Compile a unique key command.
	 *
	 * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  \Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileUnique(Blueprint $blueprint, Fluent $command)
	{
		return '';
	}

	/**
	 * Compile a plain index key command.
	 *
	 * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  \Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileIndex(Blueprint $blueprint, Fluent $command)
	{
		return '';
	}

	/**
	 * Compile an index creation command.
	 *
	 * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  \Illuminate\Support\Fluent  $command
	 * @param  string  $type
	 * @return string
	 */
	protected function compileKey(Blueprint $blueprint, Fluent $command, $type)
	{
		return '';
	}

	/**
	 * Compile a drop table command.
	 *
	 * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  \Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileDrop(Blueprint $blueprint, Fluent $command)
	{
		return 'drop table '.$this->wrapTable($blueprint);
	}

	/**
	 * Compile a drop table (if exists) command.
	 *
	 * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  \Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileDropIfExists(Blueprint $blueprint, Fluent $command)
	{
		return 'drop table '.$this->wrapTable($blueprint);
	}

	/**
	 * Compile a drop column command.
	 *
	 * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  \Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileDropColumn(Blueprint $blueprint, Fluent $command)
	{
		return '';
	}

	/**
	 * Compile a drop primary key command.
	 *
	 * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  \Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileDropPrimary(Blueprint $blueprint, Fluent $command)
	{
		return '';
	}

	/**
	 * Compile a drop unique key command.
	 *
	 * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  \Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileDropUnique(Blueprint $blueprint, Fluent $command)
	{
		return '';
	}

	/**
	 * Compile a drop index command.
	 *
	 * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  \Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileDropIndex(Blueprint $blueprint, Fluent $command)
	{
		return '';
	}

	/**
	 * Compile a drop foreign key command.
	 *
	 * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  \Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileDropForeign(Blueprint $blueprint, Fluent $command)
	{
		return '';
	}

	/**
	 * Compile a rename table command.
	 *
	 * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  \Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileRename(Blueprint $blueprint, Fluent $command)
	{
		return '';
	}

	/**
	 * Create the column definition for a array type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeArray(Fluent $column)
	{
		return "array ({$column->arrayElements})";
	}

	/**
	 * Create the column definition for a object type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeObject(Fluent $column)
	{
		return "object ".$column->attributes;
	}

	/**
	 * Create the column definition for a char type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeChar(Fluent $column)
	{
		return "string";
	}

	/**
	 * Create the column definition for a string type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeString(Fluent $column)
	{
		return "string";
	}

	/**
	 * Create the column definition for a text type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeText(Fluent $column)
	{
		return 'string';
	}

	/**
	 * Create the column definition for a medium text type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeMediumText(Fluent $column)
	{
		return 'string';
	}

	/**
	 * Create the column definition for a long text type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeLongText(Fluent $column)
	{
		return 'string';
	}

	/**
	 * Create the column definition for a big integer type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeBigInteger(Fluent $column)
	{
		return 'long';
	}

	/**
	 * Create the column definition for a integer type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeInteger(Fluent $column)
	{
		return 'integer';
	}

	/**
	 * Create the column definition for a medium integer type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeMediumInteger(Fluent $column)
	{
		return 'integer';
	}

	/**
	 * Create the column definition for a tiny integer type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeTinyInteger(Fluent $column)
	{
		return 'short';
	}

	/**
	 * Create the column definition for a small integer type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeSmallInteger(Fluent $column)
	{
		return 'integer';
	}

	/**
	 * Create the column definition for a float type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeFloat(Fluent $column)
	{
		return 'float';
	}

	/**
	 * Create the column definition for a double type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeDouble(Fluent $column)
	{
		return 'double';
	}

	/**
	 * Create the column definition for a decimal type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeDecimal(Fluent $column)
	{
		return "float";
	}

	/**
	 * Create the column definition for a boolean type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeBoolean(Fluent $column)
	{
		return 'byte';
	}

	/**
	 * Create the column definition for an enum type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeEnum(Fluent $column)
	{
		return "";
	}

	/**
	 * Create the column definition for a json type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeJson(Fluent $column)
	{
		return 'string';
	}

	/**
	 * Create the column definition for a date type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeDate(Fluent $column)
	{
		return '';
	}

	/**
	 * Create the column definition for a date-time type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeDateTime(Fluent $column)
	{
		return '';
	}

	/**
	 * Create the column definition for a time type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeTime(Fluent $column)
	{
		return '';
	}

	/**
	 * Create the column definition for a timestamp type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeTimestamp(Fluent $column)
	{
		return 'timestamp';
	}

	/**
	 * Create the column definition for a binary type.
	 *
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeBinary(Fluent $column)
	{
		return '';
	}
}