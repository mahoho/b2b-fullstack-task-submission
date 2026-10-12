<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class JsonApiErrorDocumentData extends Data
{
    /**
     * @param  list<JsonApiErrorData>  $errors
     */
    public function __construct(
        public array $errors,
    ) {}
}
