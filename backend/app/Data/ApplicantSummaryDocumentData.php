<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ApplicantSummaryDocumentData extends Data
{
    /**
     * @param  list<ApplicantResourceData>  $data
     */
    public function __construct(
        public array $data,
        public ApplicantSummaryMetaData $meta,
        public PaginationLinksData $links,
    ) {}
}
