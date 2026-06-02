#!/bin/bash

echo "Testing PHP_CodeSniffer configuration..."

cd backend

# Create test file with PSR-12 violations
mkdir -p src/Test
cat > src/Test/TestLintSample.php << 'EOF'
<?php
class test_class {
function badMethod(){
echo "no proper indentation";
}
}
EOF

# Run PHP_CodeSniffer (should fail)
if composer lint 2>&1 | grep -q "FOUND"; then
    echo "✓ PHP_CodeSniffer correctly detects PSR-12 violations"
    rm -rf src/Test
    exit 0
else
    echo "✗ PHP_CodeSniffer did not detect violations"
    rm -rf src/Test
    exit 1
fi
