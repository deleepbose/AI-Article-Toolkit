<?php

declare(strict_types=1);

namespace Deleep\ArticleToolkit\Api;

use Deleep\ArticleToolkit\Config\Config;
use Deleep\ArticleToolkit\Database\AnalysisRepository;
use Deleep\ArticleToolkit\Services\HeadlineGenerator;
use Deleep\ArticleToolkit\Services\MetaDescriptionGenerator;
use Deleep\ArticleToolkit\Services\OpenAiClient;
use Deleep\ArticleToolkit\Services\ReadabilityAnalyzer;
use Deleep\ArticleToolkit\Services\SummaryGenerator;
use JsonException;
use RuntimeException;
use Throwable;

/**
 * Tiny REST router.
 *
 * Two endpoints: POST /api/analyze and GET /api/history. Anything else
 * returns a 404 JSON payload. Errors are caught at the top level and
 * returned as a structured JSON response.
 */
final class Router
{
    private const WORDS_PER_MINUTE = 230;
    private const MAX_ARTICLE_LENGTH = 50000;

    public function dispatch(string $method, string $path): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if ($method === 'POST' && $path === '/api/analyze') {
                $this->handleAnalyze();
                return;
            }

            if ($method === 'GET' && $path === '/api/history') {
                $this->handleHistory();
                return;
            }

            $this->jsonResponse(404, ['error' => 'Not found.']);
        } catch (Throwable $e) {
            $this->jsonResponse(500, [
                'error' => 'Internal server error.',
                'detail' => Config::appEnv() === 'local' ? $e->getMessage() : null,
            ]);
        }
    }

    private function handleAnalyze(): void
    {
        $raw = file_get_contents('php://input') ?: '';
        try {
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->jsonResponse(400, ['error' => 'Request body must be valid JSON.']);
            return;
        }

        $article = isset($payload['article']) ? trim((string) $payload['article']) : '';
        if ($article === '') {
            $this->jsonResponse(400, ['error' => 'Field "article" is required.']);
            return;
        }
        if (mb_strlen($article) > self::MAX_ARTICLE_LENGTH) {
            $this->jsonResponse(413, ['error' => 'Article too long. Limit is ' . self::MAX_ARTICLE_LENGTH . ' characters.']);
            return;
        }

        $openAi = new OpenAiClient();
        $headlines = (new HeadlineGenerator($openAi))->generate($article);
        $summary = (new SummaryGenerator($openAi))->generate($article);
        $meta = (new MetaDescriptionGenerator($openAi))->generate($article);
        $readability = (new ReadabilityAnalyzer())->analyze($article);

        $readingTime = max(1, (int) ceil($readability['word_count'] / self::WORDS_PER_MINUTE));

        $record = [
            'article_excerpt' => mb_substr($article, 0, 500),
            'word_count' => $readability['word_count'],
            'headlines' => $headlines,
            'summary' => $summary,
            'meta_description' => $meta,
            'flesch_kincaid_grade' => $readability['flesch_kincaid_grade'],
            'readability_band' => $readability['band'],
            'reading_time_minutes' => $readingTime,
            'demo_mode' => Config::isDemoMode(),
        ];

        $id = null;
        try {
            $id = (new AnalysisRepository())->save($record);
        } catch (RuntimeException) {
            // Persistence failure should not block the response.
        }

        $this->jsonResponse(200, [
            'id' => $id,
            'headlines' => $headlines,
            'summary' => $summary,
            'meta_description' => $meta,
            'readability' => [
                'flesch_kincaid_grade' => $readability['flesch_kincaid_grade'],
                'band' => $readability['band'],
                'wcag_note' => $readability['wcag_note'],
            ],
            'word_count' => $readability['word_count'],
            'reading_time_minutes' => $readingTime,
            'demo_mode' => Config::isDemoMode(),
        ]);
    }

    private function handleHistory(): void
    {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        try {
            $rows = (new AnalysisRepository())->recent($limit);
        } catch (RuntimeException $e) {
            $this->jsonResponse(503, [
                'error' => 'History unavailable. Database not reachable.',
                'detail' => Config::appEnv() === 'local' ? $e->getMessage() : null,
            ]);
            return;
        }

        $this->jsonResponse(200, ['items' => $rows]);
    }

    private function jsonResponse(int $status, array $body): void
    {
        http_response_code($status);
        echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
