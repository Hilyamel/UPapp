<?php

namespace UpApp\Handlers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DeployHandler
{
    public function triggerDeploy(Request $request, Response $response): Response
    {
        // Security: Check if user is admin
        // For now, we'll add basic security check
        $user = $request->getAttribute('user');

        if (!$user) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required'
                ]
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Check if user is admin (check ADMIN_EMAIL in env)
        $adminEmail = getenv('ADMIN_EMAIL');
        if ($adminEmail && $user->getEmail() !== $adminEmail) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Admin access required'
                ]
            ]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        try {
            // Execute deployment script
            $scriptPath = __DIR__ . '/../../scripts/deploy-ftp.sh';

            // Check if running on Windows
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

            if ($isWindows) {
                $scriptPath = __DIR__ . '/../../scripts/deploy-ftp.ps1';
                $command = "powershell -ExecutionPolicy Bypass -File \"$scriptPath\" 2>&1";
            } else {
                $command = "bash \"$scriptPath\" 2>&1";
            }

            // Execute in background
            if ($isWindows) {
                $output = [];
                $returnCode = 0;
                exec($command, $output, $returnCode);

                $response->getBody()->write(json_encode([
                    'success' => $returnCode === 0,
                    'data' => [
                        'message' => $returnCode === 0
                            ? 'Deployment started successfully. Check deployment package in project root.'
                            : 'Deployment failed. Check logs for details.',
                        'output' => implode("\n", $output)
                    ],
                    'error' => null
                ]));
            } else {
                // For Linux/Mac, run in background
                $command = "nohup $command > /tmp/upapp-deploy.log 2>&1 &";
                exec($command);

                $response->getBody()->write(json_encode([
                    'success' => true,
                    'data' => [
                        'message' => 'Deployment started in background. Check /tmp/upapp-deploy.log for progress.',
                    ],
                    'error' => null
                ]));
            }

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'DEPLOY_ERROR',
                    'message' => 'Deployment failed: ' . $e->getMessage()
                ]
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function getDeployStatus(Request $request, Response $response): Response
    {
        // Read deployment log
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $logPath = $isWindows ? 'C:\\temp\\upapp-deploy.log' : '/tmp/upapp-deploy.log';

        if (file_exists($logPath)) {
            $logContent = file_get_contents($logPath);
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'log' => $logContent,
                    'timestamp' => filemtime($logPath)
                ],
                'error' => null
            ]));
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'LOG_NOT_FOUND',
                    'message' => 'No deployment log found'
                ]
            ]));
        }

        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }
}
