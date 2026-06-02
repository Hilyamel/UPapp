# Data Model: Project Foundation

**Created**: 2026-06-02

**Purpose**: Define initial DynamoDB table structure for project foundation. Additional tables (users, forms) will be defined in future feature specifications.

## Table Naming Convention

All DynamoDB tables follow the pattern: `UpApp.<ENV>.<tablename>`

Where:
- `ENV` = `dev`, `uat`, or `prod` (from `.env` file `APP_ENV` variable)
- `tablename` = lowercase, hyphenated descriptive name

**Examples**:
- `UpApp.dev.config`
- `UpApp.uat.config`
- `UpApp.prod.config`

## Table: Config (Environment-Specific Configuration)

**Purpose**: Store environment-specific application configuration that may change without code deployment. This table supports runtime configuration changes (e.g., feature flags, maintenance mode).

**Table Name**: `UpApp.<ENV>.config`

### Schema

| Attribute | Type | Description |
|-----------|------|-------------|
| `ConfigKey` | String (Partition Key) | Unique identifier for configuration setting (e.g., `maintenance_mode`, `feature_flags`) |
| `Value` | Map/String | Configuration value (flexible type to support strings, booleans, nested objects) |
| `LastModified` | String (ISO 8601) | Timestamp of last update (e.g., `2026-06-02T14:30:00Z`) |
| `ModifiedBy` | String | Email of user who last modified (for audit trail) |

### Access Patterns

1. **Read single config**: `GetItem` on `ConfigKey`
2. **Read all config**: `Scan` (acceptable for small config table)
3. **Update config**: `PutItem` with new `Value` and `LastModified`

### Initial Seed Data

```json
{
  "ConfigKey": "app_version",
  "Value": "1.0.0",
  "LastModified": "2026-06-02T12:00:00Z",
  "ModifiedBy": "system"
}
```

```json
{
  "ConfigKey": "maintenance_mode",
  "Value": false,
  "LastModified": "2026-06-02T12:00:00Z",
  "ModifiedBy": "system"
}
```

### DynamoDB Table Properties

- **Billing Mode**: PAY_PER_REQUEST (on-demand, suitable for low/variable traffic)
- **Point-in-Time Recovery**: Disabled initially (enable in production after stable)
- **Encryption**: AWS managed (default)
- **Stream**: None (not needed for config table)

### AWS CLI Creation Command

```bash
aws dynamodb create-table \
  --table-name UpApp.dev.config \
  --attribute-definitions AttributeName=ConfigKey,AttributeType=S \
  --key-schema AttributeName=ConfigKey,KeyType=HASH \
  --billing-mode PAY_PER_REQUEST \
  --region eu-central-1
```

---

## Future Tables (Defined in Other Features)

The following tables will be defined in subsequent feature specifications:

### Users Table (`UpApp.<ENV>.users`)
- **Defined in**: Feature `002-authentication`
- **Purpose**: Store user accounts, authentication tokens, roles
- **Partition Key**: `UserId` (UUID)
- **GSI**: `EmailIndex` (for login by email)

### Forms Table (`UpApp.<ENV>.forms`)
- **Defined in**: Feature `003-nvc-forms`
- **Purpose**: Store NVC form submissions (DUP, TUP, DOS)
- **Partition Key**: `UserId#FormType` (composite)
- **Sort Key**: `CreatedAt` (ISO 8601 timestamp)

### Reference Data Tables
- **Defined in**: Feature `003-nvc-forms`
- **Examples**: `UpApp.<ENV>.feelings`, `UpApp.<ENV>.needs`
- **Purpose**: Store NVC reference lists (feelings, needs) for form dropdowns

---

## DynamoDB Design Principles (Per Constitution)

### Partition Key Design
- **Avoid hot partitions**: Distribute writes evenly across partition key space
- **Use composite keys** when appropriate (e.g., `UserId#FormType`)
- **Never use sequential IDs** as partition key (creates hotspots)

### Sort Key Design
- **Use timestamps** for chronological sorting (ISO 8601 format)
- **Composite sort keys** for hierarchical data (e.g., `FORM#<formid>#VERSION#<version>`)

### Global Secondary Indexes (GSI)
- **Create GSI** for any access pattern that can't be satisfied by primary key
- **Projection**: Start with `KEYS_ONLY`, add attributes as needed
- **Billing**: GSI inherits table billing mode (PAY_PER_REQUEST)

### Query vs Scan
- **Prefer Query** over Scan whenever possible
- **Scan is acceptable** for small tables (<1000 items) like config table
- **Paginate large scans** using `LastEvaluatedKey`

### Single-Table Design
- **Consider single-table** if multiple entity types have overlapping access patterns
- **Project Foundation**: Multiple tables initially (simpler), evaluate consolidation later

---

## Validation Rules

### ConfigKey
- **Format**: Lowercase, underscore-separated (e.g., `feature_flags`, `maintenance_mode`)
- **Max Length**: 255 characters
- **Uniqueness**: Enforced by DynamoDB (partition key)

### Value
- **Type**: String, Number, Boolean, Map, or List
- **Validation**: Type-specific validation in application layer (not DynamoDB)

### Timestamps (LastModified)
- **Format**: ISO 8601 UTC (e.g., `2026-06-02T14:30:00Z`)
- **Timezone**: Always UTC
- **Precision**: Seconds (no milliseconds needed for config updates)

---

## Migration Strategy

**Current State**: No tables exist (fresh project)

**Initial Setup**:
1. Run `npm run db:sync` to create tables per environment
2. Tables created: `UpApp.dev.config`, `UpApp.uat.config`, `UpApp.prod.config`
3. Run `npm run db:seed` to populate initial seed data

**Future Schema Changes**:
- **Adding attributes**: No migration needed (DynamoDB schemaless)
- **Changing partition/sort key**: Requires table recreation or data migration
- **Adding GSI**: Can be added to existing table without downtime
- **Removing GSI**: Can be removed without affecting data

**Best Practices**:
- Document all schema changes in feature specification
- Test migrations in `dev` → `uat` before `prod`
- Keep `aws-setup.sh` script up to date with all table definitions
