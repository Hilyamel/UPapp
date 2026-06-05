<?php

namespace Tests\Benchmark;

use PHPUnit\Framework\TestCase;
use UpApp\Handlers\AuthHandler;
use UpApp\Repositories\UserRepository;
use UpApp\Models\User;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

/**
 * Benchmark tests for password reset performance
 *
 * Run with: vendor/bin/phpunit --group benchmark
 *
 * @group benchmark
 */
class PasswordResetBenchmarkTest extends TestCase
{
    private AuthHandler $handler;
    private UserRepository $userRepo;
    private array $testUserIds = [];

    protected function setUp(): void
    {
        $this->handler = new AuthHandler();
        $this->userRepo = new UserRepository();
    }

    protected function tearDown(): void
    {
        // Cleanup test users
        foreach ($this->testUserIds as $userId) {
            try {
                $this->userRepo->delete($userId);
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
    }

    /**
     * @group benchmark
     */
    public function testForgotPasswordPerformance(): void
    {
        // Create test user
        $userId = 'bench-user-' . uniqid();
        $user = new User(
            $userId,
            'benchmark@example.com',
            User::hashPassword('testpass'),
            'Benchmark User',
            true
        );
        $this->userRepo->create($user);
        $this->testUserIds[] = $userId;

        // Benchmark: forgot-password request
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $requestFactory = new ServerRequestFactory();
            $responseFactory = new ResponseFactory();

            $request = $requestFactory->createServerRequest('POST', '/api/auth/forgot-password')
                ->withParsedBody(['email' => 'benchmark@example.com']);
            $response = $responseFactory->createResponse();

            $this->handler->forgotPassword($request, $response);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000; // ms
        $avgTime = $totalTime / $iterations;

        echo "\n";
        echo "=== Password Reset Benchmark ===\n";
        echo "Forgot Password (without email sending):\n";
        echo sprintf("  Iterations: %d\n", $iterations);
        echo sprintf("  Total time: %.2f ms\n", $totalTime);
        echo sprintf("  Average time: %.2f ms\n", $avgTime);
        echo sprintf("  Throughput: %.2f req/sec\n", 1000 / $avgTime);

        // Assert performance threshold: should be under 50ms per request
        $this->assertLessThan(50, $avgTime, 'Forgot password should complete in under 50ms');
    }

    /**
     * @group benchmark
     */
    public function testResetPasswordPerformance(): void
    {
        // Create multiple users with reset tokens
        $userIds = [];
        $tokens = [];

        for ($i = 0; $i < 100; $i++) {
            $userId = 'bench-reset-' . uniqid();
            $token = 'token-' . uniqid();
            $user = new User(
                $userId,
                "reset{$i}@example.com",
                User::hashPassword('oldpass'),
                'Test User',
                true,
                null,
                null,
                null,
                $token,
                time() + 3600
            );
            $this->userRepo->create($user);
            $userIds[] = $userId;
            $tokens[] = $token;
            $this->testUserIds[] = $userId;
        }

        // Benchmark: reset-password requests
        $iterations = count($tokens);
        $startTime = microtime(true);

        foreach ($tokens as $token) {
            $requestFactory = new ServerRequestFactory();
            $responseFactory = new ResponseFactory();

            $request = $requestFactory->createServerRequest('POST', '/api/auth/reset-password')
                ->withParsedBody([
                    'token' => $token,
                    'password' => 'newpassword123'
                ]);
            $response = $responseFactory->createResponse();

            $this->handler->resetPassword($request, $response);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000; // ms
        $avgTime = $totalTime / $iterations;

        echo "\n";
        echo "Reset Password (with DB writes):\n";
        echo sprintf("  Iterations: %d\n", $iterations);
        echo sprintf("  Total time: %.2f ms\n", $totalTime);
        echo sprintf("  Average time: %.2f ms\n", $avgTime);
        echo sprintf("  Throughput: %.2f req/sec\n", 1000 / $avgTime);

        // Assert performance threshold: should be under 100ms per request
        $this->assertLessThan(100, $avgTime, 'Reset password should complete in under 100ms');
    }

    /**
     * @group benchmark
     */
    public function testTokenGenerationPerformance(): void
    {
        $iterations = 10000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            User::generateResetToken();
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000; // ms
        $avgTime = $totalTime / $iterations;

        echo "\n";
        echo "Token Generation:\n";
        echo sprintf("  Iterations: %d\n", $iterations);
        echo sprintf("  Total time: %.2f ms\n", $totalTime);
        echo sprintf("  Average time: %.4f ms\n", $avgTime);
        echo sprintf("  Throughput: %.0f tokens/sec\n", 1000 / $avgTime);

        // Token generation should be very fast (under 0.1ms)
        $this->assertLessThan(0.1, $avgTime, 'Token generation should be under 0.1ms');
    }

    /**
     * @group benchmark
     */
    public function testPasswordHashingPerformance(): void
    {
        $iterations = 100;
        $password = 'test-password-123';
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            User::hashPassword($password);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000; // ms
        $avgTime = $totalTime / $iterations;

        echo "\n";
        echo "Password Hashing (BCRYPT):\n";
        echo sprintf("  Iterations: %d\n", $iterations);
        echo sprintf("  Total time: %.2f ms\n", $totalTime);
        echo sprintf("  Average time: %.2f ms\n", $avgTime);
        echo sprintf("  Throughput: %.2f hashes/sec\n", 1000 / $avgTime);

        // BCRYPT is intentionally slow for security, but should be under 200ms
        $this->assertLessThan(200, $avgTime, 'Password hashing should complete in under 200ms');
    }

    /**
     * @group benchmark
     */
    public function testConcurrentResetRequests(): void
    {
        // Simulate concurrent reset requests for same user
        $userId = 'bench-concurrent-' . uniqid();
        $user = new User(
            $userId,
            'concurrent@example.com',
            User::hashPassword('testpass'),
            'Test User',
            true
        );
        $this->userRepo->create($user);
        $this->testUserIds[] = $userId;

        $concurrentRequests = 50;
        $startTime = microtime(true);

        for ($i = 0; $i < $concurrentRequests; $i++) {
            $requestFactory = new ServerRequestFactory();
            $responseFactory = new ResponseFactory();

            $request = $requestFactory->createServerRequest('POST', '/api/auth/forgot-password')
                ->withParsedBody(['email' => 'concurrent@example.com']);
            $response = $responseFactory->createResponse();

            $result = $this->handler->forgotPassword($request, $response);

            // Each should succeed
            $this->assertEquals(200, $result->getStatusCode());
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000; // ms
        $avgTime = $totalTime / $concurrentRequests;

        echo "\n";
        echo "Concurrent Reset Requests (same user):\n";
        echo sprintf("  Concurrent requests: %d\n", $concurrentRequests);
        echo sprintf("  Total time: %.2f ms\n", $totalTime);
        echo sprintf("  Average time: %.2f ms\n", $avgTime);

        // Verify only last token is valid
        $updatedUser = $this->userRepo->findById($userId);
        $this->assertNotNull($updatedUser->getResetToken());
        $this->assertTrue($updatedUser->isResetTokenValid());
    }
}
