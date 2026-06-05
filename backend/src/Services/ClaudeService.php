<?php

namespace UpApp\Services;

/**
 * Claude API Service for generating empathetic NVC feedback
 */
class ClaudeService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private string $model = 'claude-sonnet-4-20250514';
    private string $promptPath;

    public function __construct()
    {
        // Read from $_ENV (populated by Dotenv) or fallback to getenv()
        $this->apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? getenv('ANTHROPIC_API_KEY') ?: '';
        $this->promptPath = __DIR__ . '/../../../empathy-prompt.txt';

        if (empty($this->apiKey)) {
            error_log('WARNING: ANTHROPIC_API_KEY not set in environment');
        }
    }

    /**
     * Generate empathetic feedback using Claude API
     *
     * @param string $formType Form type (TUP, DUP, DOS)
     * @param array $formData Form data
     * @return string Generated feedback
     * @throws \Exception If API call fails
     */
    public function generateEmpatheticFeedback(string $formType, array $formData): string
    {
        if (empty($this->apiKey)) {
            // Fallback to simple response if no API key
            return $this->getFallbackResponse($formType);
        }

        $systemPrompt = $this->loadSystemPrompt();
        $userMessage = $this->formatFormDataForPrompt($formType, $formData);

        try {
            return $this->callClaudeAPI($systemPrompt, $userMessage);
        } catch (\Exception $e) {
            error_log('Claude API error: ' . $e->getMessage());
            // Fallback to simple response on error
            return $this->getFallbackResponse($formType);
        }
    }

    /**
     * Load system prompt from empathy-prompt.txt
     */
    private function loadSystemPrompt(): string
    {
        if (!file_exists($this->promptPath)) {
            throw new \Exception("Empathy prompt file not found: {$this->promptPath}");
        }

        $prompt = file_get_contents($this->promptPath);
        if ($prompt === false) {
            throw new \Exception("Failed to read empathy prompt file");
        }

        return $prompt;
    }

    /**
     * Format form data into a user message for Claude
     */
    private function formatFormDataForPrompt(string $formType, array $formData): string
    {
        $message = "Typ formularza: {$formType}\n\n";

        switch ($formType) {
            case 'TUP':
                $message .= $this->formatTUPData($formData);
                break;
            case 'DUP':
                $message .= $this->formatDUPData($formData);
                break;
            case 'DOS':
                $message .= $this->formatDOSData($formData);
                break;
        }

        return $message;
    }

    private function formatTUPData(array $data): string
    {
        $parts = [];

        if (!empty($data['situation_description'])) {
            $parts[] = "Opis sytuacji: {$data['situation_description']}";
        }
        if (!empty($data['observation'])) {
            $parts[] = "Obserwacja: {$data['observation']}";
        }
        if (!empty($data['quote'])) {
            $parts[] = "Cytat: {$data['quote']}";
        }
        if (!empty($data['judgments'])) {
            $parts[] = "Osądy: {$data['judgments']}";
        }

        // Your feelings and needs
        $yourFeelings = [];
        if (!empty($data['your_feelings_freetext'])) {
            $yourFeelings[] = $data['your_feelings_freetext'];
        }
        if (!empty($data['your_feelings_selected'])) {
            $selected = is_array($data['your_feelings_selected'])
                ? implode(', ', $data['your_feelings_selected'])
                : $data['your_feelings_selected'];
            $yourFeelings[] = "Wybrane: {$selected}";
        }
        if (!empty($yourFeelings)) {
            $parts[] = "Twoje uczucia: " . implode('; ', $yourFeelings);
        }

        $yourNeeds = [];
        if (!empty($data['your_needs_freetext'])) {
            $yourNeeds[] = $data['your_needs_freetext'];
        }
        if (!empty($data['your_needs_selected'])) {
            $selected = is_array($data['your_needs_selected'])
                ? implode(', ', $data['your_needs_selected'])
                : $data['your_needs_selected'];
            $yourNeeds[] = "Wybrane: {$selected}";
        }
        if (!empty($yourNeeds)) {
            $parts[] = "Twoje potrzeby: " . implode('; ', $yourNeeds);
        }

        // Their feelings and needs
        $theirFeelings = [];
        if (!empty($data['their_feelings_freetext'])) {
            $theirFeelings[] = $data['their_feelings_freetext'];
        }
        if (!empty($data['their_feelings_selected'])) {
            $selected = is_array($data['their_feelings_selected'])
                ? implode(', ', $data['their_feelings_selected'])
                : $data['their_feelings_selected'];
            $theirFeelings[] = "Wybrane: {$selected}";
        }
        if (!empty($theirFeelings)) {
            $parts[] = "Uczucia drugiej osoby: " . implode('; ', $theirFeelings);
        }

        $theirNeeds = [];
        if (!empty($data['their_needs_freetext'])) {
            $theirNeeds[] = $data['their_needs_freetext'];
        }
        if (!empty($data['their_needs_selected'])) {
            $selected = is_array($data['their_needs_selected'])
                ? implode(', ', $data['their_needs_selected'])
                : $data['their_needs_selected'];
            $theirNeeds[] = "Wybrane: {$selected}";
        }
        if (!empty($theirNeeds)) {
            $parts[] = "Potrzeby drugiej osoby: " . implode('; ', $theirNeeds);
        }

        return implode("\n\n", $parts);
    }

    private function formatDUPData(array $data): string
    {
        $parts = [];

        if (!empty($data['what_someone_said'])) {
            $parts[] = "Co ktoś powiedział: {$data['what_someone_said']}";
        }

        // Collect feelings and needs
        $feelings = $this->collectPrefixedItems($data, 'feeling_');
        if (!empty($feelings)) {
            $parts[] = "Uczucia: " . implode(', ', $feelings);
        }

        $needs = $this->collectPrefixedItems($data, 'need_');
        if (!empty($needs)) {
            $parts[] = "Potrzeby: " . implode(', ', $needs);
        }

        return implode("\n\n", $parts);
    }

    private function formatDOSData(array $data): string
    {
        $parts = [];

        if (!empty($data['person'])) {
            $parts[] = "Osoba: {$data['person']}";
        }
        if (!empty($data['judgment'])) {
            $parts[] = "Osąd: {$data['judgment']}";
        }

        if (!empty($data['feelings_freetext'])) {
            $parts[] = "Uczucia: {$data['feelings_freetext']}";
        }
        if (!empty($data['feelings_selected']) && is_array($data['feelings_selected'])) {
            $parts[] = "Wybrane uczucia: " . implode(', ', $data['feelings_selected']);
        }

        if (!empty($data['needs_freetext'])) {
            $parts[] = "Potrzeby: {$data['needs_freetext']}";
        }
        if (!empty($data['needs_selected']) && is_array($data['needs_selected'])) {
            $parts[] = "Wybrane potrzeby: " . implode(', ', $data['needs_selected']);
        }

        return implode("\n\n", $parts);
    }

    private function collectPrefixedItems(array $data, string $prefix): array
    {
        $items = [];
        foreach ($data as $key => $value) {
            if (strpos($key, $prefix) === 0 && !empty($value)) {
                if (is_array($value)) {
                    $items = array_merge($items, $value);
                } else {
                    $items[] = $value;
                }
            }
        }
        return $items;
    }

    /**
     * Call Claude API with system prompt and user message
     */
    private function callClaudeAPI(string $systemPrompt, string $userMessage): string
    {
        $data = [
            'model' => $this->model,
            'max_tokens' => 1024,
            'system' => $systemPrompt,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $userMessage
                ]
            ]
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $errorMsg = "Claude API returned HTTP {$httpCode}: {$response}";
            error_log($errorMsg);
            throw new \Exception($errorMsg);
        }

        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse Claude API response');
        }

        if (empty($responseData['content'][0]['text'])) {
            throw new \Exception('No text content in Claude API response');
        }

        return $responseData['content'][0]['text'];
    }

    /**
     * Fallback response when API is unavailable
     */
    private function getFallbackResponse(string $formType): string
    {
        return "Dziękuję za podzielenie się swoimi przemyśleniami. Twoja praca nad rozpoznaniem uczuć i potrzeb jest wartościowa. 🌱";
    }
}
