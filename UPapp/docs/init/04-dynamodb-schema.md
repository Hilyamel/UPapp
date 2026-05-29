# Phase 2: DynamoDB Schema & Table Creation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create and configure all DynamoDB tables with proper indexes, TTL, and seed reference data

**Architecture:** Five DynamoDB tables with GSIs for efficient querying, TTL for magic links auto-expiry, on-demand billing

**Tech Stack:** AWS CLI, AWS DynamoDB, PHP (for seeding), Bash scripting

---

## AWS Configuration

### Task 1: Configure AWS CLI

**Files:**
- None (AWS credentials configuration)

- [ ] **Step 1: Check AWS CLI installed**

Run: `aws --version`
Expected: aws-cli/2.x.x

- [ ] **Step 2: Configure AWS credentials**

```bash
aws configure
```

Prompts will ask for:
- AWS Access Key ID: (enter your key)
- AWS Secret Access Key: (enter your secret)
- Default region: eu-central-1
- Default output format: json

- [ ] **Step 3: Test AWS connection**

Run: `aws sts get-caller-identity`
Expected: JSON with your Account, UserId, Arn

- [ ] **Step 4: Verify permissions**

```bash
aws dynamodb list-tables --region eu-central-1
```

Expected: JSON response (may be empty list if no tables exist yet)

- [ ] **Step 5: Update .env with AWS config**

Edit `.env` and verify:
```env
AWS_REGION=eu-central-1
AWS_ACCESS_KEY_ID=<your-key-or-leave-empty-if-using-aws-cli>
AWS_SECRET_ACCESS_KEY=<your-secret-or-leave-empty>
DYNAMODB_TABLE_PREFIX=UpApp.dev
```

Run: `grep DYNAMODB_TABLE_PREFIX .env`
Expected: DYNAMODB_TABLE_PREFIX=UpApp.dev

---

## Table Creation Scripts

### Task 2: Create Users Table

**Files:**
- Create: `scripts/dynamodb/create-users-table.sh`

- [ ] **Step 1: Write Users table creation script**

```bash
#!/bin/bash

ENV=${1:-dev}
PREFIX="UpApp.${ENV}"
TABLE_NAME="${PREFIX}.Users"

echo "Creating ${TABLE_NAME}..."

aws dynamodb create-table \
  --table-name "${TABLE_NAME}" \
  --attribute-definitions \
    AttributeName=PK,AttributeType=S \
    AttributeName=email,AttributeType=S \
    AttributeName=isAdmin,AttributeType=S \
    AttributeName=createdAt,AttributeType=S \
  --key-schema \
    AttributeName=PK,KeyType=HASH \
  --global-secondary-indexes \
    "IndexName=EmailIndex,KeySchema=[{AttributeName=email,KeyType=HASH}],Projection={ProjectionType=ALL},ProvisionedThroughput={ReadCapacityUnits=5,WriteCapacityUnits=5}" \
    "IndexName=AdminIndex,KeySchema=[{AttributeName=isAdmin,KeyType=HASH},{AttributeName=createdAt,KeyType=RANGE}],Projection={ProjectionType=ALL},ProvisionedThroughput={ReadCapacityUnits=5,WriteCapacityUnits=5}" \
  --provisioned-throughput \
    ReadCapacityUnits=5,WriteCapacityUnits=5 \
  --region eu-central-1

echo "✓ Created ${TABLE_NAME}"

# Wait for table to become active
echo "Waiting for table to become ACTIVE..."
aws dynamodb wait table-exists --table-name "${TABLE_NAME}" --region eu-central-1
echo "✓ ${TABLE_NAME} is ACTIVE"
```

Write to: `scripts/dynamodb/create-users-table.sh`

Run: `test -f scripts/dynamodb/create-users-table.sh && echo "Script created"`
Expected: "Script created"

- [ ] **Step 2: Make script executable**

```bash
chmod +x scripts/dynamodb/create-users-table.sh
```

Run: `ls -la scripts/dynamodb/create-users-table.sh | grep -o 'x'`
Expected: Shows 'x' (executable)

- [ ] **Step 3: Run Users table creation**

