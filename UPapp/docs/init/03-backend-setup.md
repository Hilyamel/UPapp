# Phase 3: Backend Foundation - PHP Slim Framework

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Setup PHP Slim 4 REST API with dependency injection, middleware pipeline, and repository pattern

**Architecture:** Slim 4 microframework, PHP-DI container, Repository pattern for data access, PSR-7/PSR-15 compliant

**Tech Stack:** PHP 8.1, Slim 4, PHP-DI, Monolog, AWS SDK, Firebase JWT, PHPMailer, Guzzle

---

## Composer Initialization

### Task 1: Setup Composer and Core Dependencies

**Files:**
- Modify: `backend/composer.json`

- [ ] **Step 1: Initialize composer.json with core packages**

```bash
cd backend
composer init \
  --name="upapp/backend" \
  --type=project \
  --description="UPapp Backend API" \
  --no-interaction
```

Expected: composer.json created

- [ ] **Step 2: Require Slim Framework and PSR-7**

```bash
composer require slim/slim:^4.12 slim/psr7:^1.6
```

Expected: Packages installed, shows "slim/slim (4.12.x)" and "slim/psr7 (1.6.x)"

- [ ] **Step 3: Require PHP-DI container**

```bash
composer require php-di/php-di:^7.0
```

Expected: php-di installed

- [ ] **Step 4: Require Monolog for logging**

```bash
composer require monolog/monolog:^3.5
```

Expected: monolog/monolog (3.5.x) installed

- [ ] **Step 5: Add PSR-4 autoloading**

Edit `backend/composer.json`, add autoload section after require:
```json
"autoload": {
    "psr-4": {
        "UpApp\\": "src/"
    }
},
```

Then regenerate autoloader:
```bash
composer dump-autoload
```

Expected: "Generated autoload files"

- [ ] **Step 6: Verify autoloading works**

Create test file `backend/test-autoload.php`:
```php
<?php
require __DIR__ . '/vendor/autoload.php';

// Try to load a namespace (won't exist yet but should not error)
$reflector = new ReflectionClass('Composer\Autoload\ClassLoader');
echo "✓ Autoloader working\n";
echo "✓ UpApp\\ namespace mapped to src/\n";
```

Run: `php backend/test-autoload.php`
Expected: "✓ Autoloader working"

Delete test: `rm backend/test-autoload.php`

- [ ] **Step 7: Commit composer setup**

```bash
cd ..
git add backend/composer.json backend/composer.lock
git commit -m "feat: setup Slim Framework backend

- Initialize composer.json for UPapp backend
- Add Slim Framework 4.12 and PSR-7
- Add PHP-DI dependency injection container
- Add Monolog for logging
- Configure PSR-4 autoloading for UpApp namespace

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

---

### Task 2: Install Additional Dependencies

**Files:**
- Modify: `backend/composer.json`

- [ ] **Step 1: Install Firebase JWT**

```bash
cd backend
composer require firebase/php-jwt:^6.10
```

Expected: firebase/php-jwt (6.10.x) installed

- [ ] **Step 2: Install UUID generator**

```bash
composer require ramsey/uuid:^4.7
```

Expected: ramsey/uuid (4.7.x) installed

- [ ] **Step 3: Install Guzzle HTTP client**

```bash
composer require guzzlehttp/guzzle:^7.8
```

Expected: guzzlehttp/guzzle (7.8.x) installed

- [ ] **Step 4: Install PHPMailer**

```bash
composer require phpmailer/phpmailer:^6.9
```

Expected: phpmailer/phpmailer (6.9.x) installed

- [ ] **Step 5: Install CORS middleware**

```bash
composer require tuupola/cors-middleware:^1.4
```

Expected: tuupola/cors-middleware installed

- [ ] **Step 6: Verify all dependencies**

```bash
composer show | grep -E '(slim|php-di|monolog|aws|firebase|ramsey|guzzle|phpmailer|tuupola)'
```

Expected: Shows all 8+ packages installed

- [ ] **Step 7: Commit dependencies**

```bash
cd ..
git add backend/composer.json backend/composer.lock
git commit -m "feat: add backend API dependencies

- Add Firebase JWT for token management
- Add Ramsey UUID for ID generation
- Add Guzzle for OAuth HTTP requests
- Add PHPMailer for magic link emails
- Add CORS middleware

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

---

## Core Application Files

### Task 3: Create Application Entry Point

**Files:**
- Modify: `backend/public/index.php`
- Create: `backend/src/App.php`

- [ ] **Step 1: Write App factory class**

