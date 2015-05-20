<?php namespace LexicalTests;

use LexicalTests\TestCase;
use RatkoR\Crate\Connectors\Connector;

class ConnectorTest extends TestCase
{
	/** @test */
	function it_returns_dns_from_given_values_for_single_host()
	{
		$connector = new Connector();
		$dsn = $this->invokeMethod($connector, 'getDsn', [['host'=>'127.0.0.1','port'=>4000]]);

		$this->assertEquals('crate:127.0.0.1:4000', $dsn);
	}

	/** @test */
	function it_returns_default_dns_values_for_multiple_hosts_and_default_randomize()
	{
		$connector = new Connector();
		$dsn = $this->invokeMethod($connector, 'getDsn', [['host'=>'127.0.0.1,10.0.0.1','port'=>4000]]);

		$this->assertContains('crate:', $dsn);
		$this->assertContains('127.0.0.1:4000', $dsn);
		$this->assertContains('10.0.0.1:4000', $dsn);
	}

	/** @test */
	function it_returns_default_dns_values_for_multiple_hosts_with_port_specified_and_default_randomize()
	{
		$connector = new Connector();
		$dsn = $this->invokeMethod($connector, 'getDsn', [['host'=>'127.0.0.1:4200,10.0.0.1:4300','port'=>4000]]);

		$this->assertContains('crate:', $dsn);
		$this->assertContains('127.0.0.1:4200', $dsn);
		$this->assertContains('10.0.0.1:4300', $dsn);
	}

	/** @test */
	function it_returns_default_dns_values_for_multiple_hosts_with_port_specified_and_no_randomization()
	{
		$connector = new Connector();
		$dsn = $this->invokeMethod($connector, 'getDsn', [['host'=>'127.0.0.1:4200,10.0.0.1:4300','port'=>4000, 'randomHosts'=>false]]);

		$this->assertEquals('crate:127.0.0.1:4200,10.0.0.1:4300', $dsn);
	}

	/** @test */
	function it_returns_default_dns_values_for_multiple_hosts_with_port_specified_and_randomization_set()
	{
		$connector = new Connector();
		$dsn = $this->invokeMethod($connector, 'getDsn', [['host'=>'127.0.0.1:4200,10.0.0.1:4300','port'=>4000, 'randomHosts'=>true]]);

		$this->assertContains('crate:', $dsn);
		$this->assertContains('127.0.0.1:4200', $dsn);
		$this->assertContains('10.0.0.1:4300', $dsn);
	}

	/** @test */
	function it_returns_default_dns_values_for_multiple_hosts_with_one_host_port_specified_and_no_randomization()
	{
		$connector = new Connector();
		$dsn = $this->invokeMethod($connector, 'getDsn', [['host'=>'127.0.0.1,10.0.0.1:4300','port'=>4000, 'randomHosts'=>false]]);

		$this->assertEquals('crate:127.0.0.1:4000,10.0.0.1:4300', $dsn);
	}

	/** @test */
	function it_returns_default_dns_values_from_no_attributes()
	{
		$connector = new Connector();
		$dsn = $this->invokeMethod($connector, 'getDsn', [[]]);

		$this->assertEquals('crate:localhost:4200', $dsn);
	}

	/** @test */
	function it_creates_pdo_connection_for_single_host()
	{
		$connector = new Connector();
		$connection = $connector->connect(['host'=>'127.0.0.1','port'=>4200]);

		$this->assertInstanceOf('Crate\PDO\PDO', $connection);
	}
	
	/** @test */
	function it_creates_pdo_connection_for_multiple_hosts()
	{
		$connector = new Connector();
		$connection = $connector->connect(['host'=>'127.0.0.1,10.0.0.1','port'=>4200]);

		$this->assertInstanceOf('Crate\PDO\PDO', $connection);
	}
	
}