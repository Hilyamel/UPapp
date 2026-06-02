#!/usr/bin/env pwsh

$ErrorActionPreference = 'Stop'

# Load environment
if (Test-Path .env) {
    Get-Content .env | ForEach-Object {
        if ($_ -match '^([^#][^=]+)=(.*)$') {
            $name = $matches[1].Trim()
            $value = $matches[2].Trim()
            [Environment]::SetEnvironmentVariable($name, $value, 'Process')
        }
    }
} else {
    Write-Error ".env file not found"
    exit 1
}

$APP_ENV = if ($env:APP_ENV) { $env:APP_ENV } else { 'dev' }
$REGION = if ($env:AWS_REGION) { $env:AWS_REGION } else { 'eu-central-1' }
$PREFIX = if ($env:DYNAMODB_TABLE_PREFIX) { $env:DYNAMODB_TABLE_PREFIX } else { 'UpApp' }

Write-Host "[db:sync] Environment: $APP_ENV"
Write-Host "[db:sync] Region: $REGION"

function Create-DynamoDBTable {
    param(
        [string]$TableName,
        [string]$PKName,
        [string]$PKType
    )

    Write-Host "[db:sync] Creating table: $TableName"

    try {
        $existing = aws dynamodb describe-table --table-name $TableName --region $REGION 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "[db:sync] ✓ Table $TableName already exists"
            return
        }
    } catch {
        # Table doesn't exist, continue to create
    }

    aws dynamodb create-table `
        --table-name $TableName `
        --attribute-definitions AttributeName=$PKName,AttributeType=$PKType `
        --key-schema AttributeName=$PKName,KeyType=HASH `
        --billing-mode PAY_PER_REQUEST `
        --region $REGION

    if ($LASTEXITCODE -eq 0) {
        Write-Host "[db:sync] ✓ Table $TableName created successfully"
    } else {
        Write-Error "Failed to create table $TableName"
    }
}

# Create config table
$TABLE_NAME = "$PREFIX.$APP_ENV.config"
Create-DynamoDBTable -TableName $TABLE_NAME -PKName "ConfigKey" -PKType "S"

Write-Host "[db:sync] All tables synchronized"
