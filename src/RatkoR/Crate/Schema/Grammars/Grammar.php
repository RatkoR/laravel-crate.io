<?php

namespace RatkoR\Crate\Schema\Grammars;

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
    protected $modifiers = ['Index', 'AlwaysAs'];

    /**
     * Get the SQL for index column modifier.
     *
     * "INDEX OFF" and "INDEX USING plain" are attached to fields.
     * Fulltext indexes are done as named index fields.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyIndex(Blueprint $blueprint, Fluent $column)
    {
        if ($column->index === null) {
            return;
        }

        if ($column->index === true || $column->index === 'plain') {
            return ' INDEX using plain';
        }

        if ($column->index === 'off') {
            return ' INDEX OFF';
        }
    }

    /**
     * Get the SQL for alwaysAs column modifier.
     *
     * "INDEX OFF" and "INDEX USING plain" are attached to fields.
     * Fulltext indexes are done as named index fields.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyAlwaysAs(Blueprint $blueprint, Fluent $column)
    {
        if ($column->alwaysAs === null) {
            return;
        }

        return " ALWAYS AS ({$column->alwaysAs})";
    }

    /**
     * Compile the query to determine the list of tables.
     *
     * @return string
     */
    public function compileTableExists()
    {
        return 'select * from information_schema.tables where table_schema = ? and table_name = ?';
    }

    /**
     * Compile the query to determine the list of columns.
     *
     * @return string
     */
    public function compileColumnExists()
    {
        return "select column_name from information_schema.columns where table_schema = ? and table_name = ?";
    }

    /**
     * Returns index name if it is set in $attributes or generates
     * a default name if no name is given.
     *
     * @param  array $attributes
     * @return string
     */
    protected function getIndexName(array $attributes)
    {
        if (isset($attributes['name'])) {
            return $attributes['name'];
        }

        if (!is_array($attributes['columns'])) {
            return 'ind_' . $attributes['columns'];
        }

        return 'ind_' . implode('_',$attributes['columns']);
    }

    /**
     * Returns fulltext analyzer language if it is given
     * in options.
     *
     * @param string $options
     * @return string or null
     */
    protected function getFulltextAnalyzer($options)
    {
        if (strpos($options,':') === false) {
            return null;
        }

        [$index, $analyzer] = explode(':', $options);

        return $analyzer;
    }

    /**
     * Returns SQL for primary key index field
     *
     * @param array $attributes
     * @return string
     */
    protected function createPrimaryIndexSql(array $attributes)
    {
        $fields = is_array($attributes['columns']) ?
                    implode(',',$attributes['columns']) :
                    $attributes['columns'];

        return "primary key ($fields)";
    }

    /**
     * Returns SQL for fulltext named index field.
     *
     * All fulltext indexes are created as named index fields:
     *    INDEX first_column_ft using fulltext (first_column)
     *
     * @param array $attributes
     * @return string
     */
    protected function createFulltextIndexSql(array $attributes)
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

    /**
     * Based on index type calls approprite index create functions
     */
    protected function createIndexSql(array $attributes)
    {
        $sql = '';

        switch ($attributes['type']) {
            case 'primary':
                $sql = $this->createPrimaryIndexSql($attributes);
                break;
            case 'fulltext':
                $sql = $this->createFulltextIndexSql($attributes);
                break;
        }

        return $sql;
    }

    /**
     * Returns index fields for all fulltext indexes.
     *
     * @param \RatkoR\Crate\Schema\Blueprint $blueprint
     * @return array
     */
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

        /**
         * Here we add fulltext indexes. Crate features three index types:
         * INDEX OFF, plain and fulltext. 'Off' and 'plain' are added by the
         * field definition, fulltext indexes are added here. They can span over
         * multiple fields and those cannot be added in the field definitions.
         *
         * We add all fulltext indexes here - multiple or single field ft indexes.
         */
        $indexes = implode(', ', $this->getIndexes($blueprint));
        $indexes = $indexes ? ", $indexes" : '';

        $sql = 'create table ' . $this->wrapTable($blueprint) .  " ($columns $indexes) ";

        return $this->compilePartitionedBy($sql, $blueprint);
    }

    /**
     * Compile PartionendBy .
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     *
     * @return string
     */
    public function compilePartitionedBy($sql, $blueprint)
    {
        $partitionedBy = $blueprint->getPartitionedBy();
        if ($partitionedBy !== []) {
            return $sql . ' PARTITIONED BY (' . implode(', ', $blueprint->getPartitionedBy()) . ')';
        }

        return $sql;
    }

    /**
     * Compile a create blob table command.
     * Blob table does not have any fields in definitions. Just table name.
     *
     * create blob table myblobs;
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @param  \Illuminate\Database\Connection  $connection
     * @return string
     */
    public function compileCreateBlob(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        $sql = 'create blob table ' . $this->wrapTable($blueprint) . "";

        return $sql;
    }

    /**
     * Compile a drop blob table command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @param  \Illuminate\Database\Connection  $connection
     * @return string
     */
    public function compileDropBlob(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        return 'drop blob table ' . $this->wrapTable($blueprint) . "";
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

        return 'alter table ' . $table . ' ' . implode(', ', $columns);
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
        return '';
    }

    /**
     * No unique indexes in crate.io
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
     * For crate.io, we handle indexes in getIndexes(), not here.
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
     * No foreign keys in crate.io
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
        return 'drop table ' . $this->wrapTable($blueprint);
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
        return 'drop table ' . $this->wrapTable($blueprint);
    }

    /**
     * Not used in Crate.io
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
     * Not used in Crate.io
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
     * Not used in Crate.io
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
     * Not used in Crate.io
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
     * Not used in Crate.io
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
        return 'object ' . $column->attributes;
    }

    /**
     * Create the column definition for a char type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeChar(Fluent $column)
    {
        return 'string';
    }

    /**
     * Create the column definition for a string type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeString(Fluent $column)
    {
        return 'string';
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
        return 'float';
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
        return '';
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

    /**
     * Create the column definition for a geopoint type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeGeoPoint(Fluent $column)
    {
        return 'geo_point';
    }

    /**
     * Create the column definition for a geo shape type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeGeoShape(Fluent $column)
    {
        return 'geo_shape';
    }

    /**
     * Create the column definition for a ip type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeIp(Fluent $column)
    {
        return 'ip';
    }

    /**
     * Create the column definition for a generated type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeGenerated(Fluent $column)
    {
        return 'generated';
    }
}
