<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use UpApp\Services\ClaudeService;

/**
 * Integration tests for Claude API
 * Requires ANTHROPIC_API_KEY environment variable
 */
class ClaudeAPIIntegrationTest extends TestCase
{
    private ClaudeService $service;
    private bool $hasApiKey;

    protected function setUp(): void
    {
        $this->service = new ClaudeService();
        $this->hasApiKey = !empty(getenv('ANTHROPIC_API_KEY'));
    }

    public function testGenerateFeedbackForCompleteTUPForm(): void
    {
        if (!$this->hasApiKey) {
            $this->markTestSkipped('ANTHROPIC_API_KEY not set');
        }

        $formData = [
            'situation_description' => 'Szef nakrzyczał na mnie przy wszystkich',
            'your_feelings_freetext' => 'smutek',
            'your_feelings_selected' => ['zawstydzenie', 'złość'],
            'your_needs_freetext' => 'szacunek',
            'your_needs_selected' => ['godność', 'uznanie']
        ];

        $feedback = $this->service->generateEmpatheticFeedback('TUP', $formData);

        $this->assertIsString($feedback);
        $this->assertNotEmpty($feedback);
        $this->assertGreaterThan(20, strlen($feedback), 'Feedback should be substantive');

        // Check for emotional content (emojis)
        $this->assertMatchesRegularExpression('/[\x{1F300}-\x{1F9FF}]/u', $feedback, 'Should contain emoji');

        // Check for NVC language patterns
        $hasNVCLanguage =
            str_contains($feedback, 'czujesz') ||
            str_contains($feedback, 'potrzeb') ||
            str_contains($feedback, 'uczuci');
        $this->assertTrue($hasNVCLanguage, 'Should use NVC language');
    }

    public function testGenerateFeedbackForIncompleteTUPForm(): void
    {
        if (!$this->hasApiKey) {
            $this->markTestSkipped('ANTHROPIC_API_KEY not set');
        }

        $formData = [
            'situation_description' => 'Szef nakrzyczał',
            // Missing feelings and needs
        ];

        $feedback = $this->service->generateEmpatheticFeedback('TUP', $formData);

        $this->assertIsString($feedback);
        $this->assertNotEmpty($feedback);

        // Should ask questions to help user explore
        $hasQuestion = str_contains($feedback, '?');
        $this->assertTrue($hasQuestion, 'Should contain questions for incomplete form');
    }

    public function testGenerateFeedbackForDOSForm(): void
    {
        if (!$this->hasApiKey) {
            $this->markTestSkipped('ANTHROPIC_API_KEY not set');
        }

        $formData = [
            'person' => 'Partner',
            'judgment' => 'Jest nieodpowiedzialny i leniwy',
            'feelings_freetext' => 'frustracja, złość',
            'needs_selected' => ['współpraca', 'zaufanie']
        ];

        $feedback = $this->service->generateEmpatheticFeedback('DOS', $formData);

        $this->assertIsString($feedback);
        $this->assertNotEmpty($feedback);
        $this->assertGreaterThan(20, strlen($feedback));
    }

    public function testGenerateFeedbackForDUPForm(): void
    {
        if (!$this->hasApiKey) {
            $this->markTestSkipped('ANTHROPIC_API_KEY not set');
        }

        $formData = [
            'what_someone_said' => 'Nigdy mi nie pomagasz, zawsze myślisz tylko o sobie',
            'feeling_sad' => true,
            'feeling_frustrated' => true,
            'need_cooperation' => true,
            'need_appreciation' => true
        ];

        $feedback = $this->service->generateEmpatheticFeedback('DUP', $formData);

        $this->assertIsString($feedback);
        $this->assertNotEmpty($feedback);
    }

    public function testFeedbackContainsAppropriateEmojis(): void
    {
        if (!$this->hasApiKey) {
            $this->markTestSkipped('ANTHROPIC_API_KEY not set');
        }

        $formData = [
            'judgment' => 'Szef jest okropny',
            'feelings_selected' => ['złość', 'frustracja'],
            'needs_selected' => ['szacunek']
        ];

        $feedback = $this->service->generateEmpatheticFeedback('DOS', $formData);

        // Should contain 1-2 emojis
        preg_match_all('/[\x{1F300}-\x{1F9FF}]/u', $feedback, $matches);
        $emojiCount = count($matches[0]);

        $this->assertGreaterThanOrEqual(1, $emojiCount, 'Should contain at least 1 emoji');
        $this->assertLessThanOrEqual(3, $emojiCount, 'Should not overuse emojis');
    }

    public function testFeedbackLengthIsAppropriate(): void
    {
        if (!$this->hasApiKey) {
            $this->markTestSkipped('ANTHROPIC_API_KEY not set');
        }

        $formData = [
            'situation_description' => 'Kłótnia z partnerem',
            'your_feelings_freetext' => 'smutek',
            'your_needs_freetext' => 'zrozumienie'
        ];

        $feedback = $this->service->generateEmpatheticFeedback('TUP', $formData);

        // Should be concise (1-3 sentences as per prompt)
        $sentences = preg_split('/[.!?]+/', $feedback, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = count($sentences);

        $this->assertGreaterThanOrEqual(1, $sentenceCount);
        $this->assertLessThanOrEqual(5, $sentenceCount, 'Feedback should be concise (1-4 sentences)');
    }

    public function testResponseTimeIsReasonable(): void
    {
        if (!$this->hasApiKey) {
            $this->markTestSkipped('ANTHROPIC_API_KEY not set');
        }

        $formData = ['situation_description' => 'Test'];

        $startTime = microtime(true);
        $this->service->generateEmpatheticFeedback('TUP', $formData);
        $duration = microtime(true) - $startTime;

        // API should respond within 10 seconds
        $this->assertLessThan(10.0, $duration, 'Claude API should respond within 10 seconds');
    }
}
