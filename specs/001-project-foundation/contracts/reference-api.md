# Reference Data API Contracts

**Feature**: 001-project-foundation
**Date**: 2026-06-07

## Endpoints

### GET /api/reference/feelings

Retrieve all feelings grouped by category (fulfilled/unfulfilled) and subcategory.

**Authentication**: Not required (public reference data)

**Request**: None

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "fulfilled": {
      "Czułość": [
        { "id": "współczucie", "name_pl": "współczucie" },
        { "id": "serdeczność", "name_pl": "serdeczność" },
        { "id": "otwartość", "name_pl": "otwartość" }
      ],
      "Radość": [
        { "id": "entuzjazm", "name_pl": "entuzjazm" },
        { "id": "optymizm", "name_pl": "optymizm" }
      ]
    },
    "unfulfilled": {
      "Gniew": [
        { "id": "frustracja", "name_pl": "frustracja" },
        { "id": "irytacja", "name_pl": "irytacja" }
      ],
      "Smutek": [
        { "id": "przygnębienie", "name_pl": "przygnębienie" },
        { "id": "rozpacz", "name_pl": "rozpacz" }
      ]
    }
  },
  "error": null
}
```

**Response** (404 Not Found):
```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "REFERENCE_ERROR",
    "message": "Feelings data not found"
  }
}
```

**Response** (500 Internal Server Error):
```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "REFERENCE_ERROR",
    "message": "Failed to load feelings data"
  }
}
```

---

### GET /api/reference/needs

Retrieve all needs grouped by category.

**Authentication**: Not required (public reference data)

**Request**: None

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "Autonomia": [
      { "id": "wolność", "name_pl": "wolność" },
      { "id": "niezależność", "name_pl": "niezależność" },
      { "id": "wybór", "name_pl": "wybór" }
    ],
    "Współzależność": [
      { "id": "akceptacja", "name_pl": "akceptacja" },
      { "id": "wsparcie", "name_pl": "wsparcie" },
      { "id": "szacunek", "name_pl": "szacunek" }
    ],
    "Spełnienie": [
      { "id": "świętowanie", "name_pl": "świętowanie" },
      { "id": "sens", "name_pl": "sens" }
    ]
  },
  "error": null
}
```

**Response** (404 Not Found):
```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "REFERENCE_ERROR",
    "message": "Needs data not found"
  }
}
```

**Response** (500 Internal Server Error):
```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "REFERENCE_ERROR",
    "message": "Failed to load needs data"
  }
}
```

---

## TypeScript Types

```typescript
interface Feeling {
  id: string;
  name_pl: string;
}

interface Need {
  id: string;
  name_pl: string;
}

interface FeelingsData {
  fulfilled: Record<string, Feeling[]>;   // Subcategory → Feelings
  unfulfilled: Record<string, Feeling[]>; // Subcategory → Feelings
}

interface NeedsData {
  [category: string]: Need[];  // Category → Needs
}

interface APIResponse<T> {
  success: boolean;
  data: T | null;
  error: {
    code: string;
    message: string;
  } | null;
}
```

---

## CORS Headers

All endpoints must include:
```
Access-Control-Allow-Origin: <requesting-origin>
Access-Control-Allow-Credentials: true
Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
```

Allowed origins:
- `http://localhost:5173` (dev)
- `http://localhost:5174` (uat local)
- `http://localhost:5175` (dev alternate)
- `http://localhost:3000` (dev alternate)
- `https://przetargr-domow.pl` (production)
- Value from `APP_URL` environment variable (fallback)

---

**Version**: 1.0.0 | **Created**: 2026-06-07
