<?php

declare(strict_types=1);

namespace Testcontainers\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Predis\Client;
use Testcontainers\Container\MariaDBContainer;
use Testcontainers\Container\MySQLContainer;
use Testcontainers\Container\OpenSearchContainer;
use Testcontainers\Container\PostgresContainer;
use Testcontainers\Container\RedisContainer;

class ContainerTest extends TestCase
{
    public function testMySQL(): void
    {
        $container = MySQLContainer::make();
        $container->withMySQLDatabase('foo');
        $container->withMySQLUser('bar', 'baz');

        $container->run();

        $pdo = new \PDO(
            sprintf('mysql:host=%s;port=3306', $container->getAddress()),
            'bar',
            'baz',
        );

        $query = $pdo->query('SHOW databases');

        $this->assertInstanceOf(\PDOStatement::class, $query);

        $databases = $query->fetchAll(\PDO::FETCH_COLUMN);

        $this->assertNotFalse($databases);
        $this->assertContains('foo', $databases);
    }

    public function testMariaDB(): void
    {
        $container = MariaDBContainer::make();
        $container->withMariaDBDatabase('foo');
        $container->withMariaDBUser('bar', 'baz');

        $container->run();

        $pdo = new \PDO(
            sprintf('mysql:host=%s;port=3306', $container->getAddress()),
            'bar',
            'baz',
        );

        $query = $pdo->query('SHOW databases');

        $this->assertInstanceOf(\PDOStatement::class, $query);

        $databases = $query->fetchAll(\PDO::FETCH_COLUMN);

        $this->assertNotFalse($databases);
        $this->assertContains('foo', $databases);
    }

    public function testRedis(): void
    {
        $container = RedisContainer::make();

        $container->run();

        $redis = new Client([
            'scheme' => 'tcp',
            'host'   => $container->getAddress(),
            'port'   => 6379,
        ]);

        $redis->ping();

        $this->assertTrue($redis->isConnected());
    }

    public function testOpenSearch(): void
    {
        $container = OpenSearchContainer::make();
        $container->disableSecurityPlugin();

        $container->run();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf('http://%s:%d', $container->getAddress(), 9200));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = (string) curl_exec($ch);

        $this->assertNotEmpty($response);

        /** @var array{cluster_name: string} $data */
        $data = json_decode($response, true, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('cluster_name', $data);

        $this->assertEquals('docker-cluster', $data['cluster_name']);
    }

    public function testPostgreSQLContainer(): void
    {
        $container = PostgresContainer::make('latest', 'test')
            ->withPostgresUser('test')
            ->withPostgresDatabase('foo')
            ->run();


        $pdo = new \PDO(
            sprintf('pgsql:host=%s;port=5432;dbname=foo', $container->getAddress()),
            'test',
            'test',
        );

        $query = $pdo->query('SELECT datname FROM pg_database');

        $this->assertInstanceOf(\PDOStatement::class, $query);

        $databases = $query->fetchAll(\PDO::FETCH_COLUMN);

        $this->assertNotFalse($databases);
        $this->assertContains('foo', $databases);
    }
}
