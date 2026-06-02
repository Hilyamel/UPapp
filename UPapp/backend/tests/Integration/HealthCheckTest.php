<?php

declare(strict_types=1);

namespace UpApp\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use UpApp\Config\Environment;
use UpApp\Middleware\CorsMiddleware;

class HealthCheckTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        Environment::load();

        $this->app = AppFactory::create();
        $this->app->add(new CorsMiddleware());
        require __DIR__ . '/../../src/routes.php';
    }

    public function testHealthCheckReturnsHealthyStatus(): void
    {
        $request = $this->createRequest('GET', '/api/health');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = (string) $response->getBody();
        $json = json_decode($body, true);

        $this->assertIsArray($json);
        $this->assertTrue($json['success']);
        $this->assertArrayHasKey('data', $json);
        $this->assertArrayHasKey('status', $json['data']);
        $this->assertContains($json['data']['status'], ['healthy', 'degraded', 'pending']);
    }

    public function testHealthCheckReturnsTimestamp(): void
    {
        $request = $this->createRequest('GET', '/api/health');
        $response = $this->app->handle($request);

        $body = (string) $response->getBody();
        $json = json_decode($body, true);

        $this->assertArrayHasKey('timestamp', $json['data']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $json['data']['timestamp']);
    }

    private function createRequest(string $method, string $path): Request
    {
        $request = new \Slim\Psr7\Request(
            new \Slim\Psr7\Headers(),
            [],
            new \Slim\Psr7\Uri('', '', 80, $path),
            $method,
            new \Slim\Psr7\Stream(fopen('php://temp', 'r+'))
        );

        return $request;
    }
}