```bash
bash scripts/dynamodb/create-users-table.sh dev
```

Expected: "✓ Created UpApp.dev.Users" and "✓ UpApp.dev.Users is ACTIVE"

- [ ] **Step 4: Verify table created**

```bash
aws dynamodb describe-table \
  --table-name UpApp.dev.Users \
  --region eu-central-1 \
  --query 'Table.[TableName,TableStatus]' \
  --output text
```

Expected: UpApp.dev.Users    ACTIVE

- [ ] **Step 5: Commit Users table script**

```bash
git add scripts/dynamodb/create-users-table.sh
git commit -m "feat: add Users table creation script

- Create UpApp.{env}.Users table with DynamoDB
- Add EmailIndex GSI for login lookups
- Add AdminIndex GSI for admin queries
- Wait for table to become ACTIVE

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

Run: `git log --oneline -1`
Expected: Shows commit about Users table

---

### Task 3: Create FormSubmissions Table

**Files:**
- Create: `scripts/dynamodb/create-forms-table.sh`

- [ ] **Step 1: Write FormSubmissions creation script**

```bash
#!/bin/bash

ENV=${1:-dev}
PREFIX="UpApp.${ENV}"
TABLE_NAME="${PREFIX}.FormSubmissions"

echo "Creating ${TABLE_NAME}..."

aws dynamodb create-table \
  --table-name "${TABLE_NAME}" \
  --attribute-definitions \
    AttributeName=PK,AttributeType=S \
    AttributeName=SK,AttributeType=S \
    AttributeName=userId,AttributeType=S \
    AttributeName=formTypeUpdated,AttributeType=S \
    AttributeName=statusUpdated,AttributeType=S \
  --key-schema \
    AttributeName=PK,KeyType=HASH \
    AttributeName=SK,KeyType=RANGE \
  --global-secondary-indexes \
    "IndexName=FormTypeIndex,KeySchema=[{AttributeName=userId,KeyType=HASH},{AttributeName=formTypeUpdated,KeyType=RANGE}],Projection={ProjectionType=ALL},ProvisionedThroughput={ReadCapacityUnits=5,WriteCapacityUnits=5}" \
    "IndexName=StatusIndex,KeySchema=[{AttributeName=userId,KeyType=HASH},{AttributeName=statusUpdated,KeyType=RANGE}],Projection={ProjectionType=ALL},ProvisionedThroughput={ReadCapacityUnits=5,WriteCapacityUnits=5}" \
  --provisioned-throughput \
    ReadCapacityUnits=5,WriteCapacityUnits=5 \
  --region eu-central-1

echo "✓ Created ${TABLE_NAME}"

echo "Waiting for table to become ACTIVE..."
aws dynamodb wait table-exists --table-name "${TABLE_NAME}" --region eu-central-1
echo "✓ ${TABLE_NAME} is ACTIVE"
```

Write to: `scripts/dynamodb/create-forms-table.sh`

- [ ] **Step 2: Make executable and run**

```bash
chmod +x scripts/dynamodb/create-forms-table.sh
bash scripts/dynamodb/create-forms-table.sh dev
```

Expected: "✓ Created UpApp.dev.FormSubmissions" and ACTIVE

- [ ] **Step 3: Verify table and GSIs**

```bash
aws dynamodb describe-table \
  --table-name UpApp.dev.FormSubmissions \
  --region eu-central-1 \
  --query 'Table.GlobalSecondaryIndexes[*].IndexName' \
  --output text
```

Expected: FormTypeIndex    StatusIndex

- [ ] **Step 4: Commit FormSubmissions script**

```bash
git add scripts/dynamodb/create-forms-table.sh
git commit -m "feat: add FormSubmissions table creation

- Create table with composite sort key (PK=userId, SK=formId)
- Add FormTypeIndex for filtering by form type
- Add StatusIndex for draft vs completed queries

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

Run: `git log --oneline -1`
Expected: Commit about FormSubmissions

---

### Task 4: Create MagicLinks Table with TTL

**Files:**
- Create: `scripts/dynamodb/create-magiclinks-table.sh`

- [ ] **Step 1: Write MagicLinks creation script**

