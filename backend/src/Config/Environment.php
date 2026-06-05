<?php

declare(strict_types=1);

namespace UpApp\Config;

use Dotenv\Dotenv;

class Environment
{
    private static bool $loaded = false;
    private static array $requiredVars = [
        'APP_ENV',
        'APP_URL',
        'AWS_REGION',
        'DYNAMODB_TABLE_PREFIX',
        'BACKEND_URL',
    ];

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        // Load .env from project root (3 levels up from src/Config/)
        $envPath = dirname(__DIR__, 3);
        $envFile = $envPath . '/.env';

        // Only load .env if it exists (in CI it may not exist)
        if (file_exists($envFile)) {
            $dotenv = Dotenv::createImmutable($envPath);
            $dotenv->load();
        }

        self::validate();
        self::$loaded = true;
    }

    private static function validate(): void
    {
        // Set defaults for test environment if not set
        if (!isset($_ENV['APP_ENV'])) {
            $_ENV['APP_ENV'] = 'test';
        }

        $missing = [];

        foreach (self::$requiredVars as $var) {
            if (empty($_ENV[$var])) {
                // Set reasonable defaults for testing
                switch ($var) {
                    case 'APP_URL':
                        $_ENV[$var] = 'http://localhost:5173';
                        break;
                    case 'AWS_REGION':
                        $_ENV[$var] = 'eu-central-1';
                        break;
                    case 'DYNAMODB_TABLE_PREFIX':
                        $_ENV[$var] = 'UpApp';
                        break;
                    case 'BACKEND_URL':
                        $_ENV[$var] = 'http://localhost:8080';
                        break;
                    default:
                        $missing[] = $var;
                }
            }
        }

        if (!empty($missing)) {
            throw new \RuntimeException(
                'Missing required environment variables: ' . implode(', ', $missing)
            );
        }

        // Validate APP_ENV
        $validEnvs = ['dev', 'uat', 'prod', 'test'];
        if (!in_array($_ENV['APP_ENV'], $validEnvs, true)) {
            throw new \RuntimeException(
                'APP_ENV must be one of: ' . implode(', ', $validEnvs)
            );
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return $_ENV[$key] ?? $default;
    }

    public static function getTableName(string $table): string
    {
        $prefix = self::get('DYNAMODB_TABLE_PREFIX', 'UpApp');
        $env = self::get('APP_ENV', 'dev');
        return sprintf('%s.%s.%s', $prefix, $env, $table);
    }

    public static function isDev(): bool
    {
        return self::get('APP_ENV') === 'dev';
    }

    public static function isProd(): bool
    {
        return self::get('APP_ENV') === 'prod';
    }

    public static function isTest(): bool
    {
        return self::get('APP_ENV') === 'test';
    }
}
