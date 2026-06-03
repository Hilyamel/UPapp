<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient;
use UpApp\Config\Environment;

// Load environment variables
Environment::load();

$dynamodb = new DynamoDbClient([
    'region' => Environment::get('AWS_REGION', 'eu-central-1'),
    'version' => 'latest',
]);

$prefix = Environment::get('DYNAMODB_TABLE_PREFIX', 'UpApp');
$env = Environment::get('APP_ENV', 'dev');
$tableName = "{$prefix}.{$env}.Forms";

echo "Creating table: {$tableName}\n";

try {
    $result = $dynamodb->createTable([
        'TableName' => $tableName,
        'AttributeDefinitions' => [
            [
                'AttributeName' => 'FormId',
                'AttributeType' => 'S'
            ],
            [
                'AttributeName' => 'UserId',
                'AttributeType' => 'S'
            ],
            [
                'AttributeName' => 'CreatedAt',
                'AttributeType' => 'S'
            ]
        ],
        'KeySchema' => [
            [
                'AttributeName' => 'FormId',
                'KeyType' => 'HASH'
            ]
        ],
        'GlobalSecondaryIndexes' => [
            [
                'IndexName' => 'UserIndex',
                'KeySchema' => [
                    [
                        'AttributeName' => 'UserId',
                        'KeyType' => 'HASH'
                    ],
                    [
                        'AttributeName' => 'CreatedAt',
                        'KeyType' => 'RANGE'
                    ]
                ],
                'Projection' => [
                    'ProjectionType' => 'ALL'
                ],
                'ProvisionedThroughput' => [
                    'ReadCapacityUnits' => 5,
                    'WriteCapacityUnits' => 5
                ]
            ]
        ],
        'ProvisionedThroughput' => [
            'ReadCapacityUnits' => 5,
            'WriteCapacityUnits' => 5
        ]
    ]);

    echo "Table created successfully!\n";
    echo "Waiting for table to become active...\n";

    $dynamodb->waitUntil('TableExists', [
        'TableName' => $tableName,
    ]);

    echo "Table is now active and ready to use.\n";

} catch (Exception $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
    exit(1);
}
