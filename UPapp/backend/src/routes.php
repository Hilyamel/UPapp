<?php

declare(strict_types=1);

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UpApp\Handlers\HealthCheckHandler;

/** @var App $app */

// Health check endpoint
$app->get('/api/health', HealthCheckHandler::class);

// Root endpoint
$app->get('/', function (Request $request, Response $response) {
    $data = [
        'success' => true,
        'data' => [
            'application' => 'UPapp Backend API',
            'version' => '1.0.0',
            'status' => 'running',
        ],
        'error' => null,
    ];

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});
