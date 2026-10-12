<?php

namespace Tests\Unit;

use App\Data\JsonApiErrorData;
use App\Data\JsonApiErrorSourceData;
use Tests\TestCase;

class JsonApiErrorDataTest extends TestCase
{
    public function test_source_is_a_dto_that_serialises_only_what_is_set(): void
    {
        $parameter = new JsonApiErrorData('422', 'Invalid parameter', source: new JsonApiErrorSourceData(parameter: 'page[size]'));
        $pointer = new JsonApiErrorData('422', 'Invalid attribute', source: new JsonApiErrorSourceData(pointer: '/data/id'));

        $this->assertSame(['status' => '422', 'title' => 'Invalid parameter', 'source' => ['parameter' => 'page[size]']], $parameter->toArray());
        $this->assertSame(['status' => '422', 'title' => 'Invalid attribute', 'source' => ['pointer' => '/data/id']], $pointer->toArray());
    }
}
