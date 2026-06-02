<?php

declare(strict_types=1);

namespace UpApp\Config;

use Aws\DynamoDb\DynamoDbClient as AwsDynamoDbClient;

class DynamoDBClient
{
    private static ?AwsDynamoDbClient $instance = null;

    public static function getInstance(): AwsDynamoDbClient
    {
        if (self::$instance === null) {
            $config = [
                'region' => Environment::get('AWS_REGION', 'eu-central-1'),
                'version' => 'latest',
            ];

            // Add credentials if provided in environment
            $accessKey = Environment::get('AWS_ACCESS_KEY_ID');
            $secretKey = Environment::get('AWS_SECRET_ACCESS_KEY');

            if (!empty($accessKey) && !empty($secretKey)) {
                $config['credentials'] = [
                    'key' => $accessKey,
                    'secret' => $secretKey,
                ];
            }
            // Otherwise, SDK will use default credential provider chain (AWS CLI profile, IAM role, etc.)

            self::$instance = new AwsDynamoDbClient($config);
        }

        return self::$instance;
    }

    // Prevent cloning
    private function __clone()
    {
    }

    // Prevent unserialization
    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize singleton');
    }
}
