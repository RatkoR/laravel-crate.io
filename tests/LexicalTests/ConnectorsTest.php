<?php namespace LexicalTests;

use LexicalTests\TestCase;
use RatkoR\Crate\Connectors\Connector;

class ConnectorTest extends TestCase
{
	/** @test */
	function it_returns_dns_from_given_values()
	{
		$connector = new Connector();
		$dsn = $this->invokeMethod($connector, 'getDsn', [['host'=>'127.0.0.1','port'=>4000]]);

		$this->assertEquals('crate:127.0.0.1:4000', $dsn);
	}

	/** @test */
	function it_returns_default_dns_values_from_no_attributes()
	{
		$connector = new Connector();
		$dsn = $this->invokeMethod($connector, 'getDsn', [[]]);

		$this->assertEquals('crate:localhost:4200', $dsn);
	}

	/** @test */
	function it_creates_pdo_connection()
	{
		$connector = new Connector();
		$connection = $connector->connect(['host'=>'127.0.0.1','port'=>4200]);

		$this->assertInstanceOf('Crate\PDO\PDO', $connection);
	}
}