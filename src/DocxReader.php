<?php

declare(strict_types=1);

namespace EDocxParser;

class DocxReader
{
    /**
     * Word XML namespace.
     */
    private string $xmlNamespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /**
     * Constructor for DocxParser.
     *
     * @param string $filePath Path to the DOCX file
     */
    public function __construct(protected string $filePath) {}

    /**
     * Create a new instance of DocxParser.
     *
     * @param string $filePath Path to the DOCX file
     */
    public static function create(string $filePath): static
    {
        return new static($filePath);
    }

    /**
     * Set the XML namespace for the DOCX file.
     *
     * @param string $namespace The XML namespace
     */
    public function setXmlNamespace(string $namespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'): static
    {
        $this->xmlNamespace = $namespace;
        return $this;
    }

    /**
     * Read the raw XML content from the DOCX file.
     *
     * Extracts and returns the content of word/document.xml as a string.
     *
     * @return string Raw XML content
     * @throws \RuntimeException If the file cannot be opened or read
     */
    public function readXml(): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($this->filePath) !== true) {
            throw new \RuntimeException("Failed to open DOCX file: {$this->filePath}");
        }

        $xmlContent = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xmlContent === false) {
            throw new \RuntimeException("Failed to read word/document.xml from DOCX file: {$this->filePath}");
        }

        return $xmlContent;
    }

    /**
     * Read the DOCX file and convert its content to HTML.
     *
     * Parses the Word XML and converts paragraphs, text runs, bold, italic,
     * underline, and line breaks to their HTML equivalents.
     *
     * @return string Parsed HTML content
     * @throws \RuntimeException If the file cannot be opened or parsed
     */
    public function readHtml(): string
    {
        $xmlContent = $this->readXml();
        $xml = new \SimpleXMLElement($xmlContent);

        $html = '';
        $ns = $this->xmlNamespace;
        $body = $xml->children($ns)->body;

        if ($body === null) {
            return $html;
        }

        foreach ($body->children($ns)->p as $paragraph) {
            $html .= '<p>' . $this->convertParagraphToHtml($paragraph) . '</p>' . "\n";
        }

        return trim($html);
    }

    /**
     * Convert a Word paragraph element to HTML.
     *
     * @param \SimpleXMLElement $paragraph The paragraph element
     * @return string HTML content for the paragraph
     */
    private function convertParagraphToHtml(\SimpleXMLElement $paragraph): string
    {
        $html = '';

        foreach ($paragraph->children($this->xmlNamespace) as $child) {
            $nodeName = $child->getName();

            switch ($nodeName) {
                case 'r':
                    $html .= $this->convertRunToHtml($child);
                    break;
                case 'hyperlink':
                    $html .= $this->convertHyperlinkToHtml($child);
                    break;
                case 'br':
                    $html .= '<br>';
                    break;
                case 'tab':
                    $html .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                    break;
            }
        }

        return $html;
    }

    /**
     * Convert a Word run element to HTML.
     *
     * @param \SimpleXMLElement $run The run element
     * @return string HTML content for the run
     */
    private function convertRunToHtml(\SimpleXMLElement $run): string
    {
        $text = '';
        $isBold = false;
        $isItalic = false;
        $isUnderline = false;

        foreach ($run->children($this->xmlNamespace) as $child) {
            $nodeName = $child->getName();

            switch ($nodeName) {
                case 't':
                    $text .= (string) $child;
                    break;
                case 'br':
                    $text .= '<br>';
                    break;
                case 'tab':
                    $text .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                    break;
                case 'rPr':
                    foreach ($child->children($this->xmlNamespace) as $prop) {
                        $propName = $prop->getName();
                        if ($propName === 'b') {
                            $isBold = true;
                        } elseif ($propName === 'i') {
                            $isItalic = true;
                        } elseif ($propName === 'u') {
                            $isUnderline = true;
                        }
                    }
                    break;
            }
        }

        if ($isBold) {
            $text = '<b>' . $text . '</b>';
        }
        if ($isItalic) {
            $text = '<i>' . $text . '</i>';
        }
        if ($isUnderline) {
            $text = '<u>' . $text . '</u>';
        }

        return $text;
    }

    /**
     * Convert a Word hyperlink element to HTML.
     *
     * @param \SimpleXMLElement $hyperlink The hyperlink element
     * @return string HTML content for the hyperlink
     */
    private function convertHyperlinkToHtml(\SimpleXMLElement $hyperlink): string
    {
        $text = '';
        $href = (string) $hyperlink['r:id'] ?? '';

        foreach ($hyperlink->children($this->xmlNamespace) as $child) {
            $nodeName = $child->getName();
            if ($nodeName === 'r') {
                $text .= $this->convertRunToHtml($child);
            }
        }

        if ($href !== '') {
            return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . $text . '</a>';
        }

        return $text;
    }
}
