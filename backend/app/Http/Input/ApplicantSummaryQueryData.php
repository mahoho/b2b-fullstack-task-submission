<?php

namespace App\Http\Input;

use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class ApplicantSummaryQueryData extends Data
{
    public function __construct(
        #[FromRouteParameter('campaignId'), Uuid]
        public string $campaignId,
        #[MapInputName('min_score'), IntegerType, Between(0, 100)]
        public ?int $minScore = null,
        #[MapInputName('page.size'), IntegerType, Between(1, 50)]
        public int $pageSize = 10,
        #[MapInputName('page.number'), IntegerType, Min(1)]
        public int $pageNumber = 1,
    ) {}

    public static function attributes(): array
    {
        return ['page.size' => 'page[size]', 'page.number' => 'page[number]'];
    }
}
