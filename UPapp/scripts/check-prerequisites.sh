#!/bin/bash

echo "Checking prerequisites for UPapp development..."

# Check Node.js
if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    echo "✓ Node.js: $NODE_VERSION"
else
    echo "✗ Node.js not found. Please install Node.js 18+"
    exit 1
fi

# Check PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php --version | head -n 1)
    echo "✓ PHP: $PHP_VERSION"
else
    echo "✗ PHP not found. Please install PHP 8.1+"
    exit 1
fi

# Check Composer
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version | head -n 1)
    echo "✓ Composer: $COMPOSER_VERSION"
else
    echo "✗ Composer not found. Please install Composer"
    exit 1
fi

# Check AWS CLI
if command -v aws &> /dev/null; then
    AWS_VERSION=$(aws --version)
    echo "✓ AWS CLI: $AWS_VERSION"
else
    echo "✗ AWS CLI not found. Please install and configure AWS CLI"
    exit 1
fi

# Check Git
if command -v git &> /dev/null; then
    GIT_VERSION=$(git --version)
    echo "✓ Git: $GIT_VERSION"
else
    echo "✗ Git not found. Please install Git"
    exit 1
fi

echo ""
echo "All prerequisites installed!"
