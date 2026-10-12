<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class PageMetaData extends Data
{
    public function __construct(
        public int $currentPage,
        public int $perPage,
        public int $total,
        public int $lastPage,
    ) {}
}
