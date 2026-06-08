<?php

declare(strict_types=1);

// --- Produkcja: NIE wypuszczaj ostrzezen/notice PHP do tresci odpowiedzi (psuly JSON API) ---
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// --- Shared hosting: nie pozwol AWS SDK skanowac /.aws/* poza open_basedir ---
// Wskazujemy nieistniejace pliki wewnatrz katalogu aplikacji (is_readable zwroci false bez ostrzezenia).
putenv('HOME=' . dirname(__DIR__));
putenv('AWS_CONFIG_FILE=' . dirname(__DIR__) . '/.aws-config');
putenv('AWS_SHARED_CREDENTIALS_FILE=' . dirname(__DIR__) . '/.aws-credentials');

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

// Configure session cookie for production (same-origin HTTPS)
ini_set('session.cookie_samesite', 'Lax');
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
ini_set('session.cookie_secure', $isHttps ? '1' : '0');
ini_set('session.cookie_httponly', '1');
// Do NOT pin cookie_domain to 'localhost' on production -> let it default to the current host
ini_set('session.cookie_path', '/');

// Create Slim app
$app = AppFactory::create();

// Add CORS middleware
$app->add(new CorsMiddleware());

// Add error middleware (no verbose details on production)
$app->addErrorMiddleware(
    Environment::get('APP_ENV') === 'dev',
    true,
    true
);

// Register routes
require __DIR__ . '/../src/routes.php';

// Run app
$app->run();
