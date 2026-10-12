<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class JsonApiErrorSourceData extends Data
{
    public function __construct(
        public string|Optional $parameter = new Optional,
        public string|Optional $pointer = new Optional,
    ) {}
}
