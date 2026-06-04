#!/usr/bin/env pwsh

$ErrorActionPreference = 'Stop'

# Load environment
if (Test-Path .env) {
    Get-Content .env | ForEach-Object {
        if ($_ -match '^([^#][^=]+)=(.*)$') {
            $name = $matches[1].Trim()
            $rawValue = $matches[2]
            # Strip inline comments (everything after #)
            if ($rawValue -match '^([^#]*)') {
                $value = $matches[1].Trim()
                if ($value) {
                    [Environment]::SetEnvironmentVariable($name, $value, 'Process')
                }
            }
        }
    }
} else {
    Write-Error ".env file not found"
    exit 1
}

$APP_ENV = if ($env:APP_ENV) { $env:APP_ENV } else { 'dev' }
$REGION = if ($env:AWS_REGION) { $env:AWS_REGION } else { 'eu-central-1' }
$PREFIX = if ($env:DYNAMODB_TABLE_PREFIX) { $env:DYNAMODB_TABLE_PREFIX } else { 'UpApp' }

Write-Host "[db:seed] Environment: $APP_ENV"
Write-Host "[db:seed] Region: $REGION"

function Seed-DynamoDBTable {
    param(
        [string]$TableName,
        [string]$JsonFile
    )

    Write-Host "[db:seed] Seeding table: $TableName"

    if (-not (Test-Path $JsonFile)) {
        Write-Error "Seed file not found: $JsonFile"
        return
    }

    $items = Get-Content $JsonFile | ConvertFrom-Json
    $count = 0

    foreach ($item in $items) {
        $dynamoItem = @{}
        foreach ($prop in $item.PSObject.Properties) {
            $dynamoItem[$prop.Name] = @{ S = $prop.Value.ToString() }
        }

        $itemJson = $dynamoItem | ConvertTo-Json -Compress -Depth 10
        $tempFile = [System.IO.Path]::GetTempFileName()
        $itemJson | Out-File -FilePath $tempFile -Encoding utf8 -NoNewline

        Write-Host "[DEBUG] Temp file: $tempFile"
        Write-Host "[DEBUG] Content: $(Get-Content $tempFile)"

        try {
            $result = & aws dynamodb put-item --table-name $TableName --item "file://$tempFile" --region $REGION --return-values NONE 2>&1
            Write-Host "[DEBUG] AWS result: $result"
            Write-Host "[DEBUG] Exit code: $LASTEXITCODE"

            if ($LASTEXITCODE -eq 0) {
                $count++
            } else {
                Write-Host "[ERROR] Failed to insert item. Exit code: $LASTEXITCODE, Error: $result"
            }
        } finally {
            Remove-Item $tempFile -ErrorAction SilentlyContinue
        }
    }

    Write-Host "[db:seed] OK Inserted $count records"
}

# Seed config table
$TABLE_NAME = "$PREFIX.$APP_ENV.config"
Seed-DynamoDBTable -TableName $TABLE_NAME -JsonFile "data/seed/config.json"

Write-Host "[db:seed] Seeding complete"
