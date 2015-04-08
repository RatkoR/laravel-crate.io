<?php namespace RatkoR\Crate\Connectors;

use Illuminate\Database\Connectors\Connector AS BaseConnector;
use Illuminate\Database\Connectors\ConnectorInterface;
use Crate\PDO\PDO;

class Connector extends BaseConnector implements ConnectorInterface {

	/**
	 * Crate.io has only a subset of PDO options available.
	 * See: https://github.com/crate/crate-pdo
	 */
	protected $options = array(
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	);

	/**
	 * Create a new PDO connection.
	 * 
	 * Username and password are not used.
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
	 * Crate dsn looks like:
	 *   crate:<HOSTNAME_OR_IP>:<PORT> (eg: crate:localhost:4200)
	 *
	 * @param  array   $config
	 * @return string
	 */
	protected function getDsn(array $config)
	{
		$host = isset($config['host']) ? $config['host'] : 'localhost';
		$port = isset($config['port']) ? $config['port'] : 4200;

		return "crate:{$host}:{$port}";
	}

}
