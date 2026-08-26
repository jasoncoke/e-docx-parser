<?php

declare(strict_types=1);

namespace EDocxParser\Tests\Formatter;

use EDocxParser\Formatter\StripUnderlineFormatter;
use PHPUnit\Framework\TestCase;

class StripUnderlineFormatterTest extends TestCase
{
    private StripUnderlineFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new StripUnderlineFormatter();
    }

    // ==================== HTML Underline ====================

    public function testRemovesHtmlUTag(): void
    {
        $this->assertSame('underlined text', $this->formatter->format('<u>underlined text</u>'));
    }

    public function testRemovesHtmlInsTag(): void
    {
        $this->assertSame('inserted text', $this->formatter->format('<ins>inserted text</ins>'));
    }

    public function testRemovesHtmlUTagWithAttributes(): void
    {
        $this->assertSame('underlined text', $this->formatter->format('<u class="foo" id="bar">underlined text</u>'));
    }

    public function testRemovesHtmlInsTagWithAttributes(): void
    {
        $this->assertSame('inserted text', $this->formatter->format('<ins style="text-decoration:underline;">inserted text</ins>'));
    }

    // ==================== Multiple Occurrences ====================

    public function testRemovesMultipleUnderlineTags(): void
    {
        $input = '<u>first</u> and <ins>second</ins> and <u>third</u>';
        $expected = 'first and second and third';
        $this->assertSame($expected, $this->formatter->format($input));
    }

    // ==================== Mixed Content ====================

    public function testPreservesBoldAndItalicTags(): void
    {
        $input = '<b>bold</b> and <i>italic</i> and <u>underline</u> and <strong>strong</strong>';
        $expected = '<b>bold</b> and <i>italic</i> and underline and <strong>strong</strong>';
        $this->assertSame($expected, $this->formatter->format($input));
    }

    // ==================== Nested / Edge Cases ====================

    public function testHandlesEmptyString(): void
    {
        $this->assertSame('', $this->formatter->format(''));
    }

    public function testHandlesTextWithoutAnyUnderlineMarkers(): void
    {
        $this->assertSame('plain text without formatting', $this->formatter->format('plain text without formatting'));
    }

    public function testHandlesUnderlineWithSpecialCharacters(): void
    {
        $this->assertSame('underlined & special <chars>', $this->formatter->format('<u>underlined & special <chars></u>'));
    }

    public function testHandlesMultilineHtmlUnderline(): void
    {
        $input = "<u>line one\nline two</u>";
        $this->assertSame("line one\nline two", $this->formatter->format($input));
    }

    public function testHandlesSelfClosingHtmlTags(): void
    {
        // Self-closing tags are treated as HTML tags and removed
        $this->assertSame('', $this->formatter->format('<u />'));
        $this->assertSame('', $this->formatter->format('<ins/>'));
    }

    public function testHandlesCaseInsensitiveTags(): void
    {
        $this->assertSame('text', $this->formatter->format('<U>text</U>'));
        $this->assertSame('text', $this->formatter->format('<INS>text</INS>'));
        $this->assertSame('text', $this->formatter->format('<U>text</u>'));
    }
}
