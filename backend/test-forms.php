<?php

require __DIR__ . '/vendor/autoload.php';

use UpApp\Config\Environment;
use UpApp\Repositories\FormRepository;
use UpApp\Repositories\UserRepository;

Environment::load();

$userRepo = new UserRepository();
$formRepo = new FormRepository();

echo "=== Testing Forms ===\n\n";

$user = $userRepo->findByEmail('janczewski.piotr@gmail.com');

if (!$user) {
    echo "✗ User not found!\n";
    exit(1);
}

echo "✓ User found: " . $user->getEmail() . "\n";
echo "  User ID: " . $user->getId() . "\n\n";

echo "Fetching forms...\n";
$forms = $formRepo->findByUserId($user->getId());

echo "Found " . count($forms) . " forms\n\n";

foreach ($forms as $form) {
    echo "- " . $form->getFormType() . " (" . $form->getId() . ")\n";
    echo "  Title: " . ($form->getTitle() ?: '(no title)') . "\n";
    echo "  Status: " . $form->getCompletionStatus() . "\n";
    echo "  Created: " . $form->getCreatedAt() . "\n\n";
}
