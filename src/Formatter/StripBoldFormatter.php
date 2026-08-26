<?php

declare(strict_types=1);

namespace EDocxParser\Formatter;

/**
 * Strip bold formatting from text.
 *
 * Removes Markdown (**text**, __text__) and HTML (<b>, <strong>) bold markers.
 */
class StripBoldFormatter implements FormatterInterface
{
    public function format(string $text): string
    {
        // Markdown bold: **text**
        $text = preg_replace('/\*\*(.+?)\*\*/su', '$1', $text) ?? $text;
        // Markdown bold: __text__
        $text = preg_replace('/__(.+?)__/su', '$1', $text) ?? $text;

        // HTML bold tags: <b> and <strong>
        $text = preg_replace('/<(?:b|strong)[^>]*>/iu', '', $text) ?? $text;
        $text = preg_replace('/<\/(?:b|strong)\s*>/iu', '', $text) ?? $text;

        return $text;
    }
}
