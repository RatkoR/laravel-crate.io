<?php

namespace RatkoR\Crate;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use RatkoR\Crate\Connectors\Connector;

class CrateServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        Model::setConnectionResolver($this->app['db']);

        Model::setEventDispatcher($this->app['events']);
    }

    /**
     * Register the service provider
     *
     * @return void
        */
    public function register()
    {
        $this->app->resolving('db', function($db)
        {
            $db->extend('crate', function($config, $name)
            {
                $config['name'] = $name;

                $connector = new Connector();
                $connection = $connector->connect($config);

                $database = $config['database'] ?: 'doc';
                $prefix = isset($config['prefix']) ? $config['prefix'] : '';

                return new Connection($connection, $database, $prefix, $config);
            });
        });
    }
}