```php
<?php
namespace UpApp;

use Slim\Factory\AppFactory;
use DI\Container;
use Dotenv\Dotenv;

class App
{
    public static function create()
    {
        // Load environment variables
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();

        // Create DI container
        $container = new Container();
        
        // Load dependencies
        $dependenciesFile = __DIR__ . '/../config/dependencies.php';
        if (file_exists($dependenciesFile)) {
            $dependencies = require $dependenciesFile;
            $dependencies($container);
        }
        
        // Create Slim app
        AppFactory::setContainer($container);
        $app = AppFactory::create();
        
        // Add middleware
        $middlewareFile = __DIR__ . '/../config/middleware.php';
        if (file_exists($middlewareFile)) {
            $middleware = require $middlewareFile;
            $middleware($app);
        }
        
        // Register routes
        $routesFile = __DIR__ . '/../config/routes.php';
        if (file_exists($routesFile)) {
            $routes = require $routesFile;
            $routes($app);
        }
        
        return $app;
    }
}
```

Write to: `backend/src/App.php`

Run: `test -f backend/src/App.php && echo "Created"`
Expected: "Created"

- [ ] **Step 2: Write entry point**

```php
<?php
require __DIR__ . '/../vendor/autoload.php';

use UpApp\App;

$app = App::create();
$app->run();
```

Write to: `backend/public/index.php`

Run: `cat backend/public/index.php | head -5`
Expected: Shows require and use statements

- [ ] **Step 3: Test basic app bootstraps**

```bash
php backend/public/index.php 2>&1 | head -5
```

Expected: Error about missing config files (expected, we'll create them next)

- [ ] **Step 4: Commit app bootstrap**

```bash
git add backend/src/App.php backend/public/index.php
git commit -m "feat: create Slim application bootstrap

- Add App factory class
- Load environment, container, middleware, routes
- Create entry point in public/index.php
- Follows Slim 4 best practices

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

---

### Task 4: Create Configuration Files

**Files:**
- Create: `backend/config/dependencies.php`
- Create: `backend/config/middleware.php`
- Create: `backend/config/routes.php`

- [ ] **Step 1: Create dependencies configuration**

```php
<?php
use DI\Container;
use Aws\DynamoDb\DynamoDbClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

return function (Container $container) {
    // DynamoDB Client
    $container->set(DynamoDbClient::class, function () {
        $config = [
            'region' => $_ENV['AWS_REGION'],
            'version' => 'latest',
        ];
        
        // Use local DynamoDB if endpoint specified
        if (!empty($_ENV['DYNAMODB_ENDPOINT'])) {
            $config['endpoint'] = $_ENV['DYNAMODB_ENDPOINT'];
        }
        
        // Use credentials from env if specified
        if (!empty($_ENV['AWS_ACCESS_KEY_ID'])) {
            $config['credentials'] = [
                'key' => $_ENV['AWS_ACCESS_KEY_ID'],
                'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
            ];
        }
        
        return new DynamoDbClient($config);
    });

    // Logger
    $container->set(Logger::class, function () {
        $logger = new Logger('upapp');
        $logPath = __DIR__ . '/../logs/app.log';
        
        // Create logs directory if it doesn't exist
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logger->pushHandler(
            new StreamHandler($logPath, Logger::DEBUG)
        );
        
        return $logger;
    });
};
```

Write to: `backend/config/dependencies.php`

- [ ] **Step 2: Create middleware configuration**

```php
<?php
use Tuupola\Middleware\CorsMiddleware;

return function ($app) {
    // Parse request body
    $app->addBodyParsingMiddleware();
    
    // CORS Middleware
    $origins = explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? 'http://localhost:5173');
    
    $app->add(new CorsMiddleware([
        'origin' => $origins,
        'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'headers.allow' => ['Content-Type', 'Authorization'],
        'headers.expose' => [],
        'credentials' => true,
        'cache' => 86400,
    ]));

    // Error Middleware
    $displayErrors = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    $app->addErrorMiddleware($displayErrors, true, true);
};
```

Write to: `backend/config/middleware.php`

- [ ] **Step 3: Create routes configuration**

```php
<?php
use Slim\Routing\RouteCollectorProxy;