```bash
#!/bin/bash

ENV=${1:-dev}
PREFIX="UpApp.${ENV}"
TABLE_NAME="${PREFIX}.MagicLinks"

echo "Creating ${TABLE_NAME}..."

aws dynamodb create-table \
  --table-name "${TABLE_NAME}" \
  --attribute-definitions \
    AttributeName=PK,AttributeType=S \
  --key-schema \
    AttributeName=PK,KeyType=HASH \
  --provisioned-throughput \
    ReadCapacityUnits=5,WriteCapacityUnits=5 \
  --region eu-central-1

echo "✓ Created ${TABLE_NAME}"

echo "Waiting for table to become ACTIVE..."
aws dynamodb wait table-exists --table-name "${TABLE_NAME}" --region eu-central-1

echo "Enabling TTL on ${TABLE_NAME}..."
aws dynamodb update-time-to-live \
  --table-name "${TABLE_NAME}" \
  --time-to-live-specification "Enabled=true, AttributeName=TTL" \
  --region eu-central-1

echo "✓ ${TABLE_NAME} is ACTIVE with TTL enabled"
```

Write to: `scripts/dynamodb/create-magiclinks-table.sh`

- [ ] **Step 2: Make executable and run**

```bash
chmod +x scripts/dynamodb/create-magiclinks-table.sh
bash scripts/dynamodb/create-magiclinks-table.sh dev
```

Expected: Shows table created and "TTL enabled"

- [ ] **Step 3: Verify TTL enabled**

```bash
aws dynamodb describe-time-to-live \
  --table-name UpApp.dev.MagicLinks \
  --region eu-central-1 \
  --query 'TimeToLiveDescription.TimeToLiveStatus' \
  --output text
```

Expected: ENABLED or ENABLING

- [ ] **Step 4: Commit MagicLinks script**

```bash
git add scripts/dynamodb/create-magiclinks-table.sh
git commit -m "feat: add MagicLinks table with TTL

- Create table for temporary authentication links
- Enable TTL on TTL attribute for auto-expiry
- Links auto-delete after 15 minutes

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

---

### Task 5: Create Reference Tables (Feelings & Needs)

**Files:**
- Create: `scripts/dynamodb/create-reference-tables.sh`

- [ ] **Step 1: Write reference tables script**

```bash
#!/bin/bash

ENV=${1:-dev}
PREFIX="UpApp.${ENV}"

# Create Feelings table
echo "Creating ${PREFIX}.Feelings..."
aws dynamodb create-table \
  --table-name "${PREFIX}.Feelings" \
  --attribute-definitions \
    AttributeName=PK,AttributeType=S \
    AttributeName=category,AttributeType=S \
    AttributeName=sortOrder,AttributeType=N \
  --key-schema \
    AttributeName=PK,KeyType=HASH \
  --global-secondary-indexes \
    "IndexName=CategoryIndex,KeySchema=[{AttributeName=category,KeyType=HASH},{AttributeName=sortOrder,KeyType=RANGE}],Projection={ProjectionType=ALL},ProvisionedThroughput={ReadCapacityUnits=5,WriteCapacityUnits=5}" \
  --provisioned-throughput \
    ReadCapacityUnits=5,WriteCapacityUnits=1 \
  --region eu-central-1

echo "✓ Created ${PREFIX}.Feelings"

# Create Needs table
echo "Creating ${PREFIX}.Needs..."
aws dynamodb create-table \
  --table-name "${PREFIX}.Needs" \
  --attribute-definitions \
    AttributeName=PK,AttributeType=S \
    AttributeName=category,AttributeType=S \
    AttributeName=sortOrder,AttributeType=N \
  --key-schema \
    AttributeName=PK,KeyType=HASH \
  --global-secondary-indexes \
    "IndexName=CategoryIndex,KeySchema=[{AttributeName=category,KeyType=HASH},{AttributeName=sortOrder,KeyType=RANGE}],Projection={ProjectionType=ALL},ProvisionedThroughput={ReadCapacityUnits=5,WriteCapacityUnits=5}" \
  --provisioned-throughput \
    ReadCapacityUnits=5,WriteCapacityUnits=1 \
  --region eu-central-1

