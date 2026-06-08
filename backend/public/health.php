<?php

// Produkcja: nie wypuszczaj ostrzezen PHP do JSON
ini_set('display_errors', '0');
ini_set('log_errors', '1');
putenv('HOME=' . dirname(__DIR__));
putenv('AWS_CONFIG_FILE=' . dirname(__DIR__) . '/.aws-config');
putenv('AWS_SHARED_CREDENTIALS_FILE=' . dirname(__DIR__) . '/.aws-credentials');

/**
 * Simple health check endpoint for deployment verification
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'php_version' => PHP_VERSION,
    'app' => 'UPapp Backend',
];

// Check DynamoDB connection
try {
    require_once __DIR__ . '/../vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();

    $dynamodb = new Aws\DynamoDb\DynamoDbClient([
        'region' => $_ENV['AWS_REGION'] ?? 'eu-central-1',
        'version' => 'latest',
    ]);

    // Simple list tables call to verify connection
    $dynamodb->listTables(['Limit' => 1]);
    $health['dynamodb'] = 'connected';

} catch (Exception $e) {
    $health['dynamodb'] = 'error: ' . $e->getMessage();
    $health['status'] = 'degraded';
}

http_response_code($health['status'] === 'healthy' ? 200 : 503);
echo json_encode($health, JSON_PRETTY_PRINT);
