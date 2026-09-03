<?php

declare(strict_types=1);

namespace EDocxParser\Tests;

use EDocxParser\DocxReader;
use PHPUnit\Framework\TestCase;

class DocxReaderTest extends TestCase
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

    // ==================== readXml ====================

    public function testReadXmlReturnsRawXml(): void
    {
        $xml = $this->buildWordXml('<w:p><w:r><w:t>Hello World</w:t></w:r></w:p>');
        $docxPath = $this->createTestDocx($xml);
        $reader = new DocxReader($docxPath);

        $result = $reader->readXml();

        $this->assertStringContainsString('Hello World', $result);
        $this->assertStringContainsString('w:document', $result);
    }

    public function testReadXmlThrowsExceptionForInvalidFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to open DOCX file');

        $reader = new DocxReader('/nonexistent/path/file.docx');
        $reader->readXml();
    }

    public function testReadXmlThrowsExceptionForMissingDocumentXml(): void
    {
        $docxPath = $this->tempDir . '/empty.docx';
        $zip = new \ZipArchive();
        $zip->open($docxPath, \ZipArchive::CREATE);
        $zip->addFromString('other.txt', 'not a document');
        $zip->close();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to read word/document.xml');

        $reader = new DocxReader($docxPath);
        $reader->readXml();
    }

    // ==================== readHtml ====================

    public function testReadHtmlConvertsPlainTextParagraph(): void
    {
        $xml = $this->buildWordXml('<w:p><w:r><w:t>Hello World</w:t></w:r></w:p>');
        $docxPath = $this->createTestDocx($xml);
        $reader = new DocxReader($docxPath);

        $result = $reader->readHtml();

        $this->assertSame('<p>Hello World</p>', $result);
    }

    public function testReadHtmlConvertsMultipleParagraphs(): void
    {
        $body = '<w:p><w:r><w:t>First paragraph</w:t></w:r></w:p>'
            . '<w:p><w:r><w:t>Second paragraph</w:t></w:r></w:p>';
        $xml = $this->buildWordXml($body);
        $docxPath = $this->createTestDocx($xml);
        $reader = new DocxReader($docxPath);

        $result = $reader->readHtml();

        $expected = "<p>First paragraph</p>\n<p>Second paragraph</p>";
        $this->assertSame($expected, $result);
    }

    public function testReadHtmlConvertsBoldText(): void
    {
        $body = '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>bold text</w:t></w:r></w:p>';
        $xml = $this->buildWordXml($body);
        $docxPath = $this->createTestDocx($xml);
        $reader = new DocxReader($docxPath);

        $result = $reader->readHtml();

        $this->assertSame('<p><b>bold text</b></p>', $result);
    }

    public function testReadHtmlConvertsItalicText(): void
    {
        $body = '<w:p><w:r><w:rPr><w:i/></w:rPr><w:t>italic text</w:t></w:r></w:p>';
        $xml = $this->buildWordXml($body);
        $docxPath = $this->createTestDocx($xml);
        $reader = new DocxReader($docxPath);

        $result = $reader->readHtml();

        $this->assertSame('<p><i>italic text</i></p>', $result);
    }

    public function testReadHtmlConvertsUnderlineText(): void
    {
        $body = '<w:p><w:r><w:rPr><w:u/></w:rPr><w:t>underlined text</w:t></w:r></w:p>';
        $xml = $this->buildWordXml($body);
        $docxPath = $this->createTestDocx($xml);
        $reader = new DocxReader($docxPath);

        $result = $reader->readHtml();

        $this->assertSame('<p><u>underlined text</u></p>', $result);
    }

    public function testReadHtmlConvertsMultipleFormatsInOneParagraph(): void
    {
        $body = '<w:p>'
            . '<w:r><w:rPr><w:b/></w:rPr><w:t>bold</w:t></w:r>'
            . '<w:r><w:t> and </w:t></w:r>'
            . '<w:r><w:rPr><w:i/></w:rPr><w:t>italic</w:t></w:r>'
            . '</w:p>';
        $xml = $this->buildWordXml($body);
        $docxPath = $this->createTestDocx($xml);
        $reader = new DocxReader($docxPath);

        $result = $reader->readHtml();

        $this->assertSame('<p><b>bold</b> and <i>italic</i></p>', $result);
    }

    public function testReadHtmlReturnsEmptyStringForEmptyDocument(): void
    {
        $xml = $this->buildWordXml('');
        $docxPath = $this->createTestDocx($xml);
        $reader = new DocxReader($docxPath);

        $result = $reader->readHtml();

        $this->assertSame('', $result);
    }

    // ==================== Static Factory ====================

    public function testCreateReturnsInstance(): void
    {
        $xml = $this->buildWordXml('<w:p><w:r><w:t>test</w:t></w:r></w:p>');
        $docxPath = $this->createTestDocx($xml);

        $reader = DocxReader::create($docxPath);

        $this->assertInstanceOf(DocxReader::class, $reader);
        $this->assertSame('<p>test</p>', $reader->readHtml());
    }
}
