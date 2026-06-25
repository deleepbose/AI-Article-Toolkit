<?php

declare(strict_types=1);

namespace Deleep\ArticleToolkit\Services;

use Deleep\ArticleToolkit\Config\Config;
use RuntimeException;

/**
 * Thin wrapper around the OpenAI Chat Completions API.
 *
 * Uses curl directly to avoid pulling in a full HTTP client for a portfolio
 * project. Returns the first message content as a string. Caller is
 * responsible for parsing structured output (for example, JSON lists).
 */
final class OpenAiClient
{
    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    private const TIMEOUT_SECONDS = 30;

    public function chat(string $systemPrompt, string $userPrompt, float $temperature = 0.7): string
    {
        $apiKey = Config::openAiApiKey();
        if ($apiKey === null) {
            throw new RuntimeException('OPENAI_API_KEY is not set. Demo mode should have been chosen upstream.');
        }

        $payload = [
            'model' => Config::openAiModel(),
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => $temperature,
        ];

        $ch = curl_init(self::ENDPOINT);
        if ($ch === false) {
            throw new RuntimeException('Failed to initialise curl.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('OpenAI request failed: ' . $curlError);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException('OpenAI returned HTTP ' . $httpCode . ': ' . $response);
        }

        $decoded = json_decode((string) $response, true, 512, JSON_THROW_ON_ERROR);
        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || $content === '') {
            throw new RuntimeException('OpenAI response did not contain message content.');
        }

        return trim($content);
    }
}
