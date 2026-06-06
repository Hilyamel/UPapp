<?php

require __DIR__ . '/vendor/autoload.php';

use UpApp\Config\Environment;
use UpApp\Repositories\UserRepository;

Environment::load();

$userRepo = new UserRepository();
$user = $userRepo->findByEmail('janczewski.piotr@gmail.com');

if ($user && $user->getResetToken()) {
    echo $user->getResetToken();
} else {
    echo "NO_TOKEN";
}
