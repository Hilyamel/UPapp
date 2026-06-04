<?php

declare(strict_types=1);

namespace UpApp\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use UpApp\Config\Environment;

class EnvironmentTest extends TestCase
{
    protected function setUp(): void
    {
        Environment::load();
    }

    public function testGetEnvironmentVariable(): void
    {
        $appEnv = Environment::get('APP_ENV');
        $this->assertNotNull($appEnv);
        $this->assertContains($appEnv, ['dev', 'uat', 'prod', 'test']);
    }

    public function testGetTableName(): void
    {
        $tableName = Environment::getTableName('users');
        $this->assertStringContainsString('UpApp', $tableName);
        $this->assertStringContainsString('users', $tableName);
    }

    public function testIsDevMethod(): void
    {
        $isDev = Environment::isDev();
        $this->assertIsBool($isDev);
    }

    public function testTableNamingConvention(): void
    {
        $configTable = Environment::getTableName('config');
        $usersTable = Environment::getTableName('users');

        // Both should follow UpApp.<ENV>.tablename pattern
        $this->assertMatchesRegularExpression('/^UpApp\.\w+\.\w+$/', $configTable);
        $this->assertMatchesRegularExpression('/^UpApp\.\w+\.\w+$/', $usersTable);

        // Different tables should have different names
        $this->assertNotEquals($configTable, $usersTable);
    }
}
