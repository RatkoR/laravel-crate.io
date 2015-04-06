<?php namespace RatkoR\Crate\Connectors;

use Illuminate\Database\Connectors\Connector AS BaseConnector;
use Illuminate\Database\Connectors\ConnectorInterface;
use Crate\PDO\PDO;

class Connector extends BaseConnector implements ConnectorInterface {

	protected $options = array(
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	);

	/**
	 * Create a new PDO connection.
	 *
	 * @param  string  $dsn
	 * @param  array   $config
	 * @param  array   $options
	 * @return \PDO
	 */
	public function createConnection($dsn, array $config, array $options)
	{
		return new PDO($dsn, null, null, $options);
	}

	/**
	 * Establish a database connection.
	 *
	 * @param  array  $config
	 * @return \PDO
	 */
	public function connect(array $config)
	{
		$dsn = $this->getDsn($config);

		$options = $this->getOptions($config);
		$connection = $this->createConnection($dsn, $config, $options);

		return $connection;
	}

	/**
	 * Create a DSN string from a configuration.
	 *
	 * @param  array   $config
	 * @return string
	 */
	protected function getDsn(array $config)
	{
		extract($config);
		return "crate:{$host}:{$port}";
	}

}
