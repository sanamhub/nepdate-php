<?php

declare(strict_types=1);

namespace NepDate;

use RuntimeException;

/**
 * Raised for every invalid date, parse failure or out-of-range operation.
 *
 * {@see kind()} names the failed rule (PARSING.md §4) so callers can match on it instead of
 * parsing the message text.
 */
final class NepDateException extends RuntimeException
{
    public function __construct(
        private readonly ErrorKind $kind,
        string $message,
    ) {
        parent::__construct($message);
    }

    public function kind(): ErrorKind
    {
        return $this->kind;
    }
}
