<?php

namespace RatkoR\Crate\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;

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
        return array('delete from '.$this->wrapTable($query->from) => array());
    }

    public function prepareBindingsForUpdate(array $bindings, array $values)
    {
        $cleanBindings = Arr::except($bindings, ['select', 'join']);

        return array_values(
            array_merge($bindings['join'], $values, Arr::flatten($cleanBindings))
        );
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @TODO !!!!
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapValue($value)
    {
        if ($value === '*') return $value;

        return $value;
    }
}
