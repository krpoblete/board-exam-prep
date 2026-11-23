<?php

namespace App\Support\QuestionImport;

final class ImportResult
{
    /** @param list<ImportRow> $rows */
    public function __construct(
        public readonly ?string $fatal,
        public readonly array $rows,
    ) {}

    public static function fatal(string $message): self
    {
        return new self($message, []);
    }

    public function validCount(): int
    {
        return count(array_filter($this->rows, fn (ImportRow $row): bool => $row->isValid()));
    }

    public function invalidCount(): int
    {
        return count($this->rows) - $this->validCount();
    }
}
