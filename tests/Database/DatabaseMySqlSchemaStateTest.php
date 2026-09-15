<?php

namespace Heritage\Tests\Database;

use Exception;
use Generator;
use Heritage\Database\MySqlConnection;
use Heritage\Database\Schema\MySqlSchemaState;
use Pdo\Mysql;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Process\Process;

class DatabaseMySqlSchemaStateTest extends TestCase
{
    #[DataProvider('provider')]
    public function testConnectionString(string $expectedConnectionString, array $expectedVariables, array $dbConfig): void
    {
        $connection = $this->createMock(MySqlConnection::class);
        $connection->method('getConfig')->willReturn($dbConfig);

        $schemaState = new MySqlSchemaState($connection);

        $versionInfo = ['version' => '8.0.0', 'isMariaDb' => false];

        // test connectionString
        $method = new ReflectionMethod(get_class($schemaState), 'connectionString');
        $connString = $method->invoke($schemaState, $versionInfo);

        $this->assertEquals($expectedConnectionString, $connString);

        // test baseVariables
        $method = new ReflectionMethod(get_class($schemaState), 'baseVariables');
        $variables = $method->invoke($schemaState, $dbConfig);

        $this->assertEquals($expectedVariables, $variables);
    }

    public static function provider(): Generator
    {
        yield 'default' => [
            ' --user="${:UGARIT_LOAD_USER}" --password="${:UGARIT_LOAD_PASSWORD}" --host="${:UGARIT_LOAD_HOST}" --port="${:UGARIT_LOAD_PORT}"', [
                'UGARIT_LOAD_SOCKET' => '',
                'UGARIT_LOAD_HOST' => '127.0.0.1',
                'UGARIT_LOAD_PORT' => '',
                'UGARIT_LOAD_USER' => 'root',
                'UGARIT_LOAD_PASSWORD' => '',
                'UGARIT_LOAD_DATABASE' => 'forge',
                'UGARIT_LOAD_SSL_CA' => '',
                'UGARIT_LOAD_SSL_CERT' => '',
                'UGARIT_LOAD_SSL_KEY' => '',
            ], [
                'username' => 'root',
                'host' => '127.0.0.1',
                'database' => 'forge',
            ],
        ];

        yield 'ssl_ca' => [
            ' --user="${:UGARIT_LOAD_USER}" --password="${:UGARIT_LOAD_PASSWORD}" --host="${:UGARIT_LOAD_HOST}" --port="${:UGARIT_LOAD_PORT}" --ssl-ca="${:UGARIT_LOAD_SSL_CA}"', [
                'UGARIT_LOAD_SOCKET' => '',
                'UGARIT_LOAD_HOST' => '',
                'UGARIT_LOAD_PORT' => '',
                'UGARIT_LOAD_USER' => 'root',
                'UGARIT_LOAD_PASSWORD' => '',
                'UGARIT_LOAD_DATABASE' => 'forge',
                'UGARIT_LOAD_SSL_CA' => 'ssl.ca',
                'UGARIT_LOAD_SSL_CERT' => '',
                'UGARIT_LOAD_SSL_KEY' => '',
            ], [
                'username' => 'root',
                'database' => 'forge',
                'options' => [
                    Mysql::ATTR_SSL_CA => 'ssl.ca',
                ],
            ],
        ];

        yield 'ssl_cert_and_key' => [
            ' --user="${:UGARIT_LOAD_USER}" --password="${:UGARIT_LOAD_PASSWORD}" --host="${:UGARIT_LOAD_HOST}" --port="${:UGARIT_LOAD_PORT}" --ssl-ca="${:UGARIT_LOAD_SSL_CA}" --ssl-cert="${:UGARIT_LOAD_SSL_CERT}" --ssl-key="${:UGARIT_LOAD_SSL_KEY}"', [
                'UGARIT_LOAD_SOCKET' => '',
                'UGARIT_LOAD_HOST' => '',
                'UGARIT_LOAD_PORT' => '',
                'UGARIT_LOAD_USER' => 'root',
                'UGARIT_LOAD_PASSWORD' => '',
                'UGARIT_LOAD_DATABASE' => 'forge',
                'UGARIT_LOAD_SSL_CA' => 'ssl.ca',
                'UGARIT_LOAD_SSL_CERT' => '/path/to/client-cert.pem',
                'UGARIT_LOAD_SSL_KEY' => '/path/to/client-key.pem',
            ], [
                'username' => 'root',
                'database' => 'forge',
                'options' => [
                    Mysql::ATTR_SSL_CA => 'ssl.ca',
                    Mysql::ATTR_SSL_CERT => '/path/to/client-cert.pem',
                    Mysql::ATTR_SSL_KEY => '/path/to/client-key.pem',
                ],
            ],
        ];

        yield 'no_ssl' => [
            ' --user="${:UGARIT_LOAD_USER}" --password="${:UGARIT_LOAD_PASSWORD}" --host="${:UGARIT_LOAD_HOST}" --port="${:UGARIT_LOAD_PORT}" --ssl-mode=DISABLED', [
                'UGARIT_LOAD_SOCKET' => '',
                'UGARIT_LOAD_HOST' => '',
                'UGARIT_LOAD_PORT' => '',
                'UGARIT_LOAD_USER' => 'root',
                'UGARIT_LOAD_PASSWORD' => '',
                'UGARIT_LOAD_DATABASE' => 'forge',
                'UGARIT_LOAD_SSL_CA' => '',
                'UGARIT_LOAD_SSL_CERT' => '',
                'UGARIT_LOAD_SSL_KEY' => '',
            ], [
                'username' => 'root',
                'database' => 'forge',
                'options' => [
                    Mysql::ATTR_SSL_VERIFY_SERVER_CERT => false,
                ],
            ],
        ];

        yield 'unix socket' => [
            ' --user="${:UGARIT_LOAD_USER}" --password="${:UGARIT_LOAD_PASSWORD}" --socket="${:UGARIT_LOAD_SOCKET}"', [
                'UGARIT_LOAD_SOCKET' => '/tmp/mysql.sock',
                'UGARIT_LOAD_HOST' => '',
                'UGARIT_LOAD_PORT' => '',
                'UGARIT_LOAD_USER' => 'root',
                'UGARIT_LOAD_PASSWORD' => '',
                'UGARIT_LOAD_DATABASE' => 'forge',
                'UGARIT_LOAD_SSL_CA' => '',
                'UGARIT_LOAD_SSL_CERT' => '',
                'UGARIT_LOAD_SSL_KEY' => '',
            ], [
                'username' => 'root',
                'database' => 'forge',
                'unix_socket' => '/tmp/mysql.sock',
            ],
        ];
    }

    public function testExecuteDumpProcessForDepth()
    {
        $mockProcess = $this->createMock(Process::class);
        $mockProcess->method('setTimeout')->willReturnSelf();
        $mockProcess->method('mustRun')->willThrowException(
            new Exception('column-statistics')
        );

        $mockOutput = $this->createStub(\stdClass::class);
        $mockVariables = [];

        $schemaState = $this->getMockBuilder(MySqlSchemaState::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['makeProcess'])
            ->getMock();

        $schemaState->method('makeProcess')->willReturn($mockProcess);

        $this->expectExceptionObject(new Exception('Dump execution exceeded maximum depth of 30.'));

        // test executeDumpProcess
        $method = new ReflectionMethod(get_class($schemaState), 'executeDumpProcess');
        $method->invoke($schemaState, $mockProcess, $mockOutput, $mockVariables, 31);
    }
}
