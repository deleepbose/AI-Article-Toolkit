<?php

declare(strict_types=1);

namespace Deleep\ArticleToolkit\Services;

use Deleep\ArticleToolkit\Config\Config;
use JsonException;
use Throwable;

/**
 * Generates headline suggestions for an article.
 *
 * When an OpenAI API key is configured, uses the chat API with a structured
 * prompt that asks for a JSON list. When no key is set, returns a small set
 * of demo headlines derived from the first sentence so the UI still works
 * for reviewers without API access.
 */
final class HeadlineGenerator
{
    public function __construct(private readonly OpenAiClient $openAi)
    {
    }

    public function generate(string $article, int $count = 5): array
    {
        if (Config::isDemoMode()) {
            return $this->demoHeadlines($article, $count);
        }

        $system = 'You are a senior news editor. You write tight, accurate, clickable headlines. '
            . 'You return only valid JSON, no commentary, no markdown.';

        $user = "Write {$count} headline options for the article below. "
            . "Return a JSON array of strings, each headline under 12 words. "
            . "Article:\n\n" . $article;

        try {
            $response = $this->openAi->chat($system, $user, 0.8);
            $parsed = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($parsed)) {
                return $this->demoHeadlines($article, $count);
            }
            return array_slice(array_map('strval', $parsed), 0, $count);
        } catch (JsonException | Throwable) {
            return $this->demoHeadlines($article, $count);
        }
    }

    private function demoHeadlines(string $article, int $count): array
    {
        $firstSentence = $this->firstSentence($article);
        if ($firstSentence === '') {
            $firstSentence = 'Article submitted for analysis';
        }

        $base = [
            $firstSentence,
            'Breaking: ' . $firstSentence,
            $firstSentence . ' - What you need to know',
            'Analysis: ' . $firstSentence,
            'Inside the story: ' . $firstSentence,
            $firstSentence . ' - explained',
            'Why ' . lcfirst($firstSentence) . ' matters',
        ];
        return array_slice($base, 0, $count);
    }

    private function firstSentence(string $article): string
    {
        $trimmed = trim($article);
        if ($trimmed === '') {
            return '';
        }
        $parts = preg_split('/[.!?]+/u', $trimmed, 2);
        if ($parts === false || count($parts) === 0) {
            return '';
        }
        $sentence = trim($parts[0]);
        if (strlen($sentence) > 80) {
            $sentence = rtrim(substr($sentence, 0, 80)) . '...';
        }
        return $sentence;
    }
}
