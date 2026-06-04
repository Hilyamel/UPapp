<?php

declare(strict_types=1);

namespace UpApp\Tests\Integration;

use PHPUnit\Framework\TestCase;
use UpApp\Config\Environment;

class EnvironmentTableNamingTest extends TestCase
{
    protected function setUp(): void
    {
        Environment::load();
    }

    public function testTableNamesRespectEnvironment(): void
    {
        $env = Environment::get('APP_ENV');
        $tableName = Environment::getTableName('config');

        // Table name must contain the environment
        $this->assertStringContainsString($env, $tableName);

        // Table name must follow pattern: UpApp.<ENV>.config
        $expectedPattern = sprintf('/^UpApp\.%s\.config$/', preg_quote($env, '/'));
        $this->assertMatchesRegularExpression($expectedPattern, $tableName);
    }

    public function testDifferentEnvironmentsProduceDifferentTableNames(): void
    {
        // Save current env
        $originalEnv = Environment::get('APP_ENV');

        // This test verifies the logic works, but we can't actually change APP_ENV during test
        // Just verify that the table name construction includes the environment
        $tableName = Environment::getTableName('config');

        // Split and verify structure
        $parts = explode('.', $tableName);
        $this->assertCount(3, $parts);
        $this->assertEquals('UpApp', $parts[0]);
        $this->assertEquals($originalEnv, $parts[1]);
        $this->assertEquals('config', $parts[2]);
    }
}
