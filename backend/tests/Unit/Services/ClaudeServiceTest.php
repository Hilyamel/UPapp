<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use UpApp\Services\ClaudeService;

class ClaudeServiceTest extends TestCase
{
    private ClaudeService $service;

    protected function setUp(): void
    {
        $this->service = new ClaudeService();
    }

    public function testLoadSystemPromptFileExists(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('loadSystemPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($this->service);

        $this->assertIsString($prompt);
        $this->assertNotEmpty($prompt);
        $this->assertStringContainsString('empAItycznie', $prompt);
        $this->assertStringContainsString('NVC', $prompt);
    }

    public function testFormatTUPData(): void
    {
        $formData = [
            'situation_description' => 'Test situation',
            'your_feelings_freetext' => 'smutek',
            'your_feelings_selected' => ['złość', 'frustracja'],
            'your_needs_freetext' => 'szacunek',
            'your_needs_selected' => ['bezpieczeństwo']
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('formatFormDataForPrompt');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'TUP', $formData);

        $this->assertIsString($result);
        $this->assertStringContainsString('TUP', $result);
        $this->assertStringContainsString('Test situation', $result);
        $this->assertStringContainsString('smutek', $result);
        $this->assertStringContainsString('złość', $result);
        $this->assertStringContainsString('szacunek', $result);
    }

    public function testFormatDOSData(): void
    {
        $formData = [
            'person' => 'Jan K.',
            'judgment' => 'Jest nieodpowiedzialny',
            'feelings_freetext' => 'złość',
            'needs_selected' => ['zaufanie', 'współpraca']
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('formatFormDataForPrompt');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'DOS', $formData);

        $this->assertIsString($result);
        $this->assertStringContainsString('DOS', $result);
        $this->assertStringContainsString('Jan K.', $result);
        $this->assertStringContainsString('nieodpowiedzialny', $result);
        $this->assertStringContainsString('zaufanie', $result);
    }

    public function testFormatDUPData(): void
    {
        $formData = [
            'what_someone_said' => 'Nigdy mi nie pomagasz',
            'feeling_angry' => true,
            'need_support' => true
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('formatFormDataForPrompt');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'DUP', $formData);

        $this->assertIsString($result);
        $this->assertStringContainsString('DUP', $result);
        $this->assertStringContainsString('Nigdy mi nie pomagasz', $result);
    }

    public function testFallbackResponseWhenNoApiKey(): void
    {
        // Temporarily unset API key
        $originalKey = getenv('ANTHROPIC_API_KEY');
        putenv('ANTHROPIC_API_KEY=');

        $service = new ClaudeService();

        $result = $service->generateEmpatheticFeedback('TUP', [
            'situation_description' => 'Test'
        ]);

        // Restore original key
        if ($originalKey !== false) {
            putenv("ANTHROPIC_API_KEY={$originalKey}");
        }

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('Dziękuję', $result);
    }

    public function testGenerateEmpatheticFeedbackWithEmptyData(): void
    {
        $result = $this->service->generateEmpatheticFeedback('TUP', []);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }
}
