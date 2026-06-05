<?php

namespace Tests\Unit\Handlers;

use PHPUnit\Framework\TestCase;
use UpApp\Handlers\AuthHandler;
use UpApp\Repositories\UserRepository;
use UpApp\Services\EmailService;
use UpApp\Models\User;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

/**
 * Unit tests for password reset functionality
 */
class AuthHandlerPasswordResetTest extends TestCase
{
    private AuthHandler $handler;
    private UserRepository $userRepo;
    private EmailService $emailService;

    protected function setUp(): void
    {
        $this->handler = new AuthHandler();
        $this->userRepo = new UserRepository();
        $this->emailService = new EmailService();
    }

    public function testForgotPasswordWithValidEmail(): void
    {
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();

        $request = $requestFactory->createServerRequest('POST', '/api/auth/forgot-password')
            ->withParsedBody(['email' => 'test@example.com']);
        $response = $responseFactory->createResponse();

        $result = $this->handler->forgotPassword($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertStringContainsString('password reset instructions', $body['data']['message']);
    }

    public function testForgotPasswordWithEmptyEmail(): void
    {
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();

        $request = $requestFactory->createServerRequest('POST', '/api/auth/forgot-password')
            ->withParsedBody(['email' => '']);
        $response = $responseFactory->createResponse();

        $result = $this->handler->forgotPassword($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('required', $body['error']['message']);
    }

    public function testForgotPasswordSecurityNoUserEnumeration(): void
    {
        // Should return same message for existing and non-existing users
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();

        $request1 = $requestFactory->createServerRequest('POST', '/api/auth/forgot-password')
            ->withParsedBody(['email' => 'exists@example.com']);
        $response1 = $responseFactory->createResponse();

        $result1 = $this->handler->forgotPassword($request1, $response1);
        $body1 = json_decode((string)$result1->getBody(), true);

        $request2 = $requestFactory->createServerRequest('POST', '/api/auth/forgot-password')
            ->withParsedBody(['email' => 'does-not-exist@example.com']);
        $response2 = $responseFactory->createResponse();

        $result2 = $this->handler->forgotPassword($request2, $response2);
        $body2 = json_decode((string)$result2->getBody(), true);

        // Both should return 200 OK with same message
        $this->assertEquals(200, $result1->getStatusCode());
        $this->assertEquals(200, $result2->getStatusCode());
        $this->assertEquals($body1['data']['message'], $body2['data']['message']);
    }

    public function testResetPasswordWithValidToken(): void
    {
        // First create a user with reset token
        $userId = 'test-user-' . uniqid();
        $user = new User(
            $userId,
            'reset@example.com',
            User::hashPassword('oldpassword'),
            'Test User',
            true, // email verified
            null,
            null,
            null,
            'test-reset-token-123',
            time() + 3600 // valid for 1 hour
        );

        $this->userRepo->create($user);

        // Now test reset with valid token
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();

        $request = $requestFactory->createServerRequest('POST', '/api/auth/reset-password')
            ->withParsedBody([
                'token' => 'test-reset-token-123',
                'password' => 'newpassword123'
            ]);
        $response = $responseFactory->createResponse();

        $result = $this->handler->resetPassword($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertStringContainsString('reset successfully', $body['data']['message']);

        // Cleanup
        $this->userRepo->delete($userId);
    }

    public function testResetPasswordWithExpiredToken(): void
    {
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();

        // Create user with expired token
        $userId = 'test-user-expired-' . uniqid();
        $user = new User(
            $userId,
            'expired@example.com',
            User::hashPassword('oldpassword'),
            'Test User',
            true,
            null,
            null,
            null,
            'expired-token-123',
            time() - 3600 // expired 1 hour ago
        );

        $this->userRepo->create($user);

        $request = $requestFactory->createServerRequest('POST', '/api/auth/reset-password')
            ->withParsedBody([
                'token' => 'expired-token-123',
                'password' => 'newpassword123'
            ]);
        $response = $responseFactory->createResponse();

        $result = $this->handler->resetPassword($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('expired', $body['error']['message']);

        // Cleanup
        $this->userRepo->delete($userId);
    }

    public function testResetPasswordWithInvalidToken(): void
    {
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();

        $request = $requestFactory->createServerRequest('POST', '/api/auth/reset-password')
            ->withParsedBody([
                'token' => 'invalid-token-xyz',
                'password' => 'newpassword123'
            ]);
        $response = $responseFactory->createResponse();

        $result = $this->handler->resetPassword($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('Invalid', $body['error']['message']);
    }

    public function testResetPasswordWithShortPassword(): void
    {
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();

        $request = $requestFactory->createServerRequest('POST', '/api/auth/reset-password')
            ->withParsedBody([
                'token' => 'some-token',
                'password' => 'short'
            ]);
        $response = $responseFactory->createResponse();

        $result = $this->handler->resetPassword($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
        $body = json_decode((string)$result->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('8 characters', $body['error']['message']);
    }

    public function testResetPasswordClearsToken(): void
    {
        // Create user with reset token
        $userId = 'test-user-clear-' . uniqid();
        $resetToken = 'test-reset-token-' . uniqid();
        $user = new User(
            $userId,
            'cleartoken@example.com',
            User::hashPassword('oldpassword'),
            'Test User',
            true,
            null,
            null,
            null,
            $resetToken,
            time() + 3600
        );

        $this->userRepo->create($user);

        // Reset password
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();

        $request = $requestFactory->createServerRequest('POST', '/api/auth/reset-password')
            ->withParsedBody([
                'token' => $resetToken,
                'password' => 'newpassword123'
            ]);
        $response = $responseFactory->createResponse();

        $this->handler->resetPassword($request, $response);

        // Verify token is cleared
        $updatedUser = $this->userRepo->findById($userId);
        $this->assertNull($updatedUser->getResetToken());
        $this->assertNull($updatedUser->getResetTokenExpiry());

        // Cleanup
        $this->userRepo->delete($userId);
    }

    public function testResetPasswordVerifiesEmail(): void
    {
        // Create unverified user with reset token
        $userId = 'test-user-verify-' . uniqid();
        $resetToken = 'test-reset-token-' . uniqid();
        $user = new User(
            $userId,
            'unverified@example.com',
            User::hashPassword('oldpassword'),
            'Test User',
            false, // NOT verified
            'some-verification-token',
            null,
            null,
            $resetToken,
            time() + 3600
        );

        $this->userRepo->create($user);

        // Reset password
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();

        $request = $requestFactory->createServerRequest('POST', '/api/auth/reset-password')
            ->withParsedBody([
                'token' => $resetToken,
                'password' => 'newpassword123'
            ]);
        $response = $responseFactory->createResponse();

        $this->handler->resetPassword($request, $response);

        // Verify email is now verified
        $updatedUser = $this->userRepo->findById($userId);
        $this->assertTrue($updatedUser->isEmailVerified());

        // Cleanup
        $this->userRepo->delete($userId);
    }
}
