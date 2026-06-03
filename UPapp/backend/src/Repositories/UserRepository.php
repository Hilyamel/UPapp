<?php

namespace UpApp\Repositories;

use UpApp\Config\DynamoDBClient;
use UpApp\Config\Environment;
use UpApp\Models\User;
use Aws\DynamoDB\Exception\DynamoDbException;

class UserRepository
{
    private $dynamodb;
    private string $tableName;

    public function __construct()
    {
        $this->dynamodb = DynamoDBClient::getInstance();
        $this->tableName = Environment::getTableName('users');
    }

    public function findByEmail(string $email): ?User
    {
        try {
            $result = $this->dynamodb->query([
                'TableName' => $this->tableName,
                'IndexName' => 'EmailIndex',
                'KeyConditionExpression' => 'Email = :email',
                'ExpressionAttributeValues' => [
                    ':email' => ['S' => $email]
                ]
            ]);

            if (empty($result['Items'])) {
                return null;
            }

            return $this->mapItemToUser($result['Items'][0]);
        } catch (DynamoDbException $e) {
            error_log("DynamoDB error finding user by email: " . $e->getMessage());
            return null;
        }
    }

    public function findById(string $id): ?User
    {
        try {
            $result = $this->dynamodb->getItem([
                'TableName' => $this->tableName,
                'Key' => [
                    'UserId' => ['S' => $id]
                ]
            ]);

            if (empty($result['Item'])) {
                return null;
            }

            return $this->mapItemToUser($result['Item']);
        } catch (DynamoDbException $e) {
            error_log("DynamoDB error finding user by ID: " . $e->getMessage());
            return null;
        }
    }

    public function create(User $user): bool
    {
        try {
            $item = [
                'UserId' => ['S' => $user->getId()],
                'Email' => ['S' => $user->getEmail()],
                'PasswordHash' => ['S' => $user->getPasswordHash()],
                'FullName' => ['S' => $user->getFullName() ?? ''],
                'EmailVerified' => ['BOOL' => $user->isEmailVerified()],
                'CreatedAt' => ['S' => $user->getCreatedAt()],
                'UpdatedAt' => ['S' => $user->getUpdatedAt()]
            ];

            if ($user->getVerificationToken()) {
                $item['VerificationToken'] = ['S' => $user->getVerificationToken()];
            }

            $this->dynamodb->putItem([
                'TableName' => $this->tableName,
                'Item' => $item,
                'ConditionExpression' => 'attribute_not_exists(UserId)'
            ]);

            return true;
        } catch (DynamoDbException $e) {
            error_log("DynamoDB error creating user: " . $e->getMessage());
            return false;
        }
    }

    public function update(User $user): bool
    {
        try {
            $item = [
                'UserId' => ['S' => $user->getId()],
                'Email' => ['S' => $user->getEmail()],
                'PasswordHash' => ['S' => $user->getPasswordHash()],
                'FullName' => ['S' => $user->getFullName() ?? ''],
                'EmailVerified' => ['BOOL' => $user->isEmailVerified()],
                'UpdatedAt' => ['S' => $user->getUpdatedAt()],
                'CreatedAt' => ['S' => $user->getCreatedAt()]
            ];

            if ($user->getVerificationToken()) {
                $item['VerificationToken'] = ['S' => $user->getVerificationToken()];
            }

            $this->dynamodb->putItem([
                'TableName' => $this->tableName,
                'Item' => $item
            ]);

            return true;
        } catch (DynamoDbException $e) {
            error_log("DynamoDB error updating user: " . $e->getMessage());
            return false;
        }
    }

    public function findByVerificationToken(string $token): ?User
    {
        try {
            $result = $this->dynamodb->scan([
                'TableName' => $this->tableName,
                'FilterExpression' => 'VerificationToken = :token',
                'ExpressionAttributeValues' => [
                    ':token' => ['S' => $token]
                ]
            ]);

            if (empty($result['Items'])) {
                return null;
            }

            return $this->mapItemToUser($result['Items'][0]);
        } catch (DynamoDbException $e) {
            error_log("DynamoDB error finding user by token: " . $e->getMessage());
            return null;
        }
    }

    public function delete(string $userId): bool
    {
        try {
            $this->dynamodb->deleteItem([
                'TableName' => $this->tableName,
                'Key' => [
                    'UserId' => ['S' => $userId]
                ]
            ]);

            return true;
        } catch (DynamoDbException $e) {
            error_log("DynamoDB error deleting user: " . $e->getMessage());
            return false;
        }
    }

    private function mapItemToUser(array $item): User
    {
        return new User(
            $item['UserId']['S'],
            $item['Email']['S'],
            $item['PasswordHash']['S'],
            $item['FullName']['S'] ?? null,
            $item['EmailVerified']['BOOL'] ?? false,
            $item['VerificationToken']['S'] ?? null,
            $item['CreatedAt']['S'] ?? null,
            $item['UpdatedAt']['S'] ?? null
        );
    }
}
