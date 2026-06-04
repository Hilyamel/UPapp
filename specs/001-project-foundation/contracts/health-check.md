# API Contract: Health Check Endpoint

**Created**: 2026-06-02

**Purpose**: Provide a simple health check endpoint to verify backend API is running and can connect to DynamoDB.

## Endpoint

```
GET /api/health
```

## Authentication

None required (public endpoint)

## Request

No parameters required.

### Example Request

```bash
curl -X GET http://localhost:8080/api/health
```

## Response

### Success Response (200 OK)

```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "timestamp": "2026-06-02T14:30:00Z",
    "environment": "dev",
    "services": {
      "api": "ok",
      "dynamodb": "ok"
    }
  },
  "error": null
}
```

**Response Fields**:

| Field | Type | Description |
|-------|------|-------------|
| `success` | Boolean | Always `true` for successful health check |
| `data.status` | String | Overall health status: `"healthy"` or `"degraded"` |
| `data.timestamp` | String | Current server time in ISO 8601 UTC format |
| `data.environment` | String | Current environment from `APP_ENV` (dev/uat/prod) |
| `data.services.api` | String | API service status: `"ok"` or `"error"` |
| `data.services.dynamodb` | String | DynamoDB connection status: `"ok"` or `"error"` |
| `error` | Null | Always `null` for successful responses |

### Degraded Response (200 OK)

Returned when API is running but DynamoDB connection fails:

```json
{
  "success": true,
  "data": {
    "status": "degraded",
    "timestamp": "2026-06-02T14:30:00Z",
    "environment": "dev",
    "services": {
      "api": "ok",
      "dynamodb": "error"
    },
    "errors": {
      "dynamodb": "Unable to connect to DynamoDB: CredentialsNotFound"
    }
  },
  "error": null
}
```

**Note**: Health check returns 200 OK even in degraded state. This allows load balancers to distinguish between "backend not running" (connection refused) vs "backend running but dependencies failing" (200 degraded).

### Error Response (500 Internal Server Error)

Returned only if health check endpoint itself fails catastrophically:

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "INTERNAL_ERROR",
    "message": "Health check failed",
    "details": {
      "exception": "RuntimeException: Unable to read APP_ENV"
    }
  }
}
```

## Implementation Notes

### DynamoDB Health Check

The health check performs a lightweight DynamoDB operation to verify connectivity:

```php
// Attempt to describe the config table
$dynamoDb->describeTable([
    'TableName' => 'UpApp.' . $environment . '.config'
]);
```

**Why `describeTable` instead of `getItem`?**
- Does not read actual data (no need for seed data to exist)
- Fast operation (<10ms typical)
- Verifies both AWS credentials and table existence

### Performance

- **Target Response Time**: <50ms (API overhead + DynamoDB describe table)
- **Timeout**: 5 seconds (if DynamoDB unresponsive)
- **Caching**: None (health check must be real-time)

### Security

- No sensitive information in response (no credentials, internal IPs, etc.)
- Environment name (`dev`/`uat`/`prod`) is acceptable to expose
- Detailed error messages only in `dev` environment (redacted in `prod`)

### CORS

Health check endpoint respects CORS configuration:
- **Allowed Origins**: Value from `APP_URL` environment variable
- **Allowed Methods**: GET only
- **Allowed Headers**: None required
- **Credentials**: Not required

## Testing

### Manual Testing

```bash
# Test local backend
curl -i http://localhost:8080/api/health

# Test with jq for formatted output
curl -s http://localhost:8080/api/health | jq .
```

### Automated Testing (PHPUnit)

```php
public function testHealthCheckReturnsHealthyStatus()
{
    $response = $this->get('/api/health');
    
    $this->assertEquals(200, $response->getStatusCode());
    $json = json_decode($response->getBody(), true);
    
    $this->assertTrue($json['success']);
    $this->assertEquals('healthy', $json['data']['status']);
    $this->assertEquals('ok', $json['data']['services']['api']);
    $this->assertEquals('ok', $json['data']['services']['dynamodb']);
}
```

### Integration Testing

Health check is used as smoke test after deployment:
- Run `curl` against health endpoint
- Assert `status === "healthy"`
- Fail deployment if health check returns degraded or error

## Use Cases

### 1. Verify Local Development Setup

Developer runs health check after setting up environment to confirm:
- Backend server is running
- AWS credentials are configured
- DynamoDB tables are accessible

### 2. Deployment Smoke Test

After SFTP deployment, deployment script calls health check to verify:
- PHP files uploaded correctly
- `.env` configuration is valid
- Backend can connect to DynamoDB production tables

### 3. Monitoring

Future monitoring tools (if added) can poll health check endpoint to:
- Alert if backend becomes unavailable
- Detect DynamoDB connectivity issues
- Track environment configuration (dev vs uat vs prod)

### 4. Load Balancer Health Checks

If load balancer added in future:
- Configure health check path: `/api/health`
- Expect 200 status code with `status: "healthy"`
- Mark instance unhealthy if degraded for >3 consecutive checks
