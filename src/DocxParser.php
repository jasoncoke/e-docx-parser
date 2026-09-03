<?php

declare(strict_types=1);

namespace EDocxParser;

use EDocxParser\Formatter\FormatterInterface;

class DocxParser
{
    /**
     * @var array<FormatterInterface>
     */
    private array $formatters = [];

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
     * Get the path to the DOCX file.
     *
     * @return string Path to the DOCX file
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * Add a formatter to the parser.
     *
     * @param FormatterInterface $formatter Formatter instance
     */
    public function addFormatter(FormatterInterface $formatter): static
    {
        $this->formatters[] = $formatter;
        return $this;
    }

    /**
     * Parse a DOCX file and extract text content.
     *
     * @return string Extracted text content
     */
    public function parse(): string
    {
        $text = DocxReader::create($this->filePath)->readHtml();

        foreach ($this->formatters as $formatter) {
            $text = $formatter->format($text);
        }

        return $text;
    }
}
