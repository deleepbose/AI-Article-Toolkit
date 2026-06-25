<?php

declare(strict_types=1);

namespace Deleep\ArticleToolkit\Tests;

use Deleep\ArticleToolkit\Services\ReadabilityAnalyzer;
use PHPUnit\Framework\TestCase;

final class ReadabilityAnalyzerTest extends TestCase
{
    private ReadabilityAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new ReadabilityAnalyzer();
    }

    public function testEmptyInputReturnsUnknownBand(): void
    {
        $result = $this->analyzer->analyze('');
        $this->assertSame('Unknown', $result['band']);
        $this->assertSame(0, $result['word_count']);
        $this->assertSame(0, $result['sentence_count']);
    }

    public function testCountsWordsAndSentencesInSimpleText(): void
    {
        $text = 'The cat sat on the mat. The dog barked loudly.';
        $result = $this->analyzer->analyze($text);

        $this->assertSame(2, $result['sentence_count']);
        $this->assertSame(10, $result['word_count']);
    }

    public function testShortSimpleTextScoresLowGrade(): void
    {
        $text = 'I am a cat. I eat fish. I play in the sun.';
        $result = $this->analyzer->analyze($text);

        $this->assertLessThan(6.0, $result['flesch_kincaid_grade']);
        $this->assertSame('Very Easy', $result['band']);
    }

    public function testLongerProseScoresHigherGrade(): void
    {
        $text = 'The legislative consultation process incorporated numerous stakeholder submissions, '
            . 'including detailed environmental impact assessments and comprehensive economic modelling. '
            . 'Subsequent revisions accommodated reasonable technical objections, however the underlying '
            . 'objectives remained substantively unchanged throughout the deliberations.';

        $result = $this->analyzer->analyze($text);

        $this->assertGreaterThan(12.0, $result['flesch_kincaid_grade']);
        $this->assertContains($result['band'], ['Fairly Difficult', 'Difficult', 'Very Difficult']);
    }

    public function testWcagNoteIsNonEmptyForValidInput(): void
    {
        $result = $this->analyzer->analyze('The cat sat on the mat. The dog barked.');
        $this->assertNotSame('', $result['wcag_note']);
    }

    public function testHandlesPunctuationVariants(): void
    {
        $text = 'Wow! Did you see that? Yes, I did.';
        $result = $this->analyzer->analyze($text);

        $this->assertSame(3, $result['sentence_count']);
        $this->assertGreaterThan(0, $result['word_count']);
    }
}
