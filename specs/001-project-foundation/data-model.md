# Data Model: Reference Data (Feelings & Needs)

**Feature**: 001-project-foundation
**Date**: 2026-06-07
**Phase**: Phase 1 - Design

## Overview

UPapp uses static JSON files to store reference data for feelings (uczucia) and needs (potrzeby) used in NVC forms. This reference data is read-only and serves as lookup lists for dropdown components.

---

## Entities

### Feeling

Represents an emotion or feeling used in NVC practice, categorized as fulfilled (zaspokojenie) or unfulfilled (niezaspokojenie).

#### Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name_pl` | string | ✅ | Polish name of the feeling (e.g., "współczucie") |
| `category` | enum | ✅ | Category: "fulfilled" or "unfulfilled" |
| `subcategory` | string | ✅ | UI grouping (e.g., "Czułość", "Radość", "Gniew") |
| `sort_order` | number | ✅ | Sort position within subcategory |

#### Example
```json
{
  "name_pl": "współczucie",
  "category": "fulfilled",
  "subcategory": "Czułość",
  "sort_order": 1
}
```

#### Storage
- **File**: `data/lista_uczuc.json`
- **Format**: JSON array of Feeling objects
- **Size**: ~141 feelings (~13KB)

#### Validation Rules
- `name_pl` must be unique across all feelings
- `category` must be "fulfilled" or "unfulfilled"
- `subcategory` must not be empty
- `sort_order` must be positive integer

---

### Need

Represents a human need in NVC framework, grouped by category for UI organization.

#### Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name_pl` | string | ✅ | Polish name of the need (e.g., "wolność") |
| `category` | string | ✅ | UI grouping (e.g., "Autonomia", "Współzależność") |
| `sort_order` | number | ✅ | Sort position within category |

#### Example
```json
{
  "name_pl": "wolność",
  "category": "Autonomia",
  "sort_order": 1
}
```

#### Storage
- **File**: `data/lista_potrzeb.json`
- **Format**: JSON array of Need objects
- **Size**: ~80 needs (~8KB)

#### Validation Rules
- `name_pl` must be unique across all needs
- `category` must not be empty
- `sort_order` must be positive integer

---

## API Response Structures

### Feelings Grouped Response

Backend transforms flat array into nested structure grouped by category and subcategory.

```typescript
interface FeelingsGroupedResponse {
  fulfilled: {
    [subcategory: string]: Array<{
      id: string;        // Uses name_pl as ID
      name_pl: string;
    }>;
  };
  unfulfilled: {
    [subcategory: string]: Array<{
      id: string;        // Uses name_pl as ID
      name_pl: string;
    }>;
  };
}
```

**Example**:
```json
{
  "fulfilled": {
    "Czułość": [
      { "id": "współczucie", "name_pl": "współczucie" },
      { "id": "serdeczność", "name_pl": "serdeczność" }
    ],
    "Radość": [
      { "id": "entuzjazm", "name_pl": "entuzjazm" }
    ]
  },
  "unfulfilled": {
    "Gniew": [
      { "id": "frustracja", "name_pl": "frustracja" }
    ],
    "Smutek": [
      { "id": "przygnębienie", "name_pl": "przygnębienie" }
    ]
  }
}
```

### Needs Grouped Response

Backend transforms flat array into nested structure grouped by category.

```typescript
interface NeedsGroupedResponse {
  [category: string]: Array<{
    id: string;        // Uses name_pl as ID
    name_pl: string;
  }>;
}
```

**Example**:
```json
{
  "Autonomia": [
    { "id": "wolność", "name_pl": "wolność" },
    { "id": "niezależność", "name_pl": "niezależność" }
  ],
  "Współzależność": [
    { "id": "akceptacja", "name_pl": "akceptacja" },
    { "id": "wsparcie", "name_pl": "wsparcie" }
  ]
}
```

---

## Data Relationships

### Form → Feelings/Needs

Forms (DUP, TUP, DOS, OK10) reference feelings and needs by `name_pl` (used as ID).

```
┌─────────────────┐
│  DUPForm        │
├─────────────────┤
│ fulfilled_      │───┐
│  feelings_      │   │
│  selected: []   │   │  References feelings by name_pl
│                 │   │
│ unfulfilled_    │───┤
│  feelings_      │   │
│  selected: []   │   │
│                 │   │
│ needs_          │───┼───┐
│  selected: []   │   │   │  References needs by name_pl
└─────────────────┘   │   │
                      ▼   ▼
            ┌──────────────────┐  ┌──────────────────┐
            │  Feeling Entity  │  │   Need Entity    │
            ├──────────────────┤  ├──────────────────┤
            │ name_pl (PK)     │  │ name_pl (PK)     │
            │ category         │  │ category         │
            │ subcategory      │  │ sort_order       │
            │ sort_order       │  └──────────────────┘
            └──────────────────┘
```

### Storage Pattern

**Static Reference Data** (feelings/needs):
- Stored in JSON files (`data/` directory)
- Deployed with application code
- Read-only (no writes from application)
- Updated via code commits

**User Form Data** (form submissions):
- Stored in DynamoDB tables (`UpApp.<ENV>.forms`)
- Selected feelings/needs stored as arrays of `name_pl` strings
- Writable by authenticated users

---

## Implementation Notes

### ID Strategy

**Current**: Uses `name_pl` as ID
- **Pro**: No UUID generation needed
- **Pro**: Human-readable IDs in form data
- **Con**: Changing feeling/need name breaks references
- **Con**: No internationalization support (Polish only)

**Future**: Consider UUID-based IDs if multilingual support needed

### Data Integrity

**Orphaned References**: Form data may reference feelings/needs that no longer exist if reference data is updated.

**Mitigation**:
1. Never delete feelings/needs, only add new ones
2. If deletion required, migrate form data first
3. Add validation to warn about orphaned references

### Performance

**Load Time**:
- Feelings: 13KB → ~50ms read + parse
- Needs: 8KB → ~30ms read + parse
- Total: <100ms (acceptable for reference data)

**Caching**:
- Backend reads files on every request (no cache)
- Frontend caches in memory during form lifecycle
- Consider backend caching if performance degrades

---

## Migration Strategy

### Adding New Feelings/Needs

1. Edit `data/lista_uczuc.json` or `data/lista_potrzeb.json`
2. Add new entry with unique `name_pl` and appropriate `sort_order`
3. Commit changes to repository
4. Deploy to production

### Renaming Feelings/Needs

⚠️ **Breaking Change** - requires data migration

1. Update reference data file with new name
2. Write migration script to update form data in DynamoDB
3. Run migration in dev → uat → prod sequence
4. Verify no orphaned references

### Removing Feelings/Needs

⚠️ **Breaking Change** - requires data migration

**Recommended**: Do not remove, mark as deprecated instead

If removal required:
1. Identify forms referencing the feeling/need (query DynamoDB)
2. Update or archive affected forms
3. Remove entry from reference data file
4. Deploy changes

---

**Version**: 1.0.0 | **Created**: 2026-06-07 | **Status**: Complete

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