echo "✓ Created ${PREFIX}.Needs"

# Wait for both tables
echo "Waiting for tables to become ACTIVE..."
aws dynamodb wait table-exists --table-name "${PREFIX}.Feelings" --region eu-central-1
aws dynamodb wait table-exists --table-name "${PREFIX}.Needs" --region eu-central-1

echo "✓ All reference tables ACTIVE"
```

Write to: `scripts/dynamodb/create-reference-tables.sh`

- [ ] **Step 2: Make executable and run**

```bash
chmod +x scripts/dynamodb/create-reference-tables.sh
bash scripts/dynamodb/create-reference-tables.sh dev
```

Expected: Both tables created and ACTIVE

- [ ] **Step 3: Verify both tables exist**

```bash
aws dynamodb list-tables --region eu-central-1 | grep -E '(Feelings|Needs)'
```

Expected: Shows UpApp.dev.Feelings and UpApp.dev.Needs

- [ ] **Step 4: Commit reference tables script**

```bash
git add scripts/dynamodb/create-reference-tables.sh
git commit -m "feat: add reference data tables

- Create Feelings table with CategoryIndex
- Create Needs table with CategoryIndex
- Low write capacity (data rarely changes)

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

---

## Data Seeding

### Task 6: Create Seeding Script

**Files:**
- Create: `scripts/dynamodb/seed-data.php`

- [ ] **Step 1: Write PHP seeding script**

```php
<?php
require __DIR__ . '/../../backend/vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient;

// Get environment from command line or use dev
$env = $argv[1] ?? 'dev';
$prefix = "UpApp.{$env}";

echo "Seeding reference data for environment: {$prefix}\n\n";

// Initialize DynamoDB client
$dynamodb = new DynamoDbClient([
    'region' => 'eu-central-1',
    'version' => 'latest',
]);

// Seed Feelings
echo "Seeding Feelings...\n";
$feelingsFile = __DIR__ . '/../../data/lista_uczuc.json';

if (!file_exists($feelingsFile)) {
    die("Error: {$feelingsFile} not found\n");
}

$feelingsData = json_decode(file_get_contents($feelingsFile), true);
$feelingsCount = 0;

foreach ($feelingsData as $idx => $feeling) {
    $item = [
        'PK' => ['S' => "FEELING#" . str_pad($idx, 3, '0', STR_PAD_LEFT)],
        'feelingId' => ['S' => (string)$idx],
        'namePl' => ['S' => $feeling['name_pl']],
        'category' => ['S' => $feeling['category']],
        'subcategory' => ['S' => $feeling['subcategory'] ?? ''],
        'sortOrder' => ['N' => (string)($feeling['sort_order'] ?? $idx)],
    ];

    try {
        $dynamodb->putItem([
            'TableName' => "{$prefix}.Feelings",
            'Item' => $item,
        ]);
        $feelingsCount++;
    } catch (Exception $e) {
        echo "Error inserting feeling #{$idx}: " . $e->getMessage() . "\n";
    }
}

echo "✓ Inserted {$feelingsCount} feelings\n\n";

// Seed Needs
echo "Seeding Needs...\n";
$needsFile = __DIR__ . '/../../data/lista_potrzeb.json';

if (!file_exists($needsFile)) {
    die("Error: {$needsFile} not found\n");
}

$needsData = json_decode(file_get_contents($needsFile), true);
$needsCount = 0;

foreach ($needsData as $idx => $need) {
    $item = [
        'PK' => ['S' => "NEED#" . str_pad($idx, 3, '0', STR_PAD_LEFT)],
        'needId' => ['S' => (string)$idx],
        'namePl' => ['S' => $need['name_pl']],
        'category' => ['S' => $need['category']],
        'sortOrder' => ['N' => (string)($need['sort_order'] ?? $idx)],
    ];

    try {
        $dynamodb->putItem([
            'TableName' => "{$prefix}.Needs",
            'Item' => $item,
        ]);
        $needsCount++;
    } catch (Exception $e) {
        echo "Error inserting need #{$idx}: " . $e->getMessage() . "\n";
    }
}

echo "✓ Inserted {$needsCount} needs\n\n";

echo "✓ Reference data seeding complete!\n";
echo "Total: {$feelingsCount} feelings + {$needsCount} needs\n";
```

