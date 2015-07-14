<?php namespace LexicalTests;

use LexicalTests\TestCase;
use Illuminate\Support\Fluent;
use RatkoR\Crate\Schema\Blueprint;
use RatkoR\Crate\Connectors\Connector;
use RatkoR\Crate\Schema\Grammars\Grammar;
use RatkoR\Crate\Connection;
use RatkoR\Crate\NotImplementedException;

class BlueprintTest extends TestCase {

	private $connection = null;

	private function setConnection()
	{
		$connector = new Connector();
		$connection = $connector->connect(['host'=>'localhost','20000']);

		return new Connection($connection, 'doc');
	}

	protected function setUp()
	{
		$this->connection = $this->setConnection();
		$this->grammar = new Grammar();
	}

	protected function tearDown()
	{

	}

	/** @test */
	function it_adds_plain_index_data_to_column_if_index_is_not_specified()
	{
		$blueprint = new Blueprint('testtable', function($table) {
			$table->string('f_string')->index();
		});

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertContains("\"f_string\" string INDEX using plain", $def[0]);
	}

	/** @test */
	function it_adds_plain_index_data_to_column()
	{
		$blueprint = new Blueprint('testtable', function($table) {
			$table->string('f_string')->index('plain');
		});

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertContains("\"f_string\" string INDEX using plain", $def[0]);
	}

	/** @test */
	function it_adds_off_index_data_to_column()
	{
		$blueprint = new Blueprint('testtable', function($table) {
			$table->string('f_string')->index('off');
		});

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertContains("\"f_string\" string INDEX OFF", $def[0]);
	}

	/** @test */
	function it_does_not_add_fulltext_index_data_to_column()
	{
		$blueprint = new Blueprint('testtable', function($table) {
			$table->string('f_string')->index('fulltext');
		});

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertNotContains("fulltext", $def[0]);
	}

