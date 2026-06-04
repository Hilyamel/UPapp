#!/usr/bin/env php
<?php
/**
 * Manual test for ClaudeService with real form data
 *
 * This script tests:
 * 1. System prompt loading (full NVC lists)
 * 2. Form data formatting
 * 3. API call simulation (shows what would be sent)
 * 4. Actual API call if ANTHROPIC_API_KEY is set
 */

require __DIR__ . '/../vendor/autoload.php';

use UpApp\Services\ClaudeService;

echo "=== ClaudeService Manual Test ===\n\n";

// Test data for TUP form (complete)
$tupData = [
    'situation_description' => 'Szef nakrzyczał na mnie przy wszystkich na spotkaniu zespołu',
    'your_feelings_freetext' => 'smutek',
    'your_feelings_selected' => ['zawstydzenie', 'złość', 'frustracja'],
    'your_needs_freetext' => 'szacunek',
    'your_needs_selected' => ['godność', 'uznanie', 'bezpieczeństwo']
];

// Test data for incomplete form
$tupDataIncomplete = [
    'situation_description' => 'Szef nakrzyczał na mnie',
    // Missing feelings and needs
];

// Test data for DOS form
$dosData = [
    'person' => 'Partner',
    'judgment' => 'Jest nieodpowiedzialny i leniwy - nigdy nie pomaga w domu',
    'feelings_freetext' => 'frustracja, złość, rozczarowanie',
    'needs_selected' => ['współpraca', 'zaufanie', 'równość']
];

$service = new ClaudeService();

// Test 1: Complete TUP form
echo "TEST 1: Complete TUP Form\n";
echo "-------------------------\n";
try {
    $feedback = $service->generateEmpatheticFeedback('TUP', $tupData);
    echo "✓ Feedback received:\n";
    echo $feedback . "\n\n";

    // Validate feedback
    echo "Validation:\n";
    $hasEmoji = preg_match('/[\x{1F300}-\x{1F9FF}]/u', $feedback);
    echo "  - Contains emoji: " . ($hasEmoji ? "✓ YES" : "✗ NO") . "\n";

    $hasNVCLanguage =
        str_contains($feedback, 'czujesz') ||
        str_contains($feedback, 'potrzeb') ||
        str_contains($feedback, 'uczuci');
    echo "  - Uses NVC language: " . ($hasNVCLanguage ? "✓ YES" : "✗ NO") . "\n";

    $sentences = preg_split('/[.!?]+/', $feedback, -1, PREG_SPLIT_NO_EMPTY);
    $sentenceCount = count($sentences);
    echo "  - Sentence count: $sentenceCount (expected: 1-4)\n";

    $isConcise = $sentenceCount >= 1 && $sentenceCount <= 4;
    echo "  - Is concise: " . ($isConcise ? "✓ YES" : "✗ NO") . "\n";

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Incomplete TUP form
echo "TEST 2: Incomplete TUP Form (should ask questions)\n";
echo "---------------------------------------------------\n";
try {
    $feedback = $service->generateEmpatheticFeedback('TUP', $tupDataIncomplete);
    echo "✓ Feedback received:\n";
    echo $feedback . "\n\n";

    // Should contain questions
    $hasQuestion = str_contains($feedback, '?');
    echo "Validation:\n";
    echo "  - Contains questions: " . ($hasQuestion ? "✓ YES" : "✗ NO") . "\n";

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: DOS form
echo "TEST 3: DOS Form\n";
echo "----------------\n";
try {
    $feedback = $service->generateEmpatheticFeedback('DOS', $dosData);
    echo "✓ Feedback received:\n";
    echo $feedback . "\n\n";

    echo "Validation:\n";
    $hasEmoji = preg_match('/[\x{1F300}-\x{1F9FF}]/u', $feedback);
    echo "  - Contains emoji: " . ($hasEmoji ? "✓ YES" : "✗ NO") . "\n";

    $hasNVCLanguage =
        str_contains($feedback, 'czujesz') ||
        str_contains($feedback, 'potrzeb') ||
        str_contains($feedback, 'uczuci');
    echo "  - Uses NVC language: " . ($hasNVCLanguage ? "✓ YES" : "✗ NO") . "\n";

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== Test Complete ===\n\n";

// Show system info
$hasApiKey = !empty(getenv('ANTHROPIC_API_KEY'));
echo "System Info:\n";
echo "  - ANTHROPIC_API_KEY: " . ($hasApiKey ? "SET" : "NOT SET (using fallback)") . "\n";

if (!$hasApiKey) {
    echo "\nTo test with real Claude API:\n";
    echo "1. Get API key from: https://console.anthropic.com/settings/keys\n";
    echo "2. Add to .env: ANTHROPIC_API_KEY=sk-ant-api03-xxxxx\n";
    echo "3. Run this script again\n";
}
