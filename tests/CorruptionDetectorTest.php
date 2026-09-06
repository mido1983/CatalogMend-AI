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
        $input = 'English Русский עברית العربية Français € 12.50';

        self::assertSame($input, $detector->removeSafeFragments($input));
    }

    public function testSafeCleanerRemovesOnlyCertainArtifacts(): void
    {
        $detector = new CorruptionDetector();
        $input = "ABC�DEF\x01GHI";

        self::assertSame('ABCDEFGHI', $detector->removeSafeFragments($input));
    }
}
