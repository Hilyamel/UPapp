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

        $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
        $dotenv->load();

        self::validate();
        self::$loaded = true;
    }

    private static function validate(): void
    {
        $missing = [];

        foreach (self::$requiredVars as $var) {
            if (empty($_ENV[$var])) {
                $missing[] = $var;
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
