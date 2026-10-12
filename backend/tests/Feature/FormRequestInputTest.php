<?php

namespace Tests\Feature;

use App\Enums\ApplicantStatus;
use App\Http\Requests\ApplicantSummaryRequest;
use App\Http\Requests\UpdateApplicantStatusRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Route;
use Tests\TestCase;

class FormRequestInputTest extends TestCase
{
    private const CAMPAIGN_ID = '0b8f3c2e-5a61-4c7d-9e2a-3f4b5c6d7e81';

    private const APPLICANT_ID = '11111111-1111-4111-8111-111111111111';

    /**
     * @template T of FormRequest
     *
     * @param  T  $request
     * @return T
     */
    private function resolve(FormRequest $request, string $method, string $uri): FormRequest
    {
        $request->setRouteResolver(fn () => (new Route($method, $uri, []))->bind($request));
        $request->setContainer($this->app)->setRedirector($this->app->make(Redirector::class));
        $request->validateResolved();

        return $request;
    }

    public function test_summary_request_builds_its_dto_from_the_route_and_query(): void
    {
        $request = $this->resolve(
            ApplicantSummaryRequest::create('/api/v1/campaigns/'.self::CAMPAIGN_ID.'/applicant-summary', 'GET', ['min_score' => '80', 'page' => ['size' => '5']]),
            'GET',
            'api/v1/campaigns/{campaignId}/applicant-summary',
        );

        $data = $request->toData();

        $this->assertSame(self::CAMPAIGN_ID, $data->campaignId);
        $this->assertSame(80, $data->minScore);
        $this->assertSame(5, $data->pageSize);
        $this->assertSame(1, $data->pageNumber);
    }

    public function test_summary_validation_leaves_the_query_untouched(): void
    {
        $request = $this->resolve(
            ApplicantSummaryRequest::create('/api/v1/campaigns/'.self::CAMPAIGN_ID.'/applicant-summary', 'GET', ['min_score' => '80']),
            'GET',
            'api/v1/campaigns/{campaignId}/applicant-summary',
        );

        // Anything that later rebuilds a URL from the query, such as fullUrlWithQuery(), must not pick up the route id.
        $this->assertSame(['min_score' => '80'], $request->query());
    }

    public function test_status_validation_leaves_the_body_untouched(): void
    {
        $body = ['data' => ['type' => 'applicants', 'id' => self::APPLICANT_ID, 'attributes' => ['status' => 'shortlisted']]];
        $request = UpdateApplicantStatusRequest::create(
            '/api/v1/applicants/'.self::APPLICANT_ID,
            'PATCH',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body),
        );

        $request = $this->resolve($request, 'PATCH', 'api/v1/applicants/{applicantId}');

        $this->assertSame($body, $request->json()->all());
        $this->assertSame(ApplicantStatus::Shortlisted, $request->toData()->status);
        $this->assertSame(self::APPLICANT_ID, $request->toData()->applicantId);
    }
}
