<?php

declare(strict_types=1);

namespace CatalogMend\Domain\Detection;

final class CorruptionDetector
{
    private const HIGH_CONFIDENCE_MOJIBAKE = '/(?:[ÃÂÐÑ×ØÙâ][^\s]){3,}/u';

    /**
     * @return array<int, array{rule_id:string,severity:string,fragment:string,reason:string,auto_remove:bool}>
     */
    public function detect(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $findings = [];
        $rules = [
            [
                'id' => 'unicode_replacement',
                'pattern' => '/\x{FFFD}+/u',
                'severity' => 'high',
                'reason' => 'Unicode replacement character indicates lost or undecodable source bytes.',
                'auto_remove' => true,
            ],
            [
                'id' => 'forbidden_controls',
                'pattern' => '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u',
                'severity' => 'high',
                'reason' => 'Non-printing control characters were found in visible product text.',
                'auto_remove' => true,
            ],
            [
                'id' => 'high_confidence_mojibake_run',
                'pattern' => self::HIGH_CONFIDENCE_MOJIBAKE,
                'severity' => 'high',
                'reason' => 'Repeated legacy-decoding marker pairs strongly indicate mojibake.',
                'auto_remove' => true,
            ],
            [
                'id' => 'common_utf8_mojibake',
                'pattern' => '/(?:Ã[\x{0080}-\x{00BF}]|Â[\x{0080}-\x{00BF}]|â(?:€|€™|€œ|€|€“|€”|€¦)){1,}/u',
                'severity' => 'medium',
                'reason' => 'Sequence resembles UTF-8 text decoded with the wrong legacy encoding.',
                'auto_remove' => false,
            ],
            [
                'id' => 'repeated_mojibake_markers',
                'pattern' => '/(?:Ã.|Â.|Ð.|Ñ.|×.|Ø.|Ù.){2,}/u',
                'severity' => 'medium',
                'reason' => 'Repeated encoding-artifact markers were detected.',
                'auto_remove' => false,
            ],
        ];

        foreach ($rules as $rule) {
            $matched = preg_match_all($rule['pattern'], $text, $matches);
            if ($matched === false || $matched === 0) {
                continue;
            }

            foreach ($matches[0] as $fragment) {
                $findings[] = [
                    'rule_id' => $rule['id'],
                    'severity' => $rule['severity'],
                    'fragment' => (string) $fragment,
                    'reason' => $rule['reason'],
                    'auto_remove' => $rule['auto_remove'],
                ];
            }
        }

        return $findings;
    }

    public function removeSafeFragments(string $text): string
    {
        $text = preg_replace('/\x{FFFD}+/u', '', $text) ?? $text;
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $text) ?? $text;
        $text = preg_replace(self::HIGH_CONFIDENCE_MOJIBAKE, '', $text) ?? $text;

        return $text;
    }
}
