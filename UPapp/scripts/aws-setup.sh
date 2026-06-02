#!/bin/bash

set -e

# Load environment
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
else
    echo "Error: .env file not found"
    exit 1
fi

APP_ENV=${APP_ENV:-dev}
REGION=${AWS_REGION:-eu-central-1}
PREFIX=${DYNAMODB_TABLE_PREFIX:-UpApp}

echo "[db:sync] Environment: $APP_ENV"
echo "[db:sync] Region: $REGION"

# Function to create table
create_table() {
    local table_name=$1
    local pk_name=$2
    local pk_type=$3

    echo "[db:sync] Creating table: $table_name"

    if aws dynamodb describe-table --table-name "$table_name" --region "$REGION" &>/dev/null; then
        echo "[db:sync] ✓ Table $table_name already exists"
        return 0
    fi

    aws dynamodb create-table \
        --table-name "$table_name" \
        --attribute-definitions AttributeName="$pk_name",AttributeType="$pk_type" \
        --key-schema AttributeName="$pk_name",KeyType=HASH \
        --billing-mode PAY_PER_REQUEST \
        --region "$REGION"

    echo "[db:sync] ✓ Table $table_name created successfully"
}

# Create config table
TABLE_NAME="${PREFIX}.${APP_ENV}.config"
create_table "$TABLE_NAME" "ConfigKey" "S"

echo "[db:sync] All tables synchronized"
