<?php

declare(strict_types=1);

namespace Deleep\ArticleToolkit\Services;

use Deleep\ArticleToolkit\Config\Config;
use Throwable;

/**
 * Generates a 2-3 sentence summary of an article.
 *
 * Demo mode returns a deterministic snippet built from the first two sentences,
 * which keeps the API contract usable for reviewers without an API key.
 */
final class SummaryGenerator
{
    public function __construct(private readonly OpenAiClient $openAi)
    {
    }

    public function generate(string $article): string
    {
        if (Config::isDemoMode()) {
            return $this->demoSummary($article);
        }

        $system = 'You are a news editor writing tight article summaries. '
            . 'Return only the summary text, no preface, no bullet points.';

        $user = "Summarise the article below in 2 to 3 sentences. "
            . "Be factual. Do not editorialise. "
            . "Article:\n\n" . $article;

        try {
            return $this->openAi->chat($system, $user, 0.4);
        } catch (Throwable) {
            return $this->demoSummary($article);
        }
    }

    private function demoSummary(string $article): string
    {
        $trimmed = trim($article);
        if ($trimmed === '') {
            return 'No article text was provided.';
        }
        $parts = preg_split('/[.!?]+/u', $trimmed, 4);
        if ($parts === false) {
            return substr($trimmed, 0, 220) . '...';
        }
        $parts = array_filter(array_map('trim', $parts), fn ($p) => $p !== '');
        $first = array_slice(array_values($parts), 0, 2);
        if (count($first) === 0) {
            return substr($trimmed, 0, 220) . '...';
        }
        return implode('. ', $first) . '. (Demo summary, no AI key configured.)';
    }
}
