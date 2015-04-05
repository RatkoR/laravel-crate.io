<?php namespace RatkoR\Crate;

use Illuminate\Support\ServiceProvider;

class CrateServiceProvider extends ServiceProvider
{
	/**
	 * Register the service provider
     *
     * @return void
   	 */
	public function register()
	{
		$this->app->resolving('db', function($db)
		{
			$db->extend('crate', function($config)
			{
				return new Connection($config);
			});
		});
	}
}