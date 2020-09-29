<?php

namespace RatkoR\Crate;

use Closure;
use Exception;
use RatkoR\Crate\Schema\Builder;
use Crate\DBAL\Driver\PDOCrate\Driver as DoctrineDriver;
use RatkoR\Crate\Query\Grammars\Grammar as QueryGrammar;
use RatkoR\Crate\Schema\Grammars\Grammar as SchemaGrammar;
use Crate\PDO\PDO;
use RatkoR\Crate\Query\Builder as QueryBuilder;
use RatkoR\Crate\QueryException as QueryException;

class Connection extends \Illuminate\Database\Connection
{
    protected $fetchMode = PDO::FETCH_ASSOC;

    /**
     * Set the default fetch mode for the connection.
     *
     * NOTE! Crate cannot use PDO::FETCH_CLASS fetch mode, so
     * 	we silently  change it to PDO::FETCH_ASSOC
     * 	https://crate.io/docs/reference/pdo/usage.html#fetch-modes
     *
     * @param  int  $fetchMode
     * @return int
     */
    public function setFetchMode($fetchMode, $fetchArgument = null, array $ctorArgs = [])
    {
        if ($fetchMode !== \PDO::FETCH_ASSOC) {
            $fetchMode = \PDO::FETCH_ASSOC;
        }

        parent::setFetchMode($fetchMode, $fetchArgument, $ctorArgs);
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    public function getSchemaBuilder()
    {
        if ($this->schemaGrammar === null) {
            $this->useDefaultSchemaGrammar();
        }

        return new Builder($this);
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Grammar
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
     * @return \Crate\DBAL\Driver\PDOCrate\Driver
     */
    protected function getDoctrineDriver()
    {
        return new DoctrineDriver;
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * We need to override statement to bind parameters one by one. The
     * execute() function of crate-pdo can take bind parameters, but it
     * wrongly sets all of them to strings. So if you bind array to an
     * array field, you'd get error. A patch was sent to crate-pdo, but was
     * NACK-ed (with "fix eloquent").
     *
     * So in order to make this work we override statement().
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return bool
     */
    public function statement($query, $bindings = array())
    {
        return $this->run($query, $bindings, function($query, $bindings)
        {
            if ($this->pretending()) {
                return true;
            }

            $bindings = $this->prepareBindings($bindings);

            $stmt = $this->getPdo()->prepare($query);

            $this->bindParameters($stmt, $bindings);

            if (method_exists($this, 'recordsHaveBeenModified')) {
                $this->recordsHaveBeenModified();
            }

            return $stmt->execute();
        });
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * See description for statement() to see why we need to override
     * this funciton.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return 0;
            }

            $bindings = $this->prepareBindings($bindings);

            // For update or delete statements, we want to get the number of rows affected
            // by the statement and return that back to the developer. We'll first need
            // to execute the statement and then we'll use PDO to fetch the affected.
            $statement = $this->getPdo()->prepare($query);

            $this->bindParameters($statement, $bindings);
            $statement->execute();

            return $statement->rowCount();
        });
    }

    protected function bindParameters(&$stmt, $values)
    {
        if (!$values || !is_array($values))
            return;

        $i = 0;

        foreach ($values AS $value) {
            $this->bindParameter($i++, $stmt, $value);
        }
    }

    protected function bindParameter($index, &$stmt, $value)
    {
        $dataType = $this->guessDataType($value);
        $stmt->bindParam($index+1, $value, $dataType);
    }

    /**
     * Returns PDO::PARAM_* type based on parameter value.
     */
    protected function guessDataType($value)
    {
        switch (gettype($value)) {
            case 'array':
                return PDO::PARAM_ARRAY;
            case 'object':
                return PDO::PARAM_OBJECT;
            case 'double':
                return PDO::PARAM_DOUBLE;
            case 'boolean':
                return PDO::PARAM_BOOL;
            case 'NULL':
                return PDO::PARAM_NULL;
            case 'integer':
                return PDO::PARAM_LONG;
            default:
                return PDO::PARAM_STR;
        }
    }

    /**
     * Get a new query builder instance.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        return new QueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

   /**
     * Crate works with extended fields like arrays and objects. If
     * exception is triggered, laravel logs SQL and
     * it's parameters. While doing this it casts parameters to
     * string which then fails in it's default QueryException with:
     *    ErrorException: Object of class stdClass could not be converted to string
     *
     * We're overriding runQueryCallback to trigger our own
     * version of QueryException.
     *
     * @param  string    $query
     * @param  array     $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \Illuminate\Database\QueryException
     */
    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        // To execute the statement, we'll simply call the callback, which will actually
        // run the SQL against the PDO connection. Then we can calculate the time it
        // took to execute and log the query SQL, bindings and time in our memory.
        try {
            $result = $callback($query, $bindings);
        }

        // If an exception occurs when attempting to run a query, we'll format the error
        // message to include the bindings with SQL, which will make this exception a
        // lot more helpful to the developer instead of just the database's errors.
        catch (Exception $e) {
            throw new QueryException(
                $query, $this->prepareBindings($bindings), $e
            );
        }

        return $result;
    }

}