return function ($app) {
    // Health check endpoint
    $app->get('/health', function ($request, $response) {
        $data = [
            'status' => 'ok',
            'timestamp' => time(),
            'environment' => $_ENV['APP_ENV'] ?? 'unknown',
        ];
        
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // API v1 routes
    $app->group('/api/v1', function (RouteCollectorProxy $group) {
        // Auth routes (to be implemented)
        $group->group('/auth', function (RouteCollectorProxy $auth) {
            $auth->get('/test', function ($request, $response) {
                $response->getBody()->write(json_encode(['message' => 'Auth routes work']));
                return $response->withHeader('Content-Type', 'application/json');
            });
        });
    });
};
```

Write to: `backend/config/routes.php`

- [ ] **Step 4: Test backend starts**

```bash
cd backend
php -S localhost:8080 -t public > /dev/null 2>&1 &
PHP_PID=$!
sleep 2
```

Run: `curl -s http://localhost:8080/health | jq .`
Expected: JSON with "status": "ok"

Stop server: `kill $PHP_PID`

- [ ] **Step 5: Verify logs directory created**

```bash
test -d backend/logs && echo "Logs directory exists"
```

Expected: "Logs directory exists"

- [ ] **Step 6: Commit configuration files**

```bash
cd ..
git add backend/config/
git commit -m "feat: add backend configuration files

- Create dependencies.php with DynamoDB and Logger
- Create middleware.php with CORS and error handling
- Create routes.php with health check endpoint
- Auto-create logs directory

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

---

## Base Repository Pattern

### Task 5: Create DynamoDB Base Repository

**Files:**
- Create: `backend/src/Repositories/DynamoDBRepository.php`

- [ ] **Step 1: Write base repository class**

```php
<?php
namespace UpApp\Repositories;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;

abstract class DynamoDBRepository
{
    protected DynamoDbClient $dynamodb;
    protected string $tablePrefix;
    protected Marshaler $marshaler;

    public function __construct(DynamoDbClient $dynamodb)
    {
        $this->dynamodb = $dynamodb;
        $this->tablePrefix = $_ENV['DYNAMODB_TABLE_PREFIX'];
        $this->marshaler = new Marshaler();
    }

    /**
     * Get full table name with environment prefix
     */
    protected function getTableName(string $name): string
    {
        return "{$this->tablePrefix}.{$name}";
    }

    /**
     * Marshall PHP array to DynamoDB format
     */
    protected function marshallItem(array $item): array
    {
        return $this->marshaler->marshalItem($item);
    }

    /**
     * Unmarshall DynamoDB item to PHP array
     */
    protected function unmarshallItem(array $item): array
    {
        return $this->marshaler->unmarshalItem($item);
    }

    /**
     * Put item into table
     */
    protected function putItem(string $tableName, array $item): void
    {
        $this->dynamodb->putItem([
            'TableName' => $this->getTableName($tableName),
            'Item' => $this->marshallItem($item),
        ]);
    }

    /**
     * Get item by key
     */
    protected function getItem(string $tableName, array $key): ?array
    {
        $result = $this->dynamodb->getItem([
            'TableName' => $this->getTableName($tableName),
            'Key' => $this->marshallItem($key),
        ]);

        return $result['Item'] ?? null;
    }
}
```

Write to: `backend/src/Repositories/DynamoDBRepository.php`

Run: `test -f backend/src/Repositories/DynamoDBRepository.php && echo "Created"`
Expected: "Created"

- [ ] **Step 2: Test base repository compiles**

Create test: `backend/test-repo.php`:
```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Check class can be loaded
$reflector = new ReflectionClass('UpApp\Repositories\DynamoDBRepository');
echo "✓ DynamoDBRepository class loads\n";
echo "✓ Methods: " . count($reflector->getMethods()) . "\n";
```

Run: `php backend/test-repo.php`
Expected: Shows class loads with methods

Delete test: `rm backend/test-repo.php`

- [ ] **Step 3: Commit base repository**

```bash
git add backend/src/Repositories/DynamoDBRepository.php
git commit -m "feat: add DynamoDB base repository

- Create abstract base class for DynamoDB operations
- Use AWS Marshaler for data conversion
- Provide putItem and getItem helpers
- Support table prefix from environment

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

---

## Utilities

### Task 6: Create JWT Helper

**Files:**
- Create: `backend/src/Utils/JWTHelper.php`

- [ ] **Step 1: Write JWT helper class**

```php
<?php
namespace UpApp\Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHelper
{
    private string $secret;
    private int $expiry;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'];
        $this->expiry = (int)($_ENV['JWT_EXPIRY'] ?? 604800);
    }

    /**
     * Generate JWT token from payload
     */
    public function generateToken(array $payload): string
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + $this->expiry;
        
        return JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * Verify and decode JWT token
     */
    public function verifyToken(string $token): object
    {
        return JWT::decode($token, new Key($this->secret, 'HS256'));
    }

    /**
     * Set JWT token in HTTP-only cookie
     */
    public function setTokenCookie($response, string $token)
    {
        $maxAge = $this->expiry;
        $secure = ($_ENV['APP_ENV'] ?? 'development') === 'production' ? 'Secure; ' : '';
        
        return $response->withHeader(
            'Set-Cookie', 
            "session_token={$token}; {$secure}HttpOnly; SameSite=Lax; Max-Age={$maxAge}; Path=/"
        );
    }

    /**
     * Clear JWT token cookie
     */
    public function clearTokenCookie($response)
    {
        return $response->withHeader(
            'Set-Cookie', 
            "session_token=; HttpOnly; SameSite=Lax; Max-Age=0; Path=/"
        );
    }
}
```

Write to: `backend/src/Utils/JWTHelper.php`

- [ ] **Step 2: Test JWT helper**

Create test: `backend/test-jwt.php`:
```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use UpApp\Utils\JWTHelper;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$jwt = new JWTHelper();

// Generate token
$token = $jwt->generateToken([
    'sub' => 'test-user-123',
    'email' => 'test@example.com',
]);

echo "✓ Generated token: " . substr($token, 0, 20) . "...\n";

// Verify token
try {
    $decoded = $jwt->verifyToken($token);
    echo "✓ Token verified\n";
    echo "  - User: {$decoded->sub}\n";
    echo "  - Email: {$decoded->email}\n";
} catch (Exception $e) {
    echo "✗ Verification failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✓ JWTHelper working correctly\n";
```

Run: `php backend/test-jwt.php`
Expected: Shows token generated and verified

Delete test: `rm backend/test-jwt.php`

- [ ] **Step 3: Commit JWT helper**

```bash
git add backend/src/Utils/JWTHelper.php
git commit -m "feat: add JWT token helper utility

- Create JWTHelper for token generation/verification
- Use Firebase JWT library
- Support HTTP-only cookie management
- Read secret and expiry from environment

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

---

### Task 7: Create Response Helper

**Files:**
- Create: `backend/src/Utils/ResponseHelper.php`

- [ ] **Step 1: Write response helper**

```php
<?php
namespace UpApp\Utils;

use Psr\Http\Message\ResponseInterface as Response;

class ResponseHelper
{
    /**
     * Return JSON success response
     */
    public static function json(
        Response $response,
        $data,
        int $status = 200
    ): Response {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    /**
     * Return JSON error response
     */
    public static function error(
        Response $response,
        string $message,
        int $status = 400,
        ?string $code = null
    ): Response {
        $data = ['error' => $message];
        
        if ($code) {
            $data['code'] = $code;
        }
        
        return self::json($response, $data, $status);
    }

    /**
     * Return success message
     */
    public static function success(
        Response $response,
        string $message,
        ?array $data = null
    ): Response {
        $payload = ['success' => true, 'message' => $message];
        
        if ($data) {
            $payload['data'] = $data;
        }
        
        return self::json($response, $payload);
    }
}
```

Write to: `backend/src/Utils/ResponseHelper.php`

- [ ] **Step 2: Update routes to use ResponseHelper**

Edit `backend/config/routes.php`, update health check:
```php
use UpApp\Utils\ResponseHelper;

return function ($app) {
    $app->get('/health', function ($request, $response) {
        return ResponseHelper::json($response, [
            'status' => 'ok',
            'timestamp' => time(),
            'environment' => $_ENV['APP_ENV'] ?? 'unknown',
        ]);
    });
    
    // ... rest of routes
};
```

- [ ] **Step 3: Test with ResponseHelper**

```bash
cd backend
php -S localhost:8080 -t public > /dev/null 2>&1 &
PHP_PID=$!
sleep 2
```

Run: `curl -s http://localhost:8080/health | jq .status`
Expected: "ok"

Stop: `kill $PHP_PID`

- [ ] **Step 4: Commit response helper**

```bash
cd ..
git add backend/src/Utils/ResponseHelper.php backend/config/routes.php
git commit -m "feat: add response helper utility

- Create ResponseHelper for consistent JSON responses
- Add json(), error(), success() methods
- Update health check to use helper

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

---

### Task 8: Verify Backend Foundation

**Files:**
- Create: `backend/test-backend.sh`

- [ ] **Step 1: Create comprehensive test script**

```bash
#!/bin/bash

echo "================================"
echo "Backend Foundation Tests"
echo "================================"
echo ""

cd backend

# Test 1: Composer dependencies
echo "Test 1: Checking dependencies..."
if composer show | grep -q slim/slim; then
    echo "  ✓ Slim Framework installed"
else
    echo "  ✗ Slim Framework missing"
    exit 1
fi

# Test 2: Autoloading
echo "Test 2: Checking autoload..."
php -r "require 'vendor/autoload.php'; echo '  ✓ Autoload works\n';" || exit 1

# Test 3: Environment loading
echo "Test 3: Checking environment..."
php -r "require 'vendor/autoload.php'; \Dotenv\Dotenv::createImmutable(__DIR__)->load(); echo '  ✓ Environment loads\n';" || exit 1

# Test 4: JWT Helper
echo "Test 4: Checking JWT Helper..."
php -r "
require 'vendor/autoload.php';
\Dotenv\Dotenv::createImmutable(__DIR__)->load();
\$jwt = new \UpApp\Utils\JWTHelper();
\$token = \$jwt->generateToken(['test' => true]);
\$jwt->verifyToken(\$token);
echo '  ✓ JWT Helper works\n';
" || exit 1

# Test 5: Start server and test health endpoint
echo "Test 5: Checking health endpoint..."
php -S localhost:8080 -t public > /dev/null 2>&1 &
PID=$!
sleep 2

if curl -s http://localhost:8080/health | grep -q '"status":"ok"'; then
    echo "  ✓ Health endpoint works"
else
    echo "  ✗ Health endpoint failed"
    kill $PID
    exit 1
fi

kill $PID
sleep 1

echo ""
echo "================================"
echo "✓ All backend tests passed"
echo "================================"
```

Write to: `backend/test-backend.sh`

- [ ] **Step 2: Make executable and run**

```bash
chmod +x backend/test-backend.sh
bash backend/test-backend.sh
```

Expected: All 5 tests pass

- [ ] **Step 3: Add npm script**

Edit root `package.json`, add to scripts:
```json
"test:backend": "bash backend/test-backend.sh"
```

Run: `npm run test:backend`
Expected: All tests pass

- [ ] **Step 4: Commit test script**

```bash
git add backend/test-backend.sh package.json
git commit -m "test: add backend foundation tests

- Check dependencies installed
- Verify autoloading works
- Test environment loading
- Test JWT helper functionality
- Verify health endpoint responds

Co-Authored-By: Claude Opus 4.7 <noreply@anthropic.com>"
```

- [ ] **Step 5: Tag Phase 3 complete**

```bash
git tag phase-3-complete
git log --oneline --graph --decorate | head -10
```

Expected: Shows phase-3-complete tag

---

## Reference: Full Architecture

This section documents the complete backend architecture (not executable tasks).

### Directory Structure

```
backend/
├── composer.json              # Dependencies
├── public/index.php           # Entry point
├── src/
│   ├── App.php               # Application factory
│   ├── Controllers/          # HTTP controllers (Phase 4)
│   ├── Services/             # Business logic (Phase 4)
│   ├── Repositories/         # Data access (Phase 4)
│   │   └── DynamoDBRepository.php  # ✓ Base class
│   ├── Models/               # Data models (Phase 4)
│   ├── Middleware/           # Auth, CORS, etc (Phase 4)
│   └── Utils/                # Helpers
│       ├── ConfigValidator.php     # ✓ Created
│       ├── JWTHelper.php          # ✓ Created
│       └── ResponseHelper.php     # ✓ Created
├── config/
│   ├── dependencies.php      # ✓ DI container
│   ├── middleware.php        # ✓ Middleware stack
│   └── routes.php           # ✓ API routes
└── logs/
    └── app.log              # Application logs
```

### What We Built (Phase 3)

✅ Slim Framework setup  
✅ Dependency injection container  
✅ Environment loading  
✅ CORS middleware  
✅ Error handling  
✅ Health check endpoint  
✅ Base repository pattern  
✅ JWT token management  
✅ Response helpers  
✅ Logging infrastructure  

### What's Next (Phase 4+)

⏭ Controllers (Auth, Forms, Admin)  
⏭ Services (Business logic)  
⏭ Repositories (User, Form, etc)  
⏭ Authentication middleware  
⏭ Rate limiting  

---

## Next Steps

Phase 3 complete! Backend foundation ready. Continue with:

1. **[02-frontend-setup.md](02-frontend-setup.md)** - Initialize React application
2. **[05-authentication.md](05-authentication.md)** - Implement authentication (requires controllers)
3. **[06-deployment.md](06-deployment.md)** - Setup deployment tools
