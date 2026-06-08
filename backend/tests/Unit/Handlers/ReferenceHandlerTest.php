<?php

namespace UpApp\Tests\Unit\Handlers;

use PHPUnit\Framework\TestCase;
use UpApp\Handlers\ReferenceHandler;
use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

class ReferenceHandlerTest extends TestCase
{
    private ReferenceHandler $handler;
    private RequestFactory $requestFactory;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        $this->handler = new ReferenceHandler();
        $this->requestFactory = new RequestFactory();
        $this->responseFactory = new ResponseFactory();
    }

    public function testGetFeelingsReturnsSuccess(): void
    {
        $request = $this->requestFactory->createRequest('GET', '/api/reference/feelings');
        $response = $this->responseFactory->createResponse();

        $result = $this->handler->getFeelings($request, $response);

        $this->assertSame(200, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertNull($body['error']);
    }

    public function testGetFeelingsHasFulfilledAndUnfulfilled(): void
    {
        $request = $this->requestFactory->createRequest('GET', '/api/reference/feelings');
        $response = $this->responseFactory->createResponse();

        $result = $this->handler->getFeelings($request, $response);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertArrayHasKey('fulfilled', $body['data']);
        $this->assertArrayHasKey('unfulfilled', $body['data']);
        $this->assertNotEmpty($body['data']['fulfilled']);
        $this->assertNotEmpty($body['data']['unfulfilled']);
    }

    public function testGetFeelingsItemsHaveIdAndNamePl(): void
    {
        $request = $this->requestFactory->createRequest('GET', '/api/reference/feelings');
        $response = $this->responseFactory->createResponse();

        $result = $this->handler->getFeelings($request, $response);

        $body = json_decode((string) $result->getBody(), true);
        $firstSubcategory = array_key_first($body['data']['fulfilled']);
        $firstItem = $body['data']['fulfilled'][$firstSubcategory][0];

        $this->assertArrayHasKey('id', $firstItem);
        $this->assertArrayHasKey('name_pl', $firstItem);
        $this->assertNotEmpty($firstItem['id']);
        $this->assertNotEmpty($firstItem['name_pl']);
    }

    public function testGetNeedsReturnsSuccess(): void
    {
        $request = $this->requestFactory->createRequest('GET', '/api/reference/needs');
        $response = $this->responseFactory->createResponse();

        $result = $this->handler->getNeeds($request, $response);

        $this->assertSame(200, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertNull($body['error']);
    }

    public function testGetNeedsHasCategories(): void
    {
        $request = $this->requestFactory->createRequest('GET', '/api/reference/needs');
        $response = $this->responseFactory->createResponse();

        $result = $this->handler->getNeeds($request, $response);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertNotEmpty($body['data']);
        $firstCategory = array_key_first($body['data']);
        $this->assertNotEmpty($firstCategory);
        $this->assertIsArray($body['data'][$firstCategory]);
    }

    public function testGetNeedsItemsHaveIdAndNamePl(): void
    {
        $request = $this->requestFactory->createRequest('GET', '/api/reference/needs');
        $response = $this->responseFactory->createResponse();

        $result = $this->handler->getNeeds($request, $response);

        $body = json_decode((string) $result->getBody(), true);
        $firstCategory = array_key_first($body['data']);
        $firstItem = $body['data'][$firstCategory][0];

        $this->assertArrayHasKey('id', $firstItem);
        $this->assertArrayHasKey('name_pl', $firstItem);
    }
}