Write to: `scripts/dynamodb/seed-data.php`

- [ ] **Step 2: Make script executable**

```bash
chmod +x scripts/dynamodb/seed-data.php
```

- [ ] **Step 3: Install AWS SDK for PHP (needed for seeding)**

```bash
cd backend
composer init --name="upapp/backend" --type=project --no-interaction
composer require aws/aws-sdk-php:^3.296
cd ..
```

Expected: composer.json created with aws-sdk-php

- [ ] **Step 4: Run seeding script**

```bash
php scripts/dynamodb/seed-data.php dev
```

Expected: "✓ Inserted 3 feelings" and "✓ Inserted 3 needs"

- [ ] **Step 5: Verify data was seeded**

```bash
aws dynamodb scan \
  --table-name UpApp.dev.Feelings \
  --max-items 2 \
  --region eu-central-1 \
  --query 'Items[*].namePl.S' \
  --output text
```

Expected: Shows feeling names like "radosny zadowolony"

- [ ] **Step 6: Commit seeding script and composer files**

```bash
git add scripts/dynamodb/seed-data.php backend/composer.json backend/composer.lock
git commit -m "feat: add reference data seeding script

- Create PHP script to seed Feelings and Needs
- Use AWS SDK to insert data into DynamoDB
- Support environment parameter (dev/uat/prod)
- Add aws-sdk-php dependency

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

---

## Verification & Testing

### Task 7: Create All-in-One Table Creation Script

**Files:**
- Create: `scripts/dynamodb/create-tables.sh`

- [ ] **Step 1: Write master creation script**

```bash
#!/bin/bash

# Usage: ./create-tables.sh [dev|uat|prod]
# Example: ./create-tables.sh dev

ENV=${1:-dev}
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "================================"
echo "Creating all DynamoDB tables for: ${ENV}"
echo "================================"
echo ""

# Create all tables
bash "${SCRIPT_DIR}/create-users-table.sh" ${ENV}
echo ""

bash "${SCRIPT_DIR}/create-forms-table.sh" ${ENV}
echo ""

bash "${SCRIPT_DIR}/create-magiclinks-table.sh" ${ENV}
echo ""

bash "${SCRIPT_DIR}/create-reference-tables.sh" ${ENV}
echo ""

echo "================================"
echo "✓ All tables created for: ${ENV}"
echo "================================"
echo ""
echo "Next step: Run seeding script"
echo "  php scripts/dynamodb/seed-data.php ${ENV}"
```

Write to: `scripts/dynamodb/create-tables.sh`

- [ ] **Step 2: Make executable**

```bash
chmod +x scripts/dynamodb/create-tables.sh
```

- [ ] **Step 3: Test listing all tables**

```bash
aws dynamodb list-tables --region eu-central-1 --query 'TableNames' --output json
```

Expected: JSON array with all 5 UpApp.dev.* tables

- [ ] **Step 4: Count items in reference tables**

```bash
aws dynamodb scan --table-name UpApp.dev.Feelings --select "COUNT" --region eu-central-1 --query 'Count'
aws dynamodb scan --table-name UpApp.dev.Needs --select "COUNT" --region eu-central-1 --query 'Count'
```

Expected: Count of 3 for each table

- [ ] **Step 5: Commit master script**

```bash
git add scripts/dynamodb/create-tables.sh
git commit -m "feat: add master table creation script

- Create all 5 DynamoDB tables in one command
- Call individual table scripts
- Support environment parameter

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

---

### Task 8: Verify Complete Database Setup

**Files:**
- Create: `scripts/dynamodb/verify-tables.sh`

- [ ] **Step 1: Write verification script**

