<?php

declare(strict_types=1);

namespace Deleep\ArticleToolkit\Database;

use Deleep\ArticleToolkit\Config\Config;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Persists and retrieves analysis records.
 *
 * Uses PDO with parameterised queries everywhere. No string interpolation
 * into SQL. The connection is lazy so a misconfigured DB does not block the
 * application from booting if a request does not touch persistence.
 */
final class AnalysisRepository
{
    private ?PDO $pdo = null;

    public function save(array $payload): int
    {
        $sql = <<<SQL
            INSERT INTO analyses
                (article_excerpt, word_count, headlines, summary, meta_description,
                 flesch_kincaid_grade, readability_band, reading_time_minutes, demo_mode)
            VALUES
                (:excerpt, :wc, :headlines, :summary, :meta,
                 :grade, :band, :rt, :demo)
            SQL;

        $stmt = $this->connection()->prepare($sql);
        $stmt->execute([
            ':excerpt' => mb_substr($payload['article_excerpt'], 0, 500),
            ':wc' => $payload['word_count'],
            ':headlines' => json_encode($payload['headlines'], JSON_THROW_ON_ERROR),
            ':summary' => $payload['summary'],
            ':meta' => $payload['meta_description'],
            ':grade' => $payload['flesch_kincaid_grade'],
            ':band' => $payload['readability_band'],
            ':rt' => $payload['reading_time_minutes'],
            ':demo' => $payload['demo_mode'] ? 1 : 0,
        ]);

        return (int) $this->connection()->lastInsertId();
    }

    public function recent(int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));

        $sql = "SELECT id, article_excerpt, word_count, headlines, summary,
                       meta_description, flesch_kincaid_grade, readability_band,
                       reading_time_minutes, demo_mode, created_at
                FROM analyses
                ORDER BY created_at DESC
                LIMIT {$limit}";

        $stmt = $this->connection()->query($sql);
        if ($stmt === false) {
            return [];
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(function (array $row): array {
            $row['headlines'] = json_decode((string) $row['headlines'], true) ?: [];
            $row['flesch_kincaid_grade'] = (float) $row['flesch_kincaid_grade'];
            $row['word_count'] = (int) $row['word_count'];
            $row['reading_time_minutes'] = (int) $row['reading_time_minutes'];
            $row['demo_mode'] = (bool) $row['demo_mode'];
            return $row;
        }, $rows);
    }

    private function connection(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            Config::dbHost(),
            Config::dbPort(),
            Config::dbName()
        );

        try {
            $this->pdo = new PDO($dsn, Config::dbUser(), Config::dbPass(), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }

        return $this->pdo;
    }
}
