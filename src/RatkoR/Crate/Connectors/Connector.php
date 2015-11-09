<?php

namespace RatkoR\Crate\Connectors;

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
     * PDO can also work with multiple servers (eg: crate:localhost:4200,10.1.1.2:3200).
     *
     * If user provides comma delimited list of hosts, we create
     * multiple host servers.
     *
     * @param  array   $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        $host = isset($config['host']) ? $config['host'] : 'localhost';
        $port = isset($config['port']) ? (int)$config['port'] : 4200;
        $randomHosts = isset($config['randomHosts']) ? $config['randomHosts'] : true;

        if (strpos($host,",") !== false)
            return $this->getMultipleHostsDsn($host, $port, $randomHosts);

        return "crate:{$host}:{$port}";
    }

    /**
     * Returns DSN for connection to multiple hosts.
     *
     * If randomHosts config param is set to true (whic is the default),
     * we also randomize hosts so that connections are distributed among them.
     *
     * @param  string   $hostList Comma delimited list of crate hosts
     * @param  int   $defaultPort Default port as stated in config['port'] (or 4200)
     * @return string dsn string
     */
    protected function getMultipleHostsDsn($hostList, $defaultPort, $randomize)
    {
        $hosts = explode(',',$hostList);

        foreach ($hosts AS $key => $host) {
            $hosts[$key] = $this->addPortToHost($host, $defaultPort);
        }

        if ($randomize) {
            shuffle($hosts);
        }

        return 'crate:'.implode(',',$hosts);
    }

    /**
     * Returns host:port pair.
     * If port is not set, host is linked with default port.
     *
     * @param  string   $host IP or DNS name of crate server
     * @param  int   $defaultPort Default port as stated in config['port'] (or 4200)
     * @return string
     */
    protected function addPortToHost($host, $defaultPort)
    {
        if (strpos($host,':') !== false) {
            return $host;
        }

        return $host.':'.$defaultPort;
    }
}
