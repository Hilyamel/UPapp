<?php

require __DIR__ . '/vendor/autoload.php';

use UpApp\Config\Environment;
use UpApp\Repositories\UserRepository;

Environment::load();

$userRepo = new UserRepository();

echo "=== Testing Password Reset Flow ===\n\n";

// Find user
$user = $userRepo->findByEmail('janczewski.piotr@gmail.com');

if (!$user) {
    echo "✗ User not found!\n";
    exit(1);
}

echo "✓ User found: " . $user->getEmail() . "\n";
echo "  User ID: " . $user->getId() . "\n";
echo "  Email verified: " . ($user->isEmailVerified() ? 'Yes' : 'No') . "\n";

// Check reset token
$resetToken = $user->getResetToken();
$resetExpiry = $user->getResetTokenExpiry();

echo "\nReset Token Status:\n";
if ($resetToken) {
    echo "  Token: " . substr($resetToken, 0, 20) . "...\n";
    echo "  Expiry: " . date('Y-m-d H:i:s', $resetExpiry) . "\n";
    echo "  Valid: " . ($user->isResetTokenValid() ? 'Yes' : 'No (expired)') . "\n";
    echo "  Time left: " . round(($resetExpiry - time()) / 3600, 2) . " hours\n";
} else {
    echo "  No reset token set\n";
}
