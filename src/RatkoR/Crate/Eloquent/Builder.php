<?php

namespace RatkoR\Crate\Eloquent;

use Illuminate\Database\Eloquent\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
    /**
     * Add the "updated at" column to an array of values.
     *
     * Note: Crate (like postgres and sqlite) MUST not have table prefix in
     *       update query. It throws with:
     *       [SQLParseException: Column reference "my_table.update_at"
     *       has too many parts. A column must not have a schema or a table here.]
     *
     * Remove table prefix for updated_at field.
     *   Eq:
     *       update t1 set a = 1, t1.updated_at = 123456
     *   change to:
     *       update t1 set a = 1, updated_at = 123456
     *
     * See also: https://github.com/laravel/framework/pull/26031
     * 
     * @param  array  $values
     * @return array
     */
    protected function addUpdatedAtColumn(array $values)
    {
        $values = parent::addUpdatedAtColumn($values);
        $newValues = [];

        $updatedAtColumn = $this->model->getUpdatedAtColumn();

        foreach ($values as $field => $value) {
            $isUpdateAtField = (strpos($field, '.' . $updatedAtColumn) !== false);
            
            $isUpdateAtField ?
                $newValues[$updatedAtColumn] = $value :
                $newValues[$field] = $value;
        }

        return $newValues;
    }
}
