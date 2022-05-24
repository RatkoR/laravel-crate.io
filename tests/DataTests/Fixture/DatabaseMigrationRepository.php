<?php

namespace DataTests\Fixture;

class DatabaseMigrationRepository extends \Illuminate\Database\Migrations\DatabaseMigrationRepository
{
    public function createRepository()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        $schema->create($this->table, function ($table) {
            // The migrations table is responsible for keeping track of which of the
            // migrations have actually run for the application. We'll create the
            // table to hold the migration file's path as well as the batch ID.
            $table->integer('id');
            $table->string('migration');
            $table->integer('batch');
        });
    }
}
