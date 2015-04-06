<?php namespace RatkoR\Crate;

use RatkoR\Crate\Schema\Builder;
use Crate\DBAL\Driver\PDOCrate\Driver as DoctrineDriver;
use RatkoR\Crate\Query\Grammars\Grammar as QueryGrammar;
use RatkoR\Crate\Schema\Grammars\Grammar as SchemaGrammar;

class Connection extends \Illuminate\Database\Connection
{
	/**
	 * Get a schema builder instance for the connection.
	 *
	 * @return \Illuminate\Database\Schema\MySqlBuilder
	 */
	public function getSchemaBuilder()
	{
		if (is_null($this->schemaGrammar)) { $this->useDefaultSchemaGrammar(); }

		return new Builder($this);
	}

	/**
	 * Get the default query grammar instance.
	 *
	 * @return \Illuminate\Database\Query\Grammars\MySqlGrammar
	 */
	protected function getDefaultQueryGrammar()
	{
		return $this->withTablePrefix(new QueryGrammar);
	}

	/**
	 * Get the default schema grammar instance.
	 *
	 * @return \Illuminate\Database\Schema\Grammars\MySqlGrammar
	 */
	protected function getDefaultSchemaGrammar()
	{
		return $this->withTablePrefix(new SchemaGrammar);
	}

	/**
	 * Get the default post processor instance.
	 *
	 * @return \Illuminate\Database\Query\Processors\Processor
	 */
	protected function getDefaultPostProcessor()
	{
		return new Query\Processors\Processor;
	}

	/**
	 * Get the Doctrine DBAL driver.
	 *
	 * @return \Doctrine\DBAL\Driver\PDOMySql\Driver
	 */
	protected function getDoctrineDriver()
	{
		return new DoctrineDriver;
	}

}