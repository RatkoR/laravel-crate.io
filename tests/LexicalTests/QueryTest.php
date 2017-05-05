<?php

namespace LexicalTests;

use LexicalTests\TestCase;
use RatkoR\Crate\Connection;
use RatkoR\Crate\NotImplementedException;
use Mockery as m;
use Illuminate\Database\ConnectionInterface;
use RatkoR\Crate\Query\Processors\Processor;
use RatkoR\Crate\Query\Grammars\Grammar;
use RatkoR\Crate\Query\Builder;
use RatkoR\Crate\Connectors\Connector;

class QueryTest extends TestCase {

    private $connection = null;
    private $builder = null;

    private function setConnection()
    {
        $connector = new Connector();
        $connection = $connector->connect(['host'=>'localhost','20000']);

        return new Connection($connection, 'doc');
    }

    protected function setBuilder()
    {
        $grammar = new Grammar;
        $processor = m::mock('\RatkoR\Crate\Query\Processors\Processor');

        return new Builder(m::mock('\Illuminate\Database\ConnectionInterface'), $grammar, $processor);
    }

    protected function setUp()
    {
        $this->connection = $this->setConnection();
        $this->builder = $this->setBuilder();
    }

    protected function tearDown()
    {
        m::close();
    }

    /** @test */
    function it_tests_basic_select()
    {
        $this->builder->select('*')->from('users');
        $this->assertEquals('select * from users', $this->builder->toSql());
    }

    /**
     * @test
     * @expectedException RatkoR\Crate\NotImplementedException
     */
    public function it_throws_exception_for_join()
    {
        $this->builder->select('*')
            ->from('users')
            ->join('contacts', 'users.id', '=', 'contacts.id')
            ->leftJoin('photos', 'users.id', '=', 'photos.id');
    }

    /**
     * @test
     * @expectedException RatkoR\Crate\NotImplementedException
     */
    public function it_throws_exception_for_whereBetween()
    {
        $this->builder->select('*')->from('users')->whereBetween('id',[1,2]);
    }

    /**
     * @test
     * @expectedException RatkoR\Crate\NotImplementedException
     */
    public function it_throws_exception_for_whereExists()
    {
        $this->builder->select('*')->from('users')->whereExists(function() {});
    }

    /**
     * @test
     * @expectedException RatkoR\Crate\NotImplementedException
     */
    public function it_throws_exception_for_selectSub()
    {
        $this->builder->select('*')->from('users')->selectSub(function() {},'a');
    }

    /**
     * @test
     * @expectedException RatkoR\Crate\NotImplementedException
     */
    public function it_throws_exception_for_joins()
    {
        $this->builder->select('*')->from('users')->where('id', '=', 1);
        $this->builder->union($this->setBuilder()->select('*')->from('users')->where('id', '=', 2));
    }

    /**
     * These below are borrowed from:
     * https://github.com/yajra/laravel-oci8/blob/master/tests/Oci8QueryBuilderTest.php
     * Thx!
     */

    public function testAddingSelects()
    {
        $this->builder->select('foo')->addSelect('bar')->addSelect(['baz', 'boom'])->from('users');
        $this->assertEquals('select foo, bar, baz, boom from users', $this->builder->toSql());
    }

    public function testBasicSelectWithPrefix()
    {
        $this->builder->getGrammar()->setTablePrefix('prefix_');
        $this->builder->select('*')->from('users');
        $this->assertEquals('select * from prefix_users', $this->builder->toSql());
    }

    public function testBasicSelectDistinct()
    {
        $this->builder->distinct()->select('foo', 'bar')->from('users');
        $this->assertEquals('select distinct foo, bar from users', $this->builder->toSql());
    }

    public function testBasicAlias()
    {
        $this->builder->select('foo as bar')->from('users');
        $this->assertEquals('select foo as bar from users', $this->builder->toSql());
    }

    public function testBasicTableWrapping()
    {
        $this->builder->select('*')->from('public.users');
        $this->assertEquals('select * from public.users', $this->builder->toSql());
    }

    public function testBasicWheres()
    {
        $this->builder->select('*')->from('users')->where('id', '=', 1);
        $this->assertEquals('select * from users where id = ?', $this->builder->toSql());
        $this->assertEquals([0 => 1], $this->builder->getBindings());
    }

    public function testBasicOrWheres()
    {
        $this->builder->select('*')->from('users')->where('id', '=', 1)->orWhere('email', '=', 'foo');
        $this->assertEquals('select * from users where id = ? or email = ?', $this->builder->toSql());
        $this->assertEquals([0 => 1, 1 => 'foo'], $this->builder->getBindings());
    }

    public function testRawWheres()
    {
        $this->builder->select('*')->from('users')->whereRaw('id = ? or email = ?', [1, 'foo']);
        $this->assertEquals('select * from users where id = ? or email = ?', $this->builder->toSql());
        $this->assertEquals([0 => 1, 1 => 'foo'], $this->builder->getBindings());
    }

    public function testRawOrWheres()
    {
        $this->builder->select('*')->from('users')->where('id', '=', 1)->orWhereRaw('email = ?', ['foo']);
        $this->assertEquals('select * from users where id = ? or email = ?', $this->builder->toSql());
        $this->assertEquals([0 => 1, 1 => 'foo'], $this->builder->getBindings());
    }

    public function testBasicWhereIns()
    {
        $this->builder->select('*')->from('users')->whereIn('id', [1, 2, 3]);
        $this->assertEquals('select * from users where id in (?, ?, ?)', $this->builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2, 2 => 3], $this->builder->getBindings());

        $this->builder = $this->setBuilder();
        $this->builder->select('*')->from('users')->where('id', '=', 1)->orWhereIn('id', [1, 2, 3]);
        $this->assertEquals('select * from users where id = ? or id in (?, ?, ?)', $this->builder->toSql());
        $this->assertEquals([0 => 1, 1 => 1, 2 => 2, 3 => 3], $this->builder->getBindings());
    }

    public function testBasicWhereNotIns()
    {
        $this->builder->select('*')->from('users')->whereNotIn('id', [1, 2, 3]);
        $this->assertEquals('select * from users where id not in (?, ?, ?)', $this->builder->toSql());
        $this->assertEquals([0 => 1, 1 => 2, 2 => 3], $this->builder->getBindings());

        $this->builder = $this->setBuilder();
        $this->builder->select('*')->from('users')->where('id', '=', 1)->orWhereNotIn('id', [1, 2, 3]);
        $this->assertEquals('select * from users where id = ? or id not in (?, ?, ?)', $this->builder->toSql());
        $this->assertEquals([0 => 1, 1 => 1, 2 => 2, 3 => 3], $this->builder->getBindings());
    }

    public function testOrderBys()
    {
        $this->builder->select('*')->from('users')->orderBy('email')->orderBy('age', 'desc');
        $this->assertEquals('select * from users order by email asc, age desc', $this->builder->toSql());

        $this->builder = $this->setBuilder();
        $this->builder->select('*')->from('users')->orderBy('email')->orderByRaw('age ? desc', ['bar']);
        $this->assertEquals('select * from users order by email asc, age ? desc', $this->builder->toSql());
        $this->assertEquals(['bar'], $this->builder->getBindings());
    }

    public function testWhereShortcut()
    {
        $this->builder->select('*')->from('users')->where('id', 1)->orWhere('name', 'foo');
        $this->assertEquals('select * from users where id = ? or name = ?', $this->builder->toSql());
        $this->assertEquals([0 => 1, 1 => 'foo'], $this->builder->getBindings());
    }

    public function testInsertMethod()
    {
        $this->builder->getConnection()
            ->shouldReceive('insert')
            ->once()
            ->with('insert into users (email) values (?)', ['foo'])
            ->andReturn(true);
        $result = $this->builder->from('users')->insert(['email' => 'foo']);
        $this->assertTrue($result);
    }

    public function testUpdateMethod()
    {
        $this->builder->getConnection()
            ->shouldReceive('update')
            ->once()
            ->with('update users set email = ?, name = ? where id = ?', ['foo', 'bar', 1])
            ->andReturn(1);
        $result = $this->builder->from('users')->where('id', '=', 1)->update(['email' => 'foo', 'name' => 'bar']);
        $this->assertEquals(1, $result);
    }

    public function testDeleteMethod()
    {
        $this->builder->getConnection()
            ->shouldReceive('delete')
            ->once()
            ->with('delete from users where email = ?', ['foo'])
            ->andReturn(1);
        $result = $this->builder->from('users')->where('email', '=', 'foo')->delete();
        $this->assertEquals(1, $result);

        $this->builder = $this->setBuilder();
        $this->builder->getConnection()
            ->shouldReceive('delete')
            ->once()
            ->with('delete from users where users.id = ?', [1])
            ->andReturn(1);
        $result = $this->builder->from('users')->delete(1);
        $this->assertEquals(1, $result);
    }

    public function testTruncateMethod()
    {
        $this->builder->getConnection()->shouldReceive('statement')->once()->with('delete from users', []);
        $this->builder->from('users')->truncate();
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testBuilderThrowsExpectedExceptionWithUndefinedMethod()
    {
        $this->builder->noValidMethodHere();
    }
}
