<?php

declare(strict_types=1);

namespace EDocxParser\Formatter;

/**
 * Interface FormatterInterface
 *
 * This interface defines the contract for formatting text.
 * 
 */
interface FormatterInterface
{
    public function format(string $text): string;
}
