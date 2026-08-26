# e-docx-parser

A PHP library for parsing DOCX files.

## Requirements

- PHP >= 8.0

## Installation

```bash
composer require e-docx-parser/e-docx-parser
```

## Usage

```php
use EDocxParser\DocxParser;

$parser = new DocxParser();
$text = $parser->parse('path/to/file.docx');
```

## License

MIT
