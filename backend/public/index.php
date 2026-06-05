<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use UpApp\Config\Environment;
use UpApp\Middleware\CorsMiddleware;

// Load environment variables
try {
    Environment::load();
} catch (\Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'data' => null,
        'error' => [
            'code' => 'CONFIG_ERROR',
            'message' => 'Environment configuration error',
            'details' => [
                'exception' => $e->getMessage()
            ]
        ]
    ]);
    exit(1);
}

// Configure session for CORS (local development with HTTP)
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', '0'); // Set to 1 for HTTPS
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_domain', 'localhost');

// Create Slim app
$app = AppFactory::create();

// Add CORS middleware
$app->add(new CorsMiddleware());

// Add error middleware
$app->addErrorMiddleware(
    Environment::get('APP_ENV') === 'dev',
    true,
    true
);

// Register routes
require __DIR__ . '/../src/routes.php';

// Run app
$app->run();
