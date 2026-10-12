<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class JsonApiErrorData extends Data
{
    public function __construct(
        public string $status,
        public string $title,
        public string|Optional $detail = new Optional(),
        public string|Optional $code = new Optional(),
        public JsonApiErrorSourceData|Optional $source = new Optional(),
    ) {}
}
