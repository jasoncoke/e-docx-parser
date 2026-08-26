<?php

declare(strict_types=1);

namespace EDocxParser\Tests;

use EDocxParser\DocxParser;
use EDocxParser\Formatter\StripBoldFormatter;
use EDocxParser\Formatter\StripItalicFormatter;
use EDocxParser\Formatter\StripUnderlineFormatter;
use PHPUnit\Framework\TestCase;

class DocxParserTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/e-docx-parser-tests-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    /**
     * Recursively remove a directory.
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Create a test DOCX file with the given Word XML content.
     */
    private function createTestDocx(string $wordXml): string
    {
        $docxPath = $this->tempDir . '/test.docx';
        $zip = new \ZipArchive();
        $zip->open($docxPath, \ZipArchive::CREATE);
        $zip->addFromString('word/document.xml', $wordXml);
        $zip->close();
        return $docxPath;
    }

    /**
     * Build a Word XML document with the given body content.
     */
    private function buildWordXml(string $bodyContent): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:body>' . $bodyContent . '</w:body>'
            . '</w:document>';
    }

    // ==================== Constructor and Factory ====================

    public function testConstructorSetsFilePath(): void
    {
        $xml = $this->buildWordXml('<w:p><w:r><w:t>test</w:t></w:r></w:p>');
        $docxPath = $this->createTestDocx($xml);
        $parser = new DocxParser($docxPath);

        $this->assertSame($docxPath, $parser->getFilePath());
    }

    public function testCreateReturnsInstance(): void
    {
        $xml = $this->buildWordXml('<w:p><w:r><w:t>test</w:t></w:r></w:p>');
        $docxPath = $this->createTestDocx($xml);
        $parser = DocxParser::create($docxPath);

        $this->assertInstanceOf(DocxParser::class, $parser);
    }

    // ==================== addFormatter ====================

    public function testAddFormatterReturnsSelf(): void
    {
        $xml = $this->buildWordXml('<w:p><w:r><w:t>test</w:t></w:r></w:p>');
        $docxPath = $this->createTestDocx($xml);
        $parser = new DocxParser($docxPath);

        $result = $parser->addFormatter(new StripBoldFormatter());

        $this->assertSame($parser, $result);
    }

    public function testAddFormatterSupportsChaining(): void
    {
        $xml = $this->buildWordXml('<w:p><w:r><w:t>test</w:t></w:r></w:p>');
        $docxPath = $this->createTestDocx($xml);
        $parser = new DocxParser($docxPath);

        $result = $parser
            ->addFormatter(new StripBoldFormatter())
            ->addFormatter(new StripItalicFormatter());

        $this->assertSame($parser, $result);
    }

    // ==================== parse ====================

    public function testParseReturnsHtmlWithoutFormatters(): void
    {
        $body = '<w:p><w:r><w:t>Hello World</w:t></w:r></w:p>';
        $xml = $this->buildWordXml($body);
        $docxPath = $this->createTestDocx($xml);
        $parser = new DocxParser($docxPath);

        $result = $parser->parse();

        $this->assertSame('<p>Hello World</p>', $result);
    }

    public function testParseAppliesSingleFormatter(): void
    {
        $body = '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>bold text</w:t></w:r></w:p>';
        $xml = $this->buildWordXml($body);
        $docxPath = $this->createTestDocx($xml);
        $parser = new DocxParser($docxPath);
        $parser->addFormatter(new StripBoldFormatter());

        $result = $parser->parse();

        $this->assertSame('<p>bold text</p>', $result);
    }

    public function testParseAppliesMultipleFormatters(): void
    {
        $body = '<w:p>'
            . '<w:r><w:rPr><w:b/></w:rPr><w:t>bold</w:t></w:r>'
            . '<w:r><w:t> and </w:t></w:r>'
            . '<w:r><w:rPr><w:i/></w:rPr><w:t>italic</w:t></w:r>'
            . '</w:p>';
        $xml = $this->buildWordXml($body);
        $docxPath = $this->createTestDocx($xml);
        $parser = new DocxParser($docxPath);
        $parser->addFormatter(new StripBoldFormatter());
        $parser->addFormatter(new StripItalicFormatter());

        $result = $parser->parse();

        $this->assertSame('<p>bold and italic</p>', $result);
    }

    public function testParseAppliesUnderlineFormatter(): void
    {
        $body = '<w:p><w:r><w:rPr><w:u/></w:rPr><w:t>underlined text</w:t></w:r></w:p>';
        $xml = $this->buildWordXml($body);
        $docxPath = $this->createTestDocx($xml);
        $parser = new DocxParser($docxPath);
        $parser->addFormatter(new StripUnderlineFormatter());

        $result = $parser->parse();

        $this->assertSame('<p>underlined text</p>', $result);
    }

    public function testParseAppliesAllThreeFormatters(): void
    {
        $body = '<w:p>'
            . '<w:r><w:rPr><w:b/></w:rPr><w:t>bold</w:t></w:r>'
            . '<w:r><w:t> and </w:t></w:r>'
            . '<w:r><w:rPr><w:i/></w:rPr><w:t>italic</w:t></w:r>'
            . '<w:r><w:t> and </w:t></w:r>'
            . '<w:r><w:rPr><w:u/></w:rPr><w:t>underlined</w:t></w:r>'
            . '</w:p>';
        $xml = $this->buildWordXml($body);
        $docxPath = $this->createTestDocx($xml);
        $parser = new DocxParser($docxPath);
        $parser->addFormatter(new StripBoldFormatter());
        $parser->addFormatter(new StripItalicFormatter());
        $parser->addFormatter(new StripUnderlineFormatter());

        $result = $parser->parse();

        $this->assertSame('<p>bold and italic and underlined</p>', $result);
    }

    public function testParseReturnsPlainTextWhenNoFormatting(): void
    {
        $body = '<w:p><w:r><w:t>plain text</w:t></w:r></w:p>';
        $xml = $this->buildWordXml($body);
        $docxPath = $this->createTestDocx($xml);
        $parser = new DocxParser($docxPath);
        $parser->addFormatter(new StripBoldFormatter());
        $parser->addFormatter(new StripItalicFormatter());

        $result = $parser->parse();

        // Formatters should not affect plain text
        $this->assertSame('<p>plain text</p>', $result);
    }

    public function testParseWithChainedCreation(): void
    {
        $body = '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>bold</w:t></w:r></w:p>';
        $xml = $this->buildWordXml($body);
        $docxPath = $this->createTestDocx($xml);

        $result = DocxParser::create($docxPath)
            ->addFormatter(new StripBoldFormatter())
            ->parse();

        $this->assertSame('<p>bold</p>', $result);
    }
}
