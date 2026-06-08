# Error Handling Contracts

**Feature**: 001-project-foundation
**Date**: 2026-06-07

## Standard Error Response Format

All API endpoints follow consistent error response structure:

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "ERROR_CODE",
    "message": "User-friendly error message",
    "details": {
      // Optional additional information
    }
  }
}
```

---

## HTTP Status Codes

| Status Code | Usage | Example Scenarios |
|-------------|-------|-------------------|
| `200 OK` | Successful request | Data retrieved, operation completed |
| `400 Bad Request` | Client error - invalid input | Validation failures, malformed JSON |
| `401 Unauthorized` | Authentication required | Missing or invalid JWT token |
| `403 Forbidden` | Authenticated but not authorized | Non-admin accessing admin endpoints |
| `404 Not Found` | Resource doesn't exist | User/form/reference data not found |
| `409 Conflict` | Resource state conflict | Email already registered |
| `422 Unprocessable Entity` | Validation failure | Form validation errors |
| `429 Too Many Requests` | Rate limit exceeded | Too many login attempts |
| `500 Internal Server Error` | Server-side error | Unexpected exceptions, DB failures |
| `503 Service Unavailable` | Temporary unavailability | DynamoDB rate limiting |

---

## Error Codes by Category

### Authentication Errors (`AUTH_*`)

| Code | HTTP Status | Message | Trigger |
|------|-------------|---------|---------|
| `AUTH_INVALID_CREDENTIALS` | 401 | "Invalid email or password" | Login with wrong credentials |
| `AUTH_TOKEN_MISSING` | 401 | "Authentication token required" | Accessing protected route without token |
| `AUTH_TOKEN_INVALID` | 401 | "Invalid or expired token" | Malformed JWT or expired token |
| `AUTH_EMAIL_NOT_VERIFIED` | 403 | "Email verification required" | Unverified user accessing protected resource |
| `AUTH_PASSWORD_TOO_WEAK` | 422 | "Password does not meet requirements" | Password < 8 chars or missing criteria |
| `AUTH_EMAIL_EXISTS` | 409 | "Email already registered" | Registration with existing email |

### Validation Errors (`VALIDATION_*`)

| Code | HTTP Status | Message | Trigger |
|------|-------------|---------|---------|
| `VALIDATION_ERROR` | 422 | "Validation failed" | General validation failure |
| `VALIDATION_MISSING_FIELD` | 422 | "Required field missing: {field}" | Empty required field |
| `VALIDATION_INVALID_FORMAT` | 422 | "Invalid format for {field}" | Email format, date format, etc. |
| `VALIDATION_INVALID_VALUE` | 422 | "Invalid value for {field}" | Enum value not in allowed list |

### Reference Data Errors (`REFERENCE_*`)

| Code | HTTP Status | Message | Trigger |
|------|-------------|---------|---------|
| `REFERENCE_ERROR` | 500 | "Failed to load reference data" | JSON parsing error |
| `REFERENCE_ERROR` | 404 | "Feelings data not found" | Missing lista_uczuc.json |
| `REFERENCE_ERROR` | 404 | "Needs data not found" | Missing lista_potrzeb.json |

### Form Errors (`FORM_*`)

| Code | HTTP Status | Message | Trigger |
|------|-------------|---------|---------|
| `FORM_NOT_FOUND` | 404 | "Form not found" | Form ID doesn't exist |
| `FORM_UNAUTHORIZED` | 403 | "Not authorized to access this form" | User trying to access another user's form |
| `FORM_INVALID_TYPE` | 422 | "Invalid form type" | Form type not in [DUP, TUP, DOS, OK10] |

### Configuration Errors (`CONFIG_*`)

| Code | HTTP Status | Message | Trigger |
|------|-------------|---------|---------|
| `CONFIG_ERROR` | 500 | "Environment configuration error" | Missing required env vars |
| `CONFIG_INVALID_ENV` | 500 | "Invalid APP_ENV value" | APP_ENV not in [dev, uat, prod, test] |

### Database Errors (`DB_*`)

| Code | HTTP Status | Message | Trigger |
|------|-------------|---------|---------|
| `DB_CONNECTION_ERROR` | 503 | "Database connection failed" | Cannot connect to DynamoDB |
| `DB_QUERY_ERROR` | 500 | "Database query failed" | DynamoDB query exception |
| `DB_RATE_LIMIT` | 503 | "Database rate limit exceeded" | DynamoDB throttling |

---

## Error Response Examples

### Validation Error with Details
```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": {
      "email": "Invalid email format",
      "password": "Password must be at least 8 characters"
    }
  }
}
```

### Authentication Error
```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "AUTH_INVALID_CREDENTIALS",
    "message": "Invalid email or password"
  }
}
```

### Not Found Error
```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "FORM_NOT_FOUND",
    "message": "Form not found"
  }
}
```

### Server Error
```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "DB_QUERY_ERROR",
    "message": "Database query failed",
    "details": {
      "exception": "ProvisionedThroughputExceededException"
    }
  }
}
```

---

## Frontend Error Handling

### Error Display Strategy

1. **Network Errors**: Display connection error message with retry button
2. **Validation Errors**: Show field-specific errors inline with form
3. **Authentication Errors**: Redirect to login page
4. **Server Errors**: Display generic "Something went wrong" with error ID
5. **404 Errors**: Display "Not found" with navigation back

### Example TypeScript Error Handler
```typescript
function handleAPIError(error: APIError): void {
  if (error.code?.startsWith('AUTH_')) {
    // Redirect to login
    window.location.href = '/login';
  } else if (error.code?.startsWith('VALIDATION_')) {
    // Show validation errors inline
    showValidationErrors(error.details);
  } else if (error.code?.startsWith('FORM_NOT_FOUND')) {
    // Show not found page
    showNotFoundPage();
  } else {
    // Show generic error
    showErrorMessage(error.message || 'An unexpected error occurred');
  }
}
```

---

## Backend Error Logging

All errors should be logged with:
- Timestamp
- Error code
- HTTP status
- Request path
- User ID (if authenticated)
- Exception stack trace (if applicable)

**Example PHP logging**:
```php
error_log(sprintf(
    '[%s] %s %s - Code: %s, Status: %d, User: %s',
    date('Y-m-d H:i:s'),
    $_SERVER['REQUEST_METHOD'],
    $_SERVER['REQUEST_URI'],
    $errorCode,
    $httpStatus,
    $_SESSION['user_id'] ?? 'anonymous'
));
```

---

**Version**: 1.0.0 | **Created**: 2026-06-07
