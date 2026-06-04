#!/usr/bin/env php
<?php
/**
 * Admin tool to reset user password or verify email
 * Usage:
 *   php admin-reset-user.php verify user@example.com
 *   php admin-reset-user.php reset-password user@example.com newPassword123
 */

require __DIR__ . '/../vendor/autoload.php';

use UpApp\Repositories\UserRepository;
use UpApp\Models\User;

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from command line\n");
}

// Parse arguments
$command = $argv[1] ?? null;
$email = $argv[2] ?? null;

if (!$command || !$email) {
    echo "Usage:\n";
    echo "  php admin-reset-user.php verify user@example.com\n";
    echo "  php admin-reset-user.php reset-password user@example.com newPassword123\n";
    exit(1);
}

$repo = new UserRepository();

// Find user
$user = $repo->findByEmail($email);
if (!$user) {
    echo "Error: User with email '{$email}' not found\n";
    exit(1);
}

echo "Found user:\n";
echo "  Email: {$user->getEmail()}\n";
echo "  Name: {$user->getFullName()}\n";
echo "  Verified: " . ($user->isEmailVerified() ? 'YES' : 'NO') . "\n";
echo "  Created: {$user->getCreatedAt()}\n";
echo "\n";

switch ($command) {
    case 'verify':
        if ($user->isEmailVerified()) {
            echo "Email is already verified.\n";
            exit(0);
        }

        // Mark as verified
        $updatedUser = new User(
            $user->getId(),
            $user->getEmail(),
            $user->getPasswordHash(),
            $user->getFullName(),
            true, // email_verified = true
            null, // clear verification token
            $user->getCreatedAt(),
            date('Y-m-d\TH:i:s\Z')
        );

        if ($repo->update($updatedUser)) {
            echo "✓ Email verified successfully! User can now log in.\n";
        } else {
            echo "✗ Failed to update user.\n";
            exit(1);
        }
        break;

    case 'reset-password':
        $newPassword = $argv[3] ?? null;
        if (!$newPassword) {
            echo "Error: New password is required\n";
            echo "Usage: php admin-reset-user.php reset-password user@example.com newPassword123\n";
            exit(1);
        }

        if (strlen($newPassword) < 8) {
            echo "Error: Password must be at least 8 characters\n";
            exit(1);
        }

        // Update password
        $updatedUser = new User(
            $user->getId(),
            $user->getEmail(),
            User::hashPassword($newPassword),
            $user->getFullName(),
            true, // also verify email
            null, // clear verification token
            $user->getCreatedAt(),
            date('Y-m-d\TH:i:s\Z')
        );

        if ($repo->update($updatedUser)) {
            echo "✓ Password reset successfully!\n";
            echo "  Email: {$email}\n";
            echo "  New password: {$newPassword}\n";
            echo "  Email verified: YES\n";
            echo "\nUser can now log in with the new password.\n";
        } else {
            echo "✗ Failed to update user.\n";
            exit(1);
        }
        break;

    default:
        echo "Error: Unknown command '{$command}'\n";
        echo "Available commands: verify, reset-password\n";
        exit(1);
}
