<?php

declare(strict_types=1);

use CatalogMend\Domain\Detection\CorruptionDetector;
use PHPUnit\Framework\TestCase;

final class CorruptionDetectorTest extends TestCase
{
    public function testDetectsUnicodeReplacementCharacter(): void
    {
        $detector = new CorruptionDetector();
        $findings = $detector->detect("Valid text � broken");

        self::assertNotEmpty($findings);
        self::assertSame('unicode_replacement', $findings[0]['rule_id']);
        self::assertTrue($findings[0]['auto_remove']);
    }

    public function testSafeCleanerDoesNotModifyValidMultilingualText(): void
    {
        $detector = new CorruptionDetector();
        $input = 'English Русский עברית العربية Français € 12.50 Ñandú Ðelta';

        self::assertSame($input, $detector->removeSafeFragments($input));
    }

    public function testSafeCleanerRemovesCertainArtifacts(): void
    {
        $detector = new CorruptionDetector();
        $input = "ABC�DEF\x01GHI";

        self::assertSame('ABCDEFGHI', $detector->removeSafeFragments($input));
    }

    public function testDetectsAndRemovesHighConfidenceCyrillicMojibakeRun(): void
    {
        $detector = new CorruptionDetector();
        $input = 'Before ÐŸÑ€Ð¸Ð²ÐµÑ‚ after';
        $findings = $detector->detect($input);

        self::assertTrue((bool) array_filter(
            $findings,
            static fn(array $finding): bool => $finding['rule_id'] === 'high_confidence_mojibake_run' && $finding['auto_remove'] === true
        ));
        self::assertSame('Before  after', $detector->removeSafeFragments($input));
    }

    public function testDetectsAndRemovesHighConfidenceHebrewMojibakeRun(): void
    {
        $detector = new CorruptionDetector();
        $input = 'Before ×©×œ×•× after';

        self::assertSame('Before  after', $detector->removeSafeFragments($input));
    }
}
