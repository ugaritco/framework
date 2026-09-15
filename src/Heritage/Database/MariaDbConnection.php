<?php

namespace Heritage\Database;

use Heritage\Database\Query\Grammars\MariaDbGrammar as QueryGrammar;
use Heritage\Database\Query\Processors\MariaDbProcessor;
use Heritage\Database\Schema\Grammars\MariaDbGrammar as SchemaGrammar;
use Heritage\Database\Schema\MariaDbBuilder;
use Heritage\Database\Schema\MariaDbSchemaState;
use Heritage\Filesystem\Filesystem;
use Heritage\Support\Str;

class MariaDbConnection extends MySqlConnection
{
    /**
     * {@inheritdoc}
     */
    public function getDriverTitle()
    {
        return 'MariaDB';
    }

    /**
     * Determine if the connected database is a MariaDB database.
     *
     * @return bool
     */
    public function isMaria()
    {
        return true;
    }

    /**
     * Get the server version for the connection.
     *
     * @return string
     */
    public function getServerVersion(): string
    {
        return Str::between(parent::getServerVersion(), '5.5.5-', '-MariaDB');
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Heritage\Database\Query\Grammars\MariaDbGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar($this);
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Heritage\Database\Schema\MariaDbBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new MariaDbBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Heritage\Database\Schema\Grammars\MariaDbGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return new SchemaGrammar($this);
    }

    /**
     * Get the schema state for the connection.
     *
     * @param  \Heritage\Filesystem\Filesystem|null  $files
     * @param  callable|null  $processFactory
     * @return \Heritage\Database\Schema\MariaDbSchemaState
     */
    public function getSchemaState(?Filesystem $files = null, ?callable $processFactory = null)
    {
        return new MariaDbSchemaState($this, $files, $processFactory);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Heritage\Database\Query\Processors\MariaDbProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new MariaDbProcessor;
    }
}
