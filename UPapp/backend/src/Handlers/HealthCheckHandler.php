<?php

declare(strict_types=1);

namespace UpApp\Handlers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UpApp\Config\DynamoDBClient;
use UpApp\Config\Environment;

class HealthCheckHandler
{
    public function __invoke(Request $request, Response $response): Response
    {
        $status = 'healthy';
        $services = [
            'api' => 'ok',
            'dynamodb' => 'ok',
        ];
        $errors = [];

        // Check DynamoDB connection
        try {
            $client = DynamoDBClient::getInstance();
            $tableName = Environment::getTableName('config');

            $client->describeTable(['TableName' => $tableName]);
        } catch (\Aws\DynamoDB\Exception\DynamoDbException $e) {
            if ($e->getAwsErrorCode() === 'ResourceNotFoundException') {
                // Table doesn't exist yet, but connection works
                $services['dynamodb'] = 'ok';
            } else {
                $services['dynamodb'] = 'error';
                $status = 'degraded';
                $errors['dynamodb'] = Environment::isDev()
                    ? $e->getMessage()
                    : 'DynamoDB connection failed';
            }
        } catch (\Exception $e) {
            $services['dynamodb'] = 'error';
            $status = 'degraded';
            $errors['dynamodb'] = Environment::isDev()
                ? $e->getMessage()
                : 'DynamoDB connection failed';
        }

        $data = [
            'success' => true,
            'data' => [
                'status' => $status,
                'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
                'environment' => Environment::get('APP_ENV'),
                'services' => $services,
            ],
            'error' => null,
        ];

        if (!empty($errors)) {
            $data['data']['errors'] = $errors;
        }

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
