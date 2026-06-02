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

echo "[db:seed] Environment: $APP_ENV"
echo "[db:seed] Region: $REGION"

# Function to seed table
seed_table() {
    local table_name=$1
    local json_file=$2

    echo "[db:seed] Seeding table: $table_name"

    # Check if file exists
    if [ ! -f "$json_file" ]; then
        echo "[db:seed] ✗ Seed file not found: $json_file"
        return 1
    fi

    # Read JSON and insert items
    local count=0
    while IFS= read -r item; do
        aws dynamodb put-item \
            --table-name "$table_name" \
            --item "$item" \
            --region "$REGION" \
            --return-values NONE 2>/dev/null || true
        ((count++))
    done < <(jq -c '.[]' "$json_file" | jq -c '{Item: .}' | jq -c '.Item | to_entries | map({(.key): {S: (.value | tostring)}}) | from_entries')

    echo "[db:seed] ✓ Inserted $count records"
}

# Seed config table
TABLE_NAME="${PREFIX}.${APP_ENV}.config"
seed_table "$TABLE_NAME" "data/seed/config.json"

echo "[db:seed] Seeding complete"