	/** @test */
	function it_adds_fulltext_index_wo_analyzer_data_to_table()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->create();	
		$blueprint->string('f_string');
		$blueprint->index('f_string','fulltext');

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertContains(", INDEX ind_f_string using fulltext(f_string)", $def[0]);
		$this->assertNotContains("analyzer", $def[0]);
	}

	/** @test */
	function it_adds_double_fulltext_index_wo_analyzer_data_to_table()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->create();	
		$blueprint->string('f_string_1');
		$blueprint->string('f_string_2');
		$blueprint->index(['f_string_1','f_string_2'],'fulltext');

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertContains(", INDEX ind_f_string_1_f_string_2 using fulltext(f_string_1,f_string_2)", $def[0]);
		$this->assertNotContains("analyzer", $def[0]);
	}

	/** @test */
	function it_adds_fulltext_index_with_analyzer_data_to_table()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->create();	
		$blueprint->string('f_string');
		$blueprint->index('f_string','fulltext:english');

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertContains(", INDEX ind_f_string using fulltext(f_string) with (analyzer = 'english')", $def[0]);
	}

	/** @test */
	function it_does_not_add_plain_index_as_named_index_to_table()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->create();	
		$blueprint->string('f_string');
		$blueprint->index('f_string');

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertNotContains("PLAIN", $def[0]);
	}

	/** @test */
	function it_does_not_add_off_index_as_named_index_to_table()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->create();	
		$blueprint->string('f_string');
		$blueprint->index('f_string','off');

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertNotContains("OFF", $def[0]);
	}

	/**
	 * @test
	 * @expectedException RatkoR\Crate\NotImplementedException
	 */
	function it_throws_exception_for_drop_index()
	{
		$blueprint = new Blueprint('testtable');
		$return = $blueprint->dropIndex();	
	}

	/**
	 * @test
	 * @expectedException RatkoR\Crate\NotImplementedException
	 */
	function it_throws_exception_for_binary_field()
	{
		$blueprint = new Blueprint('testtable');
		$blueprint->binary('f_binary');
	}

	/**
	 * @test
	 * @expectedException RatkoR\Crate\NotImplementedException
	 */
	function it_throws_exception_for_dropPrimary()
	{
		$blueprint = new Blueprint('testtable');
		$blueprint->dropPrimary();
	}

	/**
	 * @test
	 * @expectedException RatkoR\Crate\NotImplementedException
	 */
	function it_throws_exception_for_dropUnique()
	{
		$blueprint = new Blueprint('testtable');
		$blueprint->dropUnique('index1');
	}

	/**
	 * @test
	 * @expectedException RatkoR\Crate\NotImplementedException
	 */
	function it_throws_exception_for_dropIndex()
	{
		$blueprint = new Blueprint('testtable');
		$blueprint->dropIndex('index1');
	}

	/**
	 * @test
	 * @expectedException RatkoR\Crate\NotImplementedException
	 */
	function it_throws_exception_for_dropForeign()
	{
		$blueprint = new Blueprint('testtable');
		$blueprint->dropForeign('index1');
	}

	/**
	 * @test
	 * @expectedException RatkoR\Crate\NotImplementedException
	 */
	function it_throws_exception_for_foreign_index()
	{
		$blueprint = new Blueprint('testtable');
		$blueprint->foreign('index1');
	}

	/**
	 * @test
	 * @expectedException RatkoR\Crate\NotImplementedException
	 */
	function it_throws_exception_for_unique_index()
	{
		$blueprint = new Blueprint('testtable');
		$blueprint->unique('index1');
	}

	/**
	 * @test
	 */
	function it_adds_single_primary_index()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->create();
		$blueprint->integer('f_id');

		$blueprint->primary('f_id');

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertContains(", primary key (f_id)", $def[0]);
	}

	/**
	 * @test
	 */
	function it_adds_double_primary_index()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->create();
		$blueprint->integer('f_id');
		$blueprint->integer('f2_id');

		$blueprint->primary(['f_id','f2_id']);

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertContains(", primary key (f_id,f2_id)", $def[0]);
	}

	/**
	 * @test
	 */
	function it_does_not_add_unique_index_on_field()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->create();
		$blueprint->integer('f_id')->unique();

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertNotContains("unique", $def[0]);
	}

	/**
	 * @test
	 * @expectedException RatkoR\Crate\NotImplementedException
	 */
	function it_throws_exception_for_underscore_id_field()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->create();
		$blueprint->integer('f_id');
		$blueprint->integer('_id');

		$def = $blueprint->toSql($this->connection, $this->grammar);
	}

	/**
	 * @test
	 * @expectedException RatkoR\Crate\NotImplementedException
	 */
	function it_throws_exception_for_underscore_score_field()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->create();
		$blueprint->integer('_score');
		$blueprint->string('name');

		$def = $blueprint->toSql($this->connection, $this->grammar);
	}

	/**
	 * @test
	 * @expectedException RatkoR\Crate\NotImplementedException
	 */
	function it_throws_exception_for_underscore_version_field()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->create();
		$blueprint->integer('nbItems');
		$blueprint->string('_version');

		$def = $blueprint->toSql($this->connection, $this->grammar);
	}

	/**
	 * @test
	 * @expectedException RatkoR\Crate\NotImplementedException
	 */
	function it_throws_exception_for_dropColumn()
	{
		$blueprint = new Blueprint('testtable');
		$blueprint->dropColumn('f_string');
	}

	/**
	 * @test
	 * @expectedException RatkoR\Crate\NotImplementedException
	 */
	function it_throws_exception_for_renameColumn()
	{
		$blueprint = new Blueprint('testtable');
		$blueprint->renameColumn('f_string','t_to');
	}

	/**
	 * @test
	 * @expectedException RatkoR\Crate\NotImplementedException
	 */
	function it_throws_exception_for_changing_column()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->string('f_string', 50)->change();
		$def = $blueprint->toSql($this->connection, $this->grammar);
	}

	/**
	 * @test
	 */
	function it_creates_table_with_two_fields()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->create();
		$blueprint->integer('f_id');
		$blueprint->string('f_string');

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertContains("create table \"testtable\" (\"f_id\" integer, \"f_string\" string )", $def[0]);
	}

	/**
	 * @test
	 */
	function it_drops_table()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->drop();
		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertContains("drop table \"testtable\"", $def[0]);
	}

	/**
	 * @test
	 */
	function it_creates_table_with_object_field()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->create();
		$blueprint->objectField('f_object');

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertContains("\"f_object\" object", $def[0]);
	}

	/**
	 * @test
	 */
	function it_creates_table_with_dynamic_object_field()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->create();
		$blueprint->objectField('f_object','dynamic');

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertContains("\"f_object\" object dynamic", $def[0]);
	}

	/**
	 * @test
	 */
	function it_creates_table_with_strict_object_field()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->create();
		$blueprint->objectField('f_object','strict');

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertContains("\"f_object\" object strict", $def[0]);
	}

	/**
	 * @test
	 */
	function it_creates_table_with_object_field_with_subfields()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->create();
		$blueprint->objectField('f_object','as (f_date timestamp)');

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertContains("\"f_object\" object as (f_date timestamp)", $def[0]);
	}

	/**
	 * @test
	 */
	function it_creates_table_with_array_field()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->create();
		$blueprint->arrayField('f_array','object as (age integer, name string');

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertContains("\"f_array\" array (object as (age integer, name string) )", $def[0]);
	}

	/**
	 * @test
	 */
	function it_adds_a_new_integer_field_to_table()
	{
		$blueprint = new Blueprint('testtable');

		$blueprint->integer('f_integer');

		$def = $blueprint->toSql($this->connection, $this->grammar);

		$this->assertContains("alter table \"testtable\" add \"f_integer\" integer", $def[0]);
	}
}