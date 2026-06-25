<?php

declare(strict_types=1);

namespace Deleep\ArticleToolkit\Services;

use Deleep\ArticleToolkit\Config\Config;
use Throwable;

/**
 * Generates an SEO meta description under 160 characters.
 *
 * Demo mode trims the first paragraph to the SEO limit so the output is
 * usable for layout testing without an API key.
 */
final class MetaDescriptionGenerator
{
    private const MAX_LENGTH = 160;

    public function __construct(private readonly OpenAiClient $openAi)
    {
    }

    public function generate(string $article): string
    {
        if (Config::isDemoMode()) {
            return $this->demoMeta($article);
        }

        $system = 'You write SEO meta descriptions for news articles. '
            . 'Output must be under 160 characters. Output only the meta description, no quotes.';

        $user = "Write an SEO meta description for the article below. "
            . "Under 160 characters. Plain prose. No emojis.\n\n" . $article;

        try {
            $response = $this->openAi->chat($system, $user, 0.5);
            return $this->trimToLimit($response);
        } catch (Throwable) {
            return $this->demoMeta($article);
        }
    }

    private function demoMeta(string $article): string
    {
        $trimmed = trim($article);
        if ($trimmed === '') {
            return 'Sample SEO meta description for empty article input.';
        }
        $singleLine = preg_replace('/\s+/u', ' ', $trimmed) ?? $trimmed;
        return $this->trimToLimit($singleLine);
    }

    private function trimToLimit(string $text): string
    {
        $clean = trim($text);
        if (mb_strlen($clean) <= self::MAX_LENGTH) {
            return $clean;
        }
        $cut = mb_substr($clean, 0, self::MAX_LENGTH - 3);
        $lastSpace = mb_strrpos($cut, ' ');
        if ($lastSpace !== false && $lastSpace > 100) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }
        return rtrim($cut, " ,.;:") . '...';
    }
}
