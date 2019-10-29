<?php

namespace DataTests;

use Illuminate\Support\Facades\DB;

class TestCase extends \Orchestra\Testbench\TestCase {

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            'RatkoR\Crate\CrateServiceProvider',
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  Illuminate\Foundation\Application    $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $config = require 'Config/database.php';

        $app['config']->set('database.default', 'crate');
        $app['config']->set('database.connections.crate', $config['connections']['crate']);
        $app['config']->set('database.connections.crate', $config['connections']['crate']);
        $app['config']->set('database.fetch', \PDO::FETCH_ASSOC);
        $app['config']->set('database.migrations', $config['migrations']);

        $app['config']->set('cache.driver', 'array');
    }

    /**
     *
     * @return void
     */
    public function forceCrateConsistency()
    {
        DB::connection('crate')->statement('REFRESH TABLE doc.t_users;');
    }
}