```bash
#!/bin/bash

ENV=${1:-dev}
PREFIX="UpApp.${ENV}"

echo "Verifying DynamoDB setup for: ${PREFIX}"
echo ""

TABLES=(
  "Users"
  "FormSubmissions"
  "MagicLinks"
  "Feelings"
  "Needs"
)

ALL_OK=true

for TABLE in "${TABLES[@]}"; do
  TABLE_NAME="${PREFIX}.${TABLE}"
  echo -n "Checking ${TABLE_NAME}... "
  
  STATUS=$(aws dynamodb describe-table \
    --table-name "${TABLE_NAME}" \
    --region eu-central-1 \
    --query 'Table.TableStatus' \
    --output text 2>/dev/null)
  
  if [ "$STATUS" = "ACTIVE" ]; then
    echo "✓ ACTIVE"
  else
    echo "✗ FAILED (Status: ${STATUS})"
    ALL_OK=false
  fi
done

echo ""

if [ "$ALL_OK" = true ]; then
  echo "✓ All tables verified successfully"
  exit 0
else
  echo "✗ Some tables failed verification"
  exit 1
fi
```

Write to: `scripts/dynamodb/verify-tables.sh`

- [ ] **Step 2: Make executable and run**

```bash
chmod +x scripts/dynamodb/verify-tables.sh
bash scripts/dynamodb/verify-tables.sh dev
```

Expected: All tables show "✓ ACTIVE"

- [ ] **Step 3: Add npm script for verification**

Edit `package.json` and add to scripts:
```json
"db:verify": "bash scripts/dynamodb/verify-tables.sh dev"
```

Run: `npm run db:verify`
Expected: All tables ACTIVE

- [ ] **Step 4: Commit verification script**

```bash
git add scripts/dynamodb/verify-tables.sh package.json
git commit -m "feat: add database verification script

- Check all tables exist and are ACTIVE
- Support environment parameter
- Add npm script for easy verification

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

- [ ] **Step 5: Tag Phase 2 complete**

```bash
git tag phase-2-complete
git push --tags 2>/dev/null || echo "Tag created locally"
```

Expected: Tag created

---

## Database Schema Reference

This section documents table schemas for reference (not executable).

### Table: UpApp.{env}.Users

**Primary Key:** PK (String, Partition Key): `USER#{userId}`

**Attributes:**
- userId: UUID
- email: Email address
- authProvider: "google" | "magiclink"
- googleId: Optional Google OAuth ID
- isAdmin: Boolean
- isActive: Boolean
- createdAt, updatedAt, lastLoginAt: ISO 8601 timestamps

**GSIs:**
- EmailIndex: email (PK)
- AdminIndex: isAdmin (PK), createdAt (SK)

### Table: UpApp.{env}.FormSubmissions

**Primary Key:** 
- PK: `USER#{userId}`
- SK: `FORM#{formId}`

**Attributes:**
- formId, userId: UUIDs
- formType: "DUP" | "TUP" | "DOS"
- formData: Map
- completionStatus: "draft" | "completed"
- title, aiFeedback: Optional strings
- createdAt, updatedAt: Timestamps

**GSIs:**
- FormTypeIndex: userId (PK), formTypeUpdated (SK)
- StatusIndex: userId (PK), statusUpdated (SK)

### Table: UpApp.{env}.MagicLinks

**Primary Key:** PK: `MAGICLINK#{token}`

**Attributes:**
- token: 64-char hex string
- email: Email address
- createdAt, expiresAt, usedAt: Timestamps
- TTL: Unix timestamp (auto-delete)

### Table: UpApp.{env}.Feelings

**Primary Key:** PK: `FEELING#{id}`

**Attributes:**
- feelingId, namePl, category, subcategory, sortOrder

**GSI:** CategoryIndex: category (PK), sortOrder (SK)

### Table: UpApp.{env}.Needs

**Primary Key:** PK: `NEED#{id}`

**Attributes:**
- needId, namePl, category, sortOrder

**GSI:** CategoryIndex: category (PK), sortOrder (SK)

---

## Next Steps

Phase 2 complete! Database tables created and seeded. Continue with:

1. **[03-backend-setup.md](03-backend-setup.md)** - Setup PHP Slim Framework
2. **[07-environment-config.md](07-environment-config.md)** - Configure environment variables
3. **[05-authentication.md](05-authentication.md)** - Implement authentication
