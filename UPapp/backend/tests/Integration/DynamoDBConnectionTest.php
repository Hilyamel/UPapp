<?php

declare(strict_types=1);

namespace UpApp\Tests\Integration;

use PHPUnit\Framework\TestCase;
use UpApp\Config\DynamoDBClient;
use UpApp\Config\Environment;

class DynamoDBConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        Environment::load();
    }

    public function testCanConnectToDynamoDB(): void
    {
        $client = DynamoDBClient::getInstance();
        $this->assertNotNull($client);

        // Test describe table operation (should work even if table doesn't exist yet)
        $tableName = Environment::getTableName('config');

        try {
            $result = $client->describeTable(['TableName' => $tableName]);
            $this->assertArrayHasKey('Table', $result);
        } catch (\Aws\DynamoDB\Exception\DynamoDbException $e) {
            // If table doesn't exist, that's okay - connection works
            if ($e->getAwsErrorCode() !== 'ResourceNotFoundException') {
                throw $e;
            }
            $this->assertTrue(true, 'DynamoDB connection successful (table not found is expected)');
        }
    }

    public function testTableNamingConvention(): void
    {
        $tableName = Environment::getTableName('config');
        $env = Environment::get('APP_ENV');

        $this->assertStringContainsString('UpApp', $tableName);
        $this->assertStringContainsString($env, $tableName);
        $this->assertStringContainsString('config', $tableName);
    }
}
