<?php

namespace RatkoR\Crate\Schema;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\Grammar;
use RatkoR\Crate\NotImplementedException;

class Blueprint extends \Illuminate\Database\Schema\Blueprint {

    /**
     * Array of all fulltext and primary indexes.
     *
     * Fulltext indexes are created as named index fields. This
     * array is used if a field has fulltext index attached to it
     * as (for example):
     *   $table->string('myString')->index('fulltext')
     * In this case we store field data in $indexes array so that
     * we later create named index fields as:
     *   INDEX ind_myString using fulltext (myString)
     *
     * Primary keys are defined the same way as in relational sql-s
     *   primary key (first_column, second_column)
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
     * @param  int     $precision
     * @return \Illuminate\Support\Fluent
     */
    public function dateTime($column, $precision = 0)
    {
        return $this->addColumn('timestamp', $column, compact('precision'));
    }

    /**
     * Not implemented yet....
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function binary($column)
    {
        throw new NotImplementedException('Binary fields are not yet implemented in this project...');
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
        return $this->addColumn('string', $column, compact('allowed'));
    }

    /**
     * Create a new time column on the table.
     *
     * @param  string  $column
     * @param  int  $precision
     * @return \Illuminate\Support\Fluent
     */
    public function time($column, $precision = 0)
    {
        return $this->addColumn('timestamp', $column, compact('precision'));
    }

    /**
     * geo_point field
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function geoPoint($column)
    {
        return $this->addColumn('geopoint', $column);
    }

    /**
     * geo_shape field
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function geoShape($column)
    {
        return $this->addColumn('geoshape', $column);
    }

    /**
     * ip field
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function ip($column)
    {
        return $this->addColumn('ip', $column);
    }

    /**
     * Returns true for fulltext indexes.
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
    public function index($columns = null, $options = array(), $algorithm = null)
    {
        /**
         * PLAIN and INDEX OFF are only with column names and are not
         * stored into indexes array, only fulltext is. they are generated
         * later in the process as named index columns.
         */
        if ($this->isFulltextIndex($options)) {
            $this->indexes[] = ['type'=>'fulltext','columns' => $columns, 'options' => $options];
        }

        return $this;
    }

    /**
     * Indicate that the given primary key should be dropped.
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropPrimary($index = null)
    {
        throw new NotImplementedException('Drop primary key not implemented in Crate.io');
    }

    /**
     * Indicate that the given unique key should be dropped.
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropUnique($index)
    {
        throw new NotImplementedException('Drop unique index not implemented in Crate.io');
    }

    /**
     * Indicate that the given foreign key should be dropped.
     *
     * @param  string  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropForeign($index)
    {
        throw new NotImplementedException('Drop foreign keys not implemented in Crate.io');
    }

    /**
     * Not used in Crate.io
     *
     * @param  string|array  $columns
     * @return Blueprint
     */
    public function dropIndex($columns = null)
    {
        throw new NotImplementedException('Drop index not implemented in Crate.io');
    }

    /**
     * Dropping columns is not supportedin Crate.io
     *
     * @param  string|array  $columns
     * @return \Illuminate\Support\Fluent
     */
    public function dropColumn($columns)
    {
        throw new NotImplementedException('Dropping columns is not implemented in Crate.io');
    }

    /**
     * Rename columns is not supportedin Crate.io
     *
     * @param  string|array  $columns
     * @return \Illuminate\Support\Fluent
     */
    public function renameColumn($from, $to)
    {
        throw new NotImplementedException('Dropping columns is not implemented in Crate.io');
    }

    /**
     * Specify primary key
     *
     * @param  string|array  $columns
     * @param  string  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Support\Fluent
     */
    public function primary($columns, $name = null, $algorithm = null)
    {
        $this->indexes[] = ['type'=>'primary','columns' => $columns];
    }

    /**
     * Not used in Crate.io
     *
     * @param  string|array  $columns
     * @param  string  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Support\Fluent
     */
    public function unique($columns, $name = null, $algorithm = null)
    {
        throw new NotImplementedException('Unique index not implemented in Crate.io');
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
        throw new NotImplementedException('Foreign keys are not implemented in Crate.io');
    }

    /**
     * Create a new array column on the table.
     *
     * Array is a special field type for crate.io. In migration
     * it is referenced as:
     *    $table->arrayField('name', 'options')
     * where name is field name and options are elements that
     * will be in this array.
     *
     * Examples:
     *   $table->arrayField('myField1', 'integer');
     *     For array of integers
     *
     *   $table->arrayField('myField2', 'object (dynamic) as (age integer, name string)');
     *     For array of objects
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
     * as:
     *    $table->objectField('name', 'options')
     * Options can be a string with all object properties.
     *
     * Example:
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
     * Auto incrementing is not supported in Crate
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function increments($column)
    {
        //throw new NotImplementedException('Auto increments are not supported in Crate.io');
    }

    /**
     * Auto incrementing is not supported in Crate
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function bigIncrements($column)
    {
        throw new NotImplementedException('Auto increments are not supported in Crate.io');
    }

    /**
     * Add the commands that are implied by the blueprint.
     *
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return void
     */
    protected function addImpliedCommands(Grammar $grammar)
    {

        if (count($this->getAddedColumns()) > 0 && ! $this->creating()) {
            array_unshift($this->commands, $this->createCommand('add'));
        }

        if (count($this->getChangedColumns()) > 0 && ! $this->creating()) {
            throw new NotImplementedException('Changing columns is not supported in Crate.io');
        }
    }

    /**
     * Do not allow creating of fields like _id, _score, _version
     */
    public function addColumn($type, $name, array $parameters = [])
    {
        if (in_array($name, ['_id', '_score', '_version'])) {
            throw new NotImplementedException("Naming column as '$name' is not supported.");
        }

        return parent::addColumn($type, $name, $parameters);
    }

    /**
     * Indicate that the blob table needs to be created.
     *
     * @return \Illuminate\Support\Fluent
     */
    public function createBlob()
    {
        return $this->addCommand('createBlob');
    }

    /**
     * Indicate that the blob table should be dropped.
     *
     * @return \Illuminate\Support\Fluent
     */
    public function dropBlob()
    {
        return $this->addCommand('dropBlob');
    }
}
