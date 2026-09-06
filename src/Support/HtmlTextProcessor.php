<?php

declare(strict_types=1);

namespace CatalogMend\Support;

use CatalogMend\Domain\Detection\CorruptionDetector;

final class HtmlTextProcessor
{
    public function __construct(private readonly CorruptionDetector $detector)
    {
    }

    /**
     * @return array<int, array{rule_id:string,severity:string,fragment:string,reason:string,auto_remove:bool}>
     */
    public function detect(string $value): array
    {
        $findings = [];

        foreach ($this->split($value) as $segment) {
            if ($segment['protected']) {
                continue;
            }

            foreach ($this->detector->detect($segment['value']) as $finding) {
                $findings[] = $finding;
            }
        }

        return $findings;
    }

    public function clean(string $value): string
    {
        $output = '';

        foreach ($this->split($value) as $segment) {
            $output .= $segment['protected']
                ? $segment['value']
                : $this->detector->removeSafeFragments($segment['value']);
        }

        return $output;
    }

    /**
     * Splits HTML tags, HTML comments and WordPress shortcodes away from visible text.
     * Protected segments are returned byte-for-byte.
     *
     * @return array<int, array{value:string,protected:bool}>
     */
    private function split(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $pattern = '/(<!--.*?-->|<[^>]*>|\[(?:[^\[\]"\']+|"[^"]*"|\'[^\']*\')*\])/s';
        $parts = preg_split($pattern, $value, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if ($parts === false) {
            return [['value' => $value, 'protected' => false]];
        }

        $segments = [];

        foreach ($parts as $part) {
            $isProtected = preg_match('/^(?:<!--|<|\[)/s', $part) === 1;
            $segments[] = ['value' => $part, 'protected' => $isProtected];
        }

        return $segments;
    }
}
