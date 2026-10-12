<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Hidden;

#[Hidden]
class ApplicantAggregatesData extends Data
{
    public function __construct(
        public int $total,
        public int $eligible,
        public ?float $average,
    ) {}

    /**
     * @param  array<string, mixed>  $row  DQL row: MySQL returns COUNT and SUM as strings, AVG as a decimal string or null.
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['total'],
            (int) $row['eligible'],
            $row['average'] === null ? null : (float) $row['average'],
        );
    }
}
