<?php

declare(strict_types=1);

namespace EDocxParser\Formatter;

/**
 * Strip italic formatting from text.
 *
 * Removes Markdown (*text*, _text_) and HTML (<i>, <em>) italic markers.
 */
class StripItalicFormatter implements FormatterInterface
{
    public function format(string $text): string
    {
        // Markdown italic: *text* (avoid matching **text** which is bold)
        $text = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/su', '$1', $text) ?? $text;
        // Markdown italic: _text_ (avoid matching __text__ which is bold)
        $text = preg_replace('/(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/su', '$1', $text) ?? $text;

        // HTML italic tags: <i> and <em>
        $text = preg_replace('/<(?:i|em)[^>]*>/iu', '', $text) ?? $text;
        $text = preg_replace('/<\/(?:i|em)\s*>/iu', '', $text) ?? $text;

        return $text;
    }
}
