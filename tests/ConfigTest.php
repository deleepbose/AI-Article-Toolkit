<?php

declare(strict_types=1);

namespace Deleep\ArticleToolkit\Tests;

use Deleep\ArticleToolkit\Config\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('OPENAI_API_KEY');
        putenv('OPENAI_MODEL');
        putenv('DB_HOST');
    }

    public function testDemoModeWhenNoApiKey(): void
    {
        putenv('OPENAI_API_KEY=');
        $this->assertTrue(Config::isDemoMode());
        $this->assertNull(Config::openAiApiKey());
    }

    public function testLiveModeWhenApiKeyPresent(): void
    {
        putenv('OPENAI_API_KEY=sk-test-key-not-real');
        $this->assertFalse(Config::isDemoMode());
        $this->assertSame('sk-test-key-not-real', Config::openAiApiKey());
    }

    public function testDefaultModelWhenUnset(): void
    {
        putenv('OPENAI_MODEL=');
        $this->assertSame('gpt-4o-mini', Config::openAiModel());
    }

    public function testCustomModelWhenSet(): void
    {
        putenv('OPENAI_MODEL=gpt-4o');
        $this->assertSame('gpt-4o', Config::openAiModel());
    }

    public function testDbHostDefaults(): void
    {
        putenv('DB_HOST=');
        $this->assertSame('127.0.0.1', Config::dbHost());
    }

    public function testDbHostFromEnv(): void
    {
        putenv('DB_HOST=mysql.example.com');
        $this->assertSame('mysql.example.com', Config::dbHost());
    }
}
