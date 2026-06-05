<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use UpApp\Config\Environment;
use UpApp\Services\ClaudeService;

class ClaudeServiceTest extends TestCase
{
    private ClaudeService $service;

    public static function setUpBeforeClass(): void
    {
        // Load .env from project root before any tests
        Environment::load();
    }

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
        // Validate structure per empathy-prompt.txt
        $this->assertStringContainsString('OBSERWACJA', $prompt);
        $this->assertStringContainsString('UCZUCIE', $prompt);
        $this->assertStringContainsString('POTRZEBA', $prompt);
        $this->assertStringContainsString('PYTANIE', $prompt);
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
        // Temporarily unset API key from both $_ENV and getenv()
        $originalEnvKey = $_ENV['ANTHROPIC_API_KEY'] ?? null;
        $originalGetenvKey = getenv('ANTHROPIC_API_KEY');

        unset($_ENV['ANTHROPIC_API_KEY']);
        putenv('ANTHROPIC_API_KEY=');

        $service = new ClaudeService();

        $result = $service->generateEmpatheticFeedback('TUP', [
            'situation_description' => 'Test'
        ]);

        // Restore original keys
        if ($originalEnvKey !== null) {
            $_ENV['ANTHROPIC_API_KEY'] = $originalEnvKey;
        }
        if ($originalGetenvKey !== false) {
            putenv("ANTHROPIC_API_KEY={$originalGetenvKey}");
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

    /**
     * Integration test - validates Claude API response structure
     * Requires ANTHROPIC_API_KEY in environment
     *
     * @group integration
     */
    public function testClaudeAPIResponseStructure(): void
    {
        $apiKey = getenv('ANTHROPIC_API_KEY');
        if (empty($apiKey)) {
            $this->markTestSkipped('ANTHROPIC_API_KEY not set - skipping integration test');
        }

        $testData = [
            'situation_description' => 'Szef nakrzyczał na mnie podczas spotkania',
            'your_feelings_freetext' => 'złość',
            'your_needs_freetext' => 'szacunek'
        ];

        $result = $this->service->generateEmpatheticFeedback('TUP', $testData);

        // Validate response is not fallback
        $this->assertStringNotContainsString('Dziękuję za podzielenie się', $result);

        // Validate structure according to empathy-prompt.txt
        // Response should contain questions about feelings/needs
        $this->assertMatchesRegularExpression('/czy\s+czujesz/i', $result);

        // Should mention feelings or needs
        $hasFeelingsOrNeeds =
            stripos($result, 'uczucie') !== false ||
            stripos($result, 'czujesz') !== false ||
            stripos($result, 'potrzeb') !== false;
        $this->assertTrue($hasFeelingsOrNeeds, 'Response should mention feelings or needs');
    }
}
