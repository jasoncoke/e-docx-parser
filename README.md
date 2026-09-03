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
<?php

use EDocxParser\DocxParser;

$html = DocxParser::create('path/to/file.docx')->parse();
```

## Test a local DOCX file from the terminal

Install the dependencies first:

```bash
composer install
```

Use `DocxReader` to output parsed HTML:

```bash
composer docx:read -- ./documents/example.docx
```

Use `DocxReader` to output the raw `word/document.xml` instead:

```bash
composer docx:read -- --xml ./documents/example.docx
```

Use `DocxParser` to output HTML, optionally removing formatting tags:

```bash
composer docx:parse -- ./documents/example.docx
composer docx:parse -- --strip-bold ./documents/example.docx
composer docx:parse -- --strip-bold --strip-italic ./documents/example.docx
composer docx:parse -- --strip-all ./documents/example.docx
```

Paths containing spaces must be quoted:

```bash
composer docx:parse -- --strip-all "/path/to/My Document.docx"
```

The executable files can also be run directly:

```bash
php bin/docx-reader --html ./documents/example.docx
php bin/docx-parser --strip-all ./documents/example.docx
```

## License

MIT
