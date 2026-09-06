<?php

declare(strict_types=1);

use CatalogMend\Domain\Detection\CorruptionDetector;
use CatalogMend\Support\HtmlTextProcessor;
use PHPUnit\Framework\TestCase;

final class HtmlTextProcessorTest extends TestCase
{
    public function testCleanerPreservesHtmlAndShortcodes(): void
    {
        $processor = new HtmlTextProcessor(new CorruptionDetector());
        $input = '<p class="x">Hello � world</p>[gallery ids="1,2"]';
        $expected = '<p class="x">Hello  world</p>[gallery ids="1,2"]';

        self::assertSame($expected, $processor->clean($input));
    }

    public function testDetectorIgnoresProtectedMarkup(): void
    {
        $processor = new HtmlTextProcessor(new CorruptionDetector());
        $input = '<div data-value="�">Valid text</div>';

        self::assertSame([], $processor->detect($input));
    }
}
