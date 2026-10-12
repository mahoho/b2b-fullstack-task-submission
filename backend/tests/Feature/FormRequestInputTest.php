<?php

namespace Tests\Feature;

use App\Http\Requests\ApplicantSummaryRequest;
use Illuminate\Routing\Redirector;
use Tests\TestCase;

class FormRequestInputTest extends TestCase
{
    /**
     * @param array<string, mixed> $query
     */
    private function resolve(array $query): ApplicantSummaryRequest
    {
        $request = ApplicantSummaryRequest::create('/api/v1/campaigns/0b8f3c2e-5a61-4c7d-9e2a-3f4b5c6d7e81/applicant-summary', 'GET', $query);
        $request->setContainer($this->app)->setRedirector($this->app->make(Redirector::class));
        $request->validateResolved();

        return $request;
    }

    public function test_summary_request_builds_its_dto_from_the_query(): void
    {
        $data = $this->resolve(['min_score' => '80', 'page' => ['size' => '5']])->toData();

        $this->assertSame(80, $data->minScore);
        $this->assertSame(5, $data->pageSize);
        $this->assertSame(1, $data->pageNumber);
    }

    public function test_summary_request_defaults_without_a_query(): void
    {
        $data = $this->resolve([])->toData();

        $this->assertNull($data->minScore);
        $this->assertSame(10, $data->pageSize);
        $this->assertSame(1, $data->pageNumber);
    }

    public function test_summary_validation_leaves_the_query_untouched(): void
    {
        // Anything that later rebuilds a URL from the query, such as fullUrlWithQuery(), must see only what the client sent.
        $this->assertSame(['min_score' => '80'], $this->resolve(['min_score' => '80'])->query());
    }
}
