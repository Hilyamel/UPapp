#!/bin/bash

echo "Testing Prettier configuration..."

cd frontend

# Create test file with formatting issues
cat > src/test-format-sample.ts << 'EOF'
export const test={value:"unformatted",items:[1,2,3]}
EOF

# Run Prettier
npm run format

# Check if file was formatted
if grep -q "value: 'unformatted'" src/test-format-sample.ts; then
    echo "✓ Prettier correctly formatted code"
    rm src/test-format-sample.ts
    exit 0
else
    echo "✗ Prettier did not format code"
    rm src/test-format-sample.ts
    exit 1
fi
