<?php

declare(strict_types=1);

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UpApp\Handlers\HealthCheckHandler;
use UpApp\Handlers\AuthHandler;
use UpApp\Handlers\FormHandler;
use UpApp\Handlers\ReferenceHandler;
use UpApp\Handlers\DeployHandler;

/** @var App $app */

// Health check endpoint
$app->get('/api/health', HealthCheckHandler::class);

// Authentication endpoints
$app->post('/api/auth/register', [AuthHandler::class, 'register']);
$app->post('/api/auth/login', [AuthHandler::class, 'login']);
$app->post('/api/auth/logout', [AuthHandler::class, 'logout']);
$app->get('/api/auth/me', [AuthHandler::class, 'me']);
$app->get('/api/auth/verify-email', [AuthHandler::class, 'verifyEmail']);
$app->post('/api/auth/resend-verification', [AuthHandler::class, 'resendVerification']);
$app->post('/api/auth/forgot-password', [AuthHandler::class, 'forgotPassword']);
$app->post('/api/auth/reset-password', [AuthHandler::class, 'resetPassword']);
$app->post('/api/auth/change-password', [AuthHandler::class, 'changePassword']);
$app->delete('/api/auth/account', [AuthHandler::class, 'deleteAccount']);

// Form endpoints
$app->post('/api/forms', [FormHandler::class, 'create']);
$app->get('/api/forms', [FormHandler::class, 'list']);
$app->get('/api/forms/{id}', [FormHandler::class, 'get']);
$app->put('/api/forms/{id}', [FormHandler::class, 'update']);
$app->delete('/api/forms/{id}', [FormHandler::class, 'delete']);
$app->get('/api/forms/{id}/summary', [FormHandler::class, 'getSummary']);
$app->post('/api/forms/{id}/ai-feedback', [FormHandler::class, 'generateAIFeedback']);

// Reference data endpoints
$app->get('/api/reference/feelings', [ReferenceHandler::class, 'getFeelings']);
$app->get('/api/reference/needs', [ReferenceHandler::class, 'getNeeds']);

// Admin/Deploy endpoints
$app->post('/api/admin/deploy', [DeployHandler::class, 'triggerDeploy']);
$app->get('/api/admin/deploy/status', [DeployHandler::class, 'getDeployStatus']);

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
