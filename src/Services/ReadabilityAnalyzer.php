<?php

declare(strict_types=1);

namespace Deleep\ArticleToolkit\Services;

/**
 * Flesch-Kincaid Grade Level analyzer with accessibility bands.
 *
 * Pure-PHP implementation. No external API calls. The syllable counter
 * uses a vowel-group heuristic which is good enough for English news copy
 * but is not a linguistic-grade phonetic analyzer.
 *
 * Formula:
 *   FK = 0.39 * (words / sentences) + 11.8 * (syllables / words) - 15.59
 *
 * Bands map the FK score to plain-English labels for editors who do not
 * want to memorise grade-level numbers.
 */
final class ReadabilityAnalyzer
{
    public function analyze(string $text): array
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return $this->emptyResult();
        }

        $sentences = $this->countSentences($trimmed);
        $words = $this->countWords($trimmed);
        $syllables = $this->countSyllables($trimmed);

        if ($sentences === 0 || $words === 0) {
            return $this->emptyResult();
        }

        $grade = 0.39 * ($words / $sentences)
            + 11.8 * ($syllables / $words)
            - 15.59;

        $grade = round($grade, 2);
        $band = $this->bandFor($grade);

        return [
            'flesch_kincaid_grade' => $grade,
            'band' => $band,
            'wcag_note' => $this->wcagNoteFor($grade, $band),
            'word_count' => $words,
            'sentence_count' => $sentences,
        ];
    }

    private function countSentences(string $text): int
    {
        // Split on sentence-ending punctuation followed by whitespace or end.
        $matches = preg_split('/[.!?]+(?:\s|$)/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if ($matches === false) {
            return 0;
        }
        return count($matches);
    }

    private function countWords(string $text): int
    {
        $matches = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        if ($matches === false) {
            return 0;
        }
        return count($matches);
    }

    private function countSyllables(string $text): int
    {
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false) {
            return 0;
        }

        $total = 0;
        foreach ($words as $word) {
            $total += $this->syllablesInWord($word);
        }
        return $total;
    }

    private function syllablesInWord(string $word): int
    {
        $clean = strtolower(preg_replace('/[^a-z]/i', '', $word) ?? '');
        if ($clean === '') {
            return 0;
        }

        // Trailing silent "e" rule
        if (strlen($clean) > 2 && substr($clean, -1) === 'e') {
            $clean = substr($clean, 0, -1);
        }

        // Count vowel groups
        $count = preg_match_all('/[aeiouy]+/', $clean);
        if ($count === false || $count === 0) {
            return 1;
        }
        return (int) $count;
    }

    private function bandFor(float $grade): string
    {
        return match (true) {
            $grade < 6.0 => 'Very Easy',
            $grade < 9.0 => 'Easy',
            $grade < 12.0 => 'Plain English',
            $grade < 14.0 => 'Fairly Difficult',
            $grade < 17.0 => 'Difficult',
            default => 'Very Difficult',
        };
    }

    private function wcagNoteFor(float $grade, string $band): string
    {
        $roundedGrade = (int) round($grade);
        return match ($band) {
            'Very Easy', 'Easy' => "Reads at a US grade {$roundedGrade} level, suitable for the widest accessibility audience including readers with cognitive disabilities.",
            'Plain English' => "Reads at a US grade {$roundedGrade} level, suitable for general newsroom audiences and aligned with WCAG 2.1 readability guidance.",
            'Fairly Difficult' => "Reads at a US grade {$roundedGrade} level, sits above WCAG 2.1 plain-language guidance for general audiences.",
            'Difficult', 'Very Difficult' => "Reads at a US grade {$roundedGrade} level, exceeds plain-language thresholds. Consider shortening sentences or replacing complex terms.",
            default => "Reads at a US grade {$roundedGrade} level.",
        };
    }

    private function emptyResult(): array
    {
        return [
            'flesch_kincaid_grade' => 0.0,
            'band' => 'Unknown',
            'wcag_note' => 'Insufficient text to score.',
            'word_count' => 0,
            'sentence_count' => 0,
        ];
    }
}
