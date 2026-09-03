<?php

declare(strict_types=1);

namespace EDocxParser\Formatter;

/**
 * Strip underline formatting from text.
 *
 * Removes HTML (<u>, <ins>) underline markers.
 */
class StripUnderlineFormatter implements FormatterInterface
{
    public function format(string $text): string
    {
        // HTML underline tags: <u> and <ins>
        $text = preg_replace('/<(?:u|ins)[^>]*>/iu', '', $text) ?? $text;
        $text = preg_replace('/<\/(?:u|ins)\s*>/iu', '', $text) ?? $text;

        return $text;
    }
}
