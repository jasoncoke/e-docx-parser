<?php

declare(strict_types=1);

namespace EDocxParser\Tests\Formatter;

use EDocxParser\Formatter\StripBoldFormatter;
use PHPUnit\Framework\TestCase;

class StripBoldFormatterTest extends TestCase
{
    private StripBoldFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new StripBoldFormatter();
    }

    // ==================== Markdown Bold ====================

    public function testRemovesMarkdownDoubleAsterisks(): void
    {
        $this->assertSame('bold text', $this->formatter->format('**bold text**'));
    }

    public function testRemovesMarkdownDoubleUnderscores(): void
    {
        $this->assertSame('bold text', $this->formatter->format('__bold text__'));
    }

    // ==================== HTML Bold ====================

    public function testRemovesHtmlBTag(): void
    {
        $this->assertSame('bold text', $this->formatter->format('<b>bold text</b>'));
    }

    public function testRemovesHtmlStrongTag(): void
    {
        $this->assertSame('bold text', $this->formatter->format('<strong>bold text</strong>'));
    }

    public function testRemovesHtmlBTagWithAttributes(): void
    {
        $this->assertSame('bold text', $this->formatter->format('<b class="foo" id="bar">bold text</b>'));
    }

    public function testRemovesHtmlStrongTagWithAttributes(): void
    {
        $this->assertSame('bold text', $this->formatter->format('<strong style="font-weight:bold;">bold text</strong>'));
    }

    // ==================== Multiple Occurrences ====================

    public function testRemovesMultipleBoldMarkers(): void
    {
        $input = '**first** and **second** and __third__';
        $expected = 'first and second and third';
        $this->assertSame($expected, $this->formatter->format($input));
    }

    public function testRemovesMultipleHtmlBoldTags(): void
    {
        $input = '<b>first</b> and <strong>second</strong> and <b>third</b>';
        $expected = 'first and second and third';
        $this->assertSame($expected, $this->formatter->format($input));
    }

    // ==================== Mixed Content ====================

    public function testPreservesItalicMarkersWhileRemovingBold(): void
    {
        $input = '**bold** and *italic* and __bold2__ and _italic2_';
        $expected = 'bold and *italic* and bold2 and _italic2_';
        $this->assertSame($expected, $this->formatter->format($input));
    }

    public function testPreservesHtmlItalicTagsWhileRemovingBold(): void
    {
        $input = '<b>bold</b> and <i>italic</i> and <strong>bold2</strong> and <em>italic2</em>';
        $expected = 'bold and <i>italic</i> and bold2 and <em>italic2</em>';
        $this->assertSame($expected, $this->formatter->format($input));
    }

    // ==================== Nested / Edge Cases ====================

    public function testHandlesEmptyString(): void
    {
        $this->assertSame('', $this->formatter->format(''));
    }

    public function testHandlesTextWithoutAnyBoldMarkers(): void
    {
        $this->assertSame('plain text without formatting', $this->formatter->format('plain text without formatting'));
    }

    public function testHandlesBoldWithSpecialCharacters(): void
    {
        $this->assertSame('bold & special <chars>', $this->formatter->format('**bold & special <chars>**'));
    }

    public function testHandlesMultilineMarkdownBold(): void
    {
        $input = "**line one\nline two**";
        $this->assertSame("line one\nline two", $this->formatter->format($input));
    }

    public function testHandlesMultilineHtmlBold(): void
    {
        $input = "<b>line one\nline two</b>";
        $this->assertSame("line one\nline two", $this->formatter->format($input));
    }

    public function testHandlesSelfClosingHtmlTags(): void
    {
        // Self-closing tags are treated as HTML tags and removed
        $this->assertSame('', $this->formatter->format('<b />'));
        $this->assertSame('', $this->formatter->format('<strong/>'));
    }

    public function testHandlesNestedLikeMarkdownPatterns(): void
    {
        // Inner bold markers should be removed even when wrapped by other markers
        $this->assertSame('*text*', $this->formatter->format('*__text__*'));
        $this->assertSame('_text_', $this->formatter->format('_**text**_'));
    }
}
