<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class PaginationLinksData extends Data
{
    public function __construct(
        public string $self,
        public string $first,
        public ?string $prev,
        public ?string $next,
        public string $last,
    ) {}
}
