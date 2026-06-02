#!/bin/bash

echo "Testing ESLint configuration..."

cd frontend

# Test that ESLint catches violations
echo "Creating test file with linting errors..."
cat > src/test-lint-sample.ts << 'EOF'
const unused_variable = 'test';
export const badFunction = () => {
console.log('bad indentation')
}
EOF

# Run ESLint (should fail)
if npm run lint 2>&1 | grep -q "error"; then
    echo "✓ ESLint correctly detects violations"
    rm src/test-lint-sample.ts
    exit 0
else
    echo "✗ ESLint did not detect violations"
    rm src/test-lint-sample.ts
    exit 1
fi
