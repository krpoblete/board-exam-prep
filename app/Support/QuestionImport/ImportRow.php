<?php

namespace App\Support\QuestionImport;

final class ImportRow
{
    /**
     * @param  array<string, string>  $raw
     * @param  array<string, mixed>|null  $data
     * @param  list<string>  $errors
     */
    public function __construct(
        public readonly int $row,
        public readonly array $raw,
        public readonly ?array $data,
        public readonly array $errors,
    ) {}

    public function isValid(): bool
    {
        return $this->errors === []
            && $this->data !== null;
    }
}
