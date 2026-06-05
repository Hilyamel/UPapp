<?php

declare(strict_types=1);

namespace UpApp\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use UpApp\Config\Environment;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            $response = new \Slim\Psr7\Response();
            return $this->addCorsHeaders($response);
        }

        $response = $handler->handle($request);
        return $this->addCorsHeaders($response);
    }

    private function addCorsHeaders(ResponseInterface $response): ResponseInterface
    {
        // Support multiple development origins
        $allowedOrigins = [
            Environment::get('APP_URL', 'http://localhost:5173'),
            'http://localhost:5173',
            'http://localhost:5174',
            'http://localhost:5175',
            'http://localhost:3000',
        ];

        // Get Origin from request
        $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Use requested origin if it's in our whitelist, otherwise use default
        $allowedOrigin = in_array($requestOrigin, $allowedOrigins, true)
            ? $requestOrigin
            : $allowedOrigins[0];

        return $response
            ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Expose-Headers', 'Set-Cookie')
            ->withHeader('Access-Control-Max-Age', '3600');
    }
}
