<?php

declare(strict_types=1);

namespace Deleep\ArticleToolkit\Config;

/**
 * Reads configuration from environment variables.
 *
 * The .env file is loaded by the application entry point via phpdotenv.
 * This class wraps getenv() with sensible defaults and explicit typing
 * so the rest of the code never touches superglobals directly.
 */
final class Config
{
    public static function dbHost(): string
    {
        return self::stringOrDefault('DB_HOST', '127.0.0.1');
    }

    public static function dbPort(): int
    {
        return (int) self::stringOrDefault('DB_PORT', '3306');
    }

    public static function dbName(): string
    {
        return self::stringOrDefault('DB_NAME', 'article_toolkit');
    }

    public static function dbUser(): string
    {
        return self::stringOrDefault('DB_USER', 'root');
    }

    public static function dbPass(): string
    {
        return self::stringOrDefault('DB_PASS', '');
    }

    public static function openAiApiKey(): ?string
    {
        $value = getenv('OPENAI_API_KEY');
        if ($value === false || $value === '') {
            return null;
        }
        return $value;
    }

    public static function openAiModel(): string
    {
        return self::stringOrDefault('OPENAI_MODEL', 'gpt-4o-mini');
    }

    public static function isDemoMode(): bool
    {
        return self::openAiApiKey() === null;
    }

    public static function appEnv(): string
    {
        return self::stringOrDefault('APP_ENV', 'production');
    }

    private static function stringOrDefault(string $key, string $default): string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }
        return $value;
    }
}
