<?php

namespace UpApp\Repositories;

use Aws\DynamoDb\DynamoDbClient;
use UpApp\Models\Form;
use UpApp\Config\Environment;

class FormRepository
{
    private DynamoDbClient $dynamodb;
    private string $tableName;

    public function __construct()
    {
        $this->dynamodb = new DynamoDbClient([
            'region' => Environment::get('AWS_REGION', 'eu-central-1'),
            'version' => 'latest',
        ]);

        $prefix = Environment::get('DYNAMODB_TABLE_PREFIX', 'UpApp');
        $env = Environment::get('APP_ENV', 'dev');
        $this->tableName = "{$prefix}.{$env}.Forms";
    }

    public function create(Form $form): bool
    {
        try {
            $item = [
                'FormId' => ['S' => $form->getId()],
                'UserId' => ['S' => $form->getUserId()],
                'FormType' => ['S' => $form->getFormType()],
                'FormData' => ['S' => json_encode($form->getFormData())],
                'CompletionStatus' => ['S' => $form->getCompletionStatus()],
                'CreatedAt' => ['S' => $form->getCreatedAt()],
                'UpdatedAt' => ['S' => $form->getUpdatedAt()],
            ];

            if ($form->getTitle()) {
                $item['Title'] = ['S' => $form->getTitle()];
            }

            $this->dynamodb->putItem([
                'TableName' => $this->tableName,
                'Item' => $item,
            ]);
            return true;
        } catch (\Exception $e) {
            error_log("Failed to create form: " . $e->getMessage());
            return false;
        }
    }

    public function update(Form $form): bool
    {
        try {
            $item = [
                'FormId' => ['S' => $form->getId()],
                'UserId' => ['S' => $form->getUserId()],
                'FormType' => ['S' => $form->getFormType()],
                'FormData' => ['S' => json_encode($form->getFormData())],
                'CompletionStatus' => ['S' => $form->getCompletionStatus()],
                'CreatedAt' => ['S' => $form->getCreatedAt()],
                'UpdatedAt' => ['S' => $form->getUpdatedAt()],
            ];

            if ($form->getTitle()) {
                $item['Title'] = ['S' => $form->getTitle()];
            }

            $this->dynamodb->putItem([
                'TableName' => $this->tableName,
                'Item' => $item,
            ]);
            return true;
        } catch (\Exception $e) {
            error_log("Failed to update form: " . $e->getMessage());
            return false;
        }
    }

    public function findById(string $formId): ?Form
    {
        try {
            $result = $this->dynamodb->getItem([
                'TableName' => $this->tableName,
                'Key' => [
                    'FormId' => ['S' => $formId],
                ],
            ]);

            if (!isset($result['Item'])) {
                return null;
            }

            return $this->itemToForm($result['Item']);
        } catch (\Exception $e) {
            error_log("Failed to find form: " . $e->getMessage());
            return null;
        }
    }

    public function findByUserId(string $userId): array
    {
        try {
            $result = $this->dynamodb->query([
                'TableName' => $this->tableName,
                'IndexName' => 'UserIndex',
                'KeyConditionExpression' => 'UserId = :userId',
                'ExpressionAttributeValues' => [
                    ':userId' => ['S' => $userId],
                ],
                'ScanIndexForward' => false, // newest first
            ]);

            $forms = [];
            foreach ($result['Items'] as $item) {
                $forms[] = $this->itemToForm($item);
            }

            return $forms;
        } catch (\Exception $e) {
            error_log("Failed to find forms for user: " . $e->getMessage());
            return [];
        }
    }

    public function delete(string $formId): bool
    {
        try {
            $this->dynamodb->deleteItem([
                'TableName' => $this->tableName,
                'Key' => [
                    'FormId' => ['S' => $formId],
                ],
            ]);
            return true;
        } catch (\Exception $e) {
            error_log("Failed to delete form: " . $e->getMessage());
            return false;
        }
    }

    public function deleteAllByUserId(string $userId): bool
    {
        try {
            $forms = $this->findByUserId($userId);
            foreach ($forms as $form) {
                $this->delete($form->getId());
            }
            return true;
        } catch (\Exception $e) {
            error_log("Failed to delete all forms for user: " . $e->getMessage());
            return false;
        }
    }

    private function itemToForm(array $item): Form
    {
        return new Form(
            $item['FormId']['S'],
            $item['UserId']['S'],
            $item['FormType']['S'],
            json_decode($item['FormData']['S'], true),
            $item['CompletionStatus']['S'],
            $item['Title']['S'] ?? null,
            $item['CreatedAt']['S'],
            $item['UpdatedAt']['S']
        );
    }
}
