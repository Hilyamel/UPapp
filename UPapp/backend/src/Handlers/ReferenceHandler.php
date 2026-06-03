<?php

namespace UpApp\Handlers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReferenceHandler
{
    private string $dataDir;

    public function __construct()
    {
        $this->dataDir = __DIR__ . '/../../../data';
    }

    public function getFeelings(Request $request, Response $response): Response
    {
        $feelingsFile = $this->dataDir . '/lista_uczuc.json';

        if (!file_exists($feelingsFile)) {
            return $this->errorResponse($response, 'Feelings data not found', 404);
        }

        $feelingsData = json_decode(file_get_contents($feelingsFile), true);

        if (!$feelingsData) {
            return $this->errorResponse($response, 'Failed to load feelings data', 500);
        }

        // Group feelings by category and subcategory
        $grouped = [
            'fulfilled' => [],
            'unfulfilled' => []
        ];

        foreach ($feelingsData as $feeling) {
            $category = $feeling['category'];
            $subcategory = $feeling['subcategory'];

            if (!isset($grouped[$category][$subcategory])) {
                $grouped[$category][$subcategory] = [];
            }

            $grouped[$category][$subcategory][] = [
                'id' => $feeling['name_pl'], // Use name as ID since we don't have UUIDs
                'name_pl' => $feeling['name_pl']
            ];
        }

        return $this->successResponse($response, $grouped);
    }

    public function getNeeds(Request $request, Response $response): Response
    {
        $needsFile = $this->dataDir . '/lista_potrzeb.json';

        if (!file_exists($needsFile)) {
            return $this->errorResponse($response, 'Needs data not found', 404);
        }

        $needsData = json_decode(file_get_contents($needsFile), true);

        if (!$needsData) {
            return $this->errorResponse($response, 'Failed to load needs data', 500);
        }

        // Group needs by category
        $grouped = [];

        foreach ($needsData as $need) {
            $category = $need['category'];

            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }

            $grouped[$category][] = [
                'id' => $need['name_pl'], // Use name as ID since we don't have UUIDs
                'name_pl' => $need['name_pl']
            ];
        }

        return $this->successResponse($response, $grouped);
    }

    private function successResponse(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $data,
            'error' => null
        ], JSON_UNESCAPED_UNICODE));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }

    private function errorResponse(Response $response, string $message, int $status = 400): Response
    {
        $response->getBody()->write(json_encode([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => 'REFERENCE_ERROR',
                'message' => $message
            ]
        ], JSON_UNESCAPED_UNICODE));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }
}
