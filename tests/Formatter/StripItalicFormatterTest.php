<?php

declare(strict_types=1);

namespace EDocxParser\Tests\Formatter;

use EDocxParser\Formatter\StripItalicFormatter;
use PHPUnit\Framework\TestCase;

class StripItalicFormatterTest extends TestCase
{
    private StripItalicFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new StripItalicFormatter();
    }

    // ==================== Markdown Italic ====================

    public function testRemovesMarkdownSingleAsterisks(): void
    {
        $this->assertSame('italic text', $this->formatter->format('*italic text*'));
    }

    public function testRemovesMarkdownSingleUnderscores(): void
    {
        $this->assertSame('italic text', $this->formatter->format('_italic text_'));
    }

    // ==================== HTML Italic ====================

    public function testRemovesHtmlITag(): void
    {
        $this->assertSame('italic text', $this->formatter->format('<i>italic text</i>'));
    }

    public function testRemovesHtmlEmTag(): void
    {
        $this->assertSame('italic text', $this->formatter->format('<em>italic text</em>'));
    }

    public function testRemovesHtmlITagWithAttributes(): void
    {
        $this->assertSame('italic text', $this->formatter->format('<i class="foo" id="bar">italic text</i>'));
    }

    public function testRemovesHtmlEmTagWithAttributes(): void
    {
        $this->assertSame('italic text', $this->formatter->format('<em style="font-style:italic;">italic text</em>'));
    }

    // ==================== Boundary: Bold Should Not Be Touched ====================

    public function testDoesNotRemoveMarkdownBoldDoubleAsterisks(): void
    {
        $this->assertSame('**bold text**', $this->formatter->format('**bold text**'));
    }

    public function testDoesNotRemoveMarkdownBoldDoubleUnderscores(): void
    {
        $this->assertSame('__bold text__', $this->formatter->format('__bold text__'));
    }

    // ==================== Multiple Occurrences ====================

    public function testRemovesMultipleItalicMarkers(): void
    {
        $input = '*first* and *second* and _third_';
        $expected = 'first and second and third';
        $this->assertSame($expected, $this->formatter->format($input));
    }

    public function testRemovesMultipleHtmlItalicTags(): void
    {
        $input = '<i>first</i> and <em>second</em> and <i>third</i>';
        $expected = 'first and second and third';
        $this->assertSame($expected, $this->formatter->format($input));
    }

    // ==================== Mixed Content ====================

    public function testPreservesBoldMarkersWhileRemovingItalic(): void
    {
        $input = '**bold** and *italic* and __bold2__ and _italic2_';
        $expected = '**bold** and italic and __bold2__ and italic2';
        $this->assertSame($expected, $this->formatter->format($input));
    }

    public function testPreservesHtmlBoldTagsWhileRemovingItalic(): void
    {
        $input = '<b>bold</b> and <i>italic</i> and <strong>bold2</strong> and <em>italic2</em>';
        $expected = '<b>bold</b> and italic and <strong>bold2</strong> and italic2';
        $this->assertSame($expected, $this->formatter->format($input));
    }

    // ==================== Nested / Edge Cases ====================

    public function testHandlesEmptyString(): void
    {
        $this->assertSame('', $this->formatter->format(''));
    }

    public function testHandlesTextWithoutAnyItalicMarkers(): void
    {
        $this->assertSame('plain text without formatting', $this->formatter->format('plain text without formatting'));
    }

    public function testHandlesItalicWithSpecialCharacters(): void
    {
        $this->assertSame('italic & special <chars>', $this->formatter->format('*italic & special <chars>*'));
    }

    public function testHandlesMultilineMarkdownItalic(): void
    {
        $input = "*line one\nline two*";
        $this->assertSame("line one\nline two", $this->formatter->format($input));
    }

    public function testHandlesMultilineHtmlItalic(): void
    {
        $input = "<i>line one\nline two</i>";
        $this->assertSame("line one\nline two", $this->formatter->format($input));
    }

    public function testHandlesSelfClosingHtmlTags(): void
    {
        // Self-closing tags are treated as HTML tags and removed
        $this->assertSame('', $this->formatter->format('<i />'));
        $this->assertSame('', $this->formatter->format('<em/>'));
    }

    public function testHandlesAsteriskWithBoldInside(): void
    {
        // * should not match across ** boundaries
        $this->assertSame('**text**', $this->formatter->format('**text**'));
    }

    // ==================== Combined / Cross-Formatter Scenarios ====================

    public function testNestedBoldAndItalicTogether(): void
    {
        // When bold wraps italic: **_italic inside bold_**
        // StripItalicFormatter should still remove the inner italic markers
        $input = '**_italic inside bold_**';
        $expected = '**italic inside bold**';
        $this->assertSame($expected, $this->formatter->format($input));
    }
}
