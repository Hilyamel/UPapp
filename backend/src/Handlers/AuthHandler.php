<?php

namespace UpApp\Handlers;

use UpApp\Models\User;
use UpApp\Repositories\UserRepository;
use UpApp\Repositories\FormRepository;
use UpApp\Services\EmailService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;

class AuthHandler
{
    private UserRepository $userRepository;
    private FormRepository $formRepository;
    private EmailService $emailService;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->formRepository = new FormRepository();
        $this->emailService = new EmailService();
    }

    public function register(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        // Validate input
        if (empty($data['email']) || empty($data['password'])) {
            return $this->errorResponse($response, 'Email and password are required', 400);
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->errorResponse($response, 'Invalid email format', 400);
        }

        if (strlen($data['password']) < 8) {
            return $this->errorResponse($response, 'Password must be at least 8 characters', 400);
        }

        // Check if user already exists
        $existingUser = $this->userRepository->findByEmail($data['email']);
        if ($existingUser) {
            return $this->errorResponse($response, 'User with this email already exists', 409);
        }

        // Create new user with verification token
        $verificationToken = User::generateVerificationToken();
        $user = new User(
            Uuid::uuid4()->toString(),
            $data['email'],
            User::hashPassword($data['password']),
            $data['full_name'] ?? null,
            false, // email not verified yet
            $verificationToken
        );

        if (!$this->userRepository->create($user)) {
            return $this->errorResponse($response, 'Failed to create user', 500);
        }

        // Send verification email
        $emailSent = $this->emailService->sendVerificationEmail(
            $user->getEmail(),
            $user->getFullName() ?? 'User',
            $verificationToken
        );

        if (!$emailSent) {
            error_log("Failed to send verification email to: " . $user->getEmail());
        }

        // Don't start session yet - user must verify email first
        return $this->successResponse($response, [
            'message' => 'Registration successful. Please check your email to verify your account.',
            'email' => $user->getEmail()
        ], 201);
    }

    public function login(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (empty($data['email']) || empty($data['password'])) {
            return $this->errorResponse($response, 'Email and password are required', 400);
        }

        // Find user
        $user = $this->userRepository->findByEmail($data['email']);
        if (!$user || !$user->verifyPassword($data['password'])) {
            return $this->errorResponse($response, 'Invalid email or password', 401);
        }

        // Check if email is verified
        if (!$user->isEmailVerified()) {
            return $this->errorResponse(
                $response,
                'Please verify your email address before logging in. Check your inbox for the verification link.',
                403,
                'EMAIL_NOT_VERIFIED'
            );
        }

        // Start session
        session_start();
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_email'] = $user->getEmail();

        return $this->successResponse($response, [
            'user' => $user->toArray()
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        session_start();
        session_destroy();

        return $this->successResponse($response, [
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request, Response $response): Response
    {
        session_start();

        if (empty($_SESSION['user_id'])) {
            return $this->errorResponse($response, 'Not authenticated', 401);
        }

        $user = $this->userRepository->findById($_SESSION['user_id']);
        if (!$user) {
            session_destroy();
            return $this->errorResponse($response, 'User not found', 404);
        }

        return $this->successResponse($response, $user->toArray());
    }

    public function verifyEmail(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $token = $queryParams['token'] ?? null;

        if (!$token) {
            return $this->errorResponse($response, 'Verification token is required', 400);
        }

        // Find user by verification token
        $user = $this->userRepository->findByVerificationToken($token);
        if (!$user) {
            return $this->errorResponse($response, 'Invalid or expired verification token', 400);
        }

        // Already verified
        if ($user->isEmailVerified()) {
            return $this->successResponse($response, [
                'message' => 'Email already verified. You can now log in.'
            ]);
        }

        // Mark email as verified
        $user->markEmailAsVerified();
        if (!$this->userRepository->update($user)) {
            return $this->errorResponse($response, 'Failed to verify email', 500);
        }

        return $this->successResponse($response, [
            'message' => 'Email verified successfully! You can now log in.',
            'email' => $user->getEmail()
        ]);
    }

    public function resendVerification(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (empty($data['email'])) {
            return $this->errorResponse($response, 'Email is required', 400);
        }

        // Find user
        $user = $this->userRepository->findByEmail($data['email']);
        if (!$user) {
            // Don't reveal if user exists or not for security
            return $this->successResponse($response, [
                'message' => 'If an unverified account exists with this email, a verification link has been sent.'
            ]);
        }

        // Check if already verified
        if ($user->isEmailVerified()) {
            return $this->errorResponse($response, 'This email is already verified. Please log in.', 400);
        }

        // Generate new token and invalidate old one
        $newToken = User::generateVerificationToken();
        $user = new User(
            $user->getId(),
            $user->getEmail(),
            $user->getPasswordHash(),
            $user->getFullName(),
            false,
            $newToken,
            $user->getCreatedAt(),
            date('Y-m-d\TH:i:s\Z')
        );

        if (!$this->userRepository->update($user)) {
            return $this->errorResponse($response, 'Failed to generate new verification token', 500);
        }

        // Send verification email
        $emailSent = $this->emailService->sendVerificationEmail(
            $user->getEmail(),
            $user->getFullName() ?? 'User',
            $newToken
        );

        if (!$emailSent) {
            error_log("Failed to resend verification email to: " . $user->getEmail());
        }

        return $this->successResponse($response, [
            'message' => 'Verification email sent. Please check your inbox.',
            'email' => $user->getEmail()
        ]);
    }

    public function deleteAccount(Request $request, Response $response): Response
    {
        session_start();

        if (empty($_SESSION['user_id'])) {
            return $this->errorResponse($response, 'Not authenticated', 401);
        }

        $userId = $_SESSION['user_id'];

        // Delete all user's forms first
        $this->formRepository->deleteAllByUserId($userId);

        // Delete user account
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return $this->errorResponse($response, 'User not found', 404);
        }

        if (!$this->userRepository->delete($userId)) {
            return $this->errorResponse($response, 'Failed to delete account', 500);
        }

        // Destroy session
        session_destroy();

        return $this->successResponse($response, [
            'message' => 'Account deleted successfully'
        ]);
    }

    public function forgotPassword(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (empty($data['email'])) {
            return $this->errorResponse($response, 'Email is required', 400);
        }

        // Find user
        $user = $this->userRepository->findByEmail($data['email']);

        // Always return success (don't reveal if user exists)
        if (!$user) {
            return $this->successResponse($response, [
                'message' => 'If an account exists with this email, you will receive password reset instructions.'
            ]);
        }

        // Generate reset token (valid for 24 hours)
        $resetToken = User::generateResetToken();
        $expiry = time() + 86400; // 24 hours

        // Update user with reset token
        $user->setResetToken($resetToken, $expiry);
        if (!$this->userRepository->update($user)) {
            return $this->errorResponse($response, 'Failed to generate reset token', 500);
        }

        // Send reset email
        $emailSent = $this->emailService->sendPasswordResetEmail(
            $user->getEmail(),
            $user->getFullName() ?? 'User',
            $resetToken
        );

        if (!$emailSent) {
            error_log("Failed to send password reset email to: " . $user->getEmail());
        }

        return $this->successResponse($response, [
            'message' => 'If an account exists with this email, you will receive password reset instructions.'
        ]);
    }

    public function resetPassword(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (empty($data['token']) || empty($data['password'])) {
            return $this->errorResponse($response, 'Token and new password are required', 400);
        }

        if (strlen($data['password']) < 8) {
            return $this->errorResponse($response, 'Password must be at least 8 characters', 400);
        }

        // Find user by reset token
        $user = $this->userRepository->findByResetToken($data['token']);
        if (!$user) {
            return $this->errorResponse($response, 'Invalid or expired reset token', 400);
        }

        // Check if token is still valid
        if (!$user->isResetTokenValid()) {
            return $this->errorResponse($response, 'Reset token has expired. Please request a new one.', 400);
        }

        // Update password and clear reset token
        $user->setPassword($data['password']);
        $user->clearResetToken();

        // Also ensure email is verified
        if (!$user->isEmailVerified()) {
            $user->markEmailAsVerified();
        }

        if (!$this->userRepository->update($user)) {
            return $this->errorResponse($response, 'Failed to reset password', 500);
        }

        return $this->successResponse($response, [
            'message' => 'Password reset successfully. You can now log in with your new password.',
            'email' => $user->getEmail()
        ]);
    }

    public function changePassword(Request $request, Response $response): Response
    {
        session_start();

        if (empty($_SESSION['user_id'])) {
            return $this->errorResponse($response, 'Not authenticated', 401);
        }

        $data = json_decode($request->getBody()->getContents(), true);

        if (empty($data['current_password']) || empty($data['new_password'])) {
            return $this->errorResponse($response, 'Current password and new password are required', 400);
        }

        if (strlen($data['new_password']) < 8) {
            return $this->errorResponse($response, 'New password must be at least 8 characters', 400);
        }

        // Get user
        $user = $this->userRepository->findById($_SESSION['user_id']);
        if (!$user) {
            return $this->errorResponse($response, 'User not found', 404);
        }

        // Verify current password
        if (!$user->verifyPassword($data['current_password'])) {
            return $this->errorResponse($response, 'Current password is incorrect', 401);
        }

        // Check if new password is different
        if ($user->verifyPassword($data['new_password'])) {
            return $this->errorResponse($response, 'New password must be different from current password', 400);
        }

        // Update password
        $user->setPassword($data['new_password']);

        if (!$this->userRepository->update($user)) {
            return $this->errorResponse($response, 'Failed to change password', 500);
        }

        return $this->successResponse($response, [
            'message' => 'Password changed successfully'
        ]);
    }

    private function successResponse(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $data,
            'error' => null
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    private function errorResponse(Response $response, string $message, int $status = 400, string $code = 'AUTH_ERROR'): Response
    {
        $response->getBody()->write(json_encode([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
