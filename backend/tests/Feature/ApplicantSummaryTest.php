<?php

namespace Tests\Feature;

use App\Entities\Campaign;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesEntities;
use Tests\Concerns\RefreshesDoctrineSchema;
use Tests\TestCase;

class ApplicantSummaryTest extends TestCase
{
    use RefreshesDoctrineSchema, CreatesEntities;

    private const TOKEN = 'partner-token';

    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campaign = $this->makeCampaign($this->makePartner(self::TOKEN), eligibilityThreshold: 70);
    }

    private function summary(string $query = '', ?string $campaignId = null): TestResponse
    {
        $id = $campaignId ?? $this->campaign->getId();

        return $this->withToken(self::TOKEN)->getJson("/api/v1/campaigns/{$id}/applicant-summary{$query}");
    }

    /**
     * Nine applicants: two top tier (95, 91), four more eligible (88, 88, 75, 70), two below (64, 40), one unscored.
     * Average of the eight scored is 611 / 8 = 76.375.
     */
    private function seedApplicants(): void
    {
        $this->makeApplicant($this->campaign, 88, 'Grace Hopper', '2026-10-02 10:00:00');
        $this->makeApplicant($this->campaign, 95, 'Ada Lovelace', '2026-10-03 10:00:00');
        $this->makeApplicant($this->campaign, 40, 'Charles Babbage', '2026-10-01 10:00:00');
        $this->makeApplicant($this->campaign, null, 'Alan Turing', '2026-10-01 08:00:00');
        $this->makeApplicant($this->campaign, 70, 'Katherine Johnson', '2026-10-04 10:00:00');
        $this->makeApplicant($this->campaign, 91, 'Margaret Hamilton', '2026-10-01 10:00:00');
        $this->makeApplicant($this->campaign, 64, 'Dorothy Vaughan', '2026-10-05 10:00:00');
        $this->makeApplicant($this->campaign, 75, 'Mary Jackson', '2026-10-06 10:00:00');
        $this->makeApplicant($this->campaign, 88, 'Hedy Lamarr', '2026-10-01 09:00:00');
    }

    /**
     * @return list<string>
     */
    private function names(TestResponse $response): array
    {
        return array_column(array_column($response->json('data'), 'attributes'), 'name');
    }

    public function test_returns_the_campaign_summary_and_ordered_applicants(): void
    {
        $this->seedApplicants();

        $response = $this->summary()
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonPath('meta.summary.totalApplicants', 9)
            ->assertJsonPath('meta.summary.eligibleApplicants', 6)
            ->assertJsonPath('meta.summary.averageAiScore', 76.4)
            ->assertJsonCount(2, 'meta.summary.topTier')
            ->assertJsonPath('meta.summary.topTier.0.name', 'Ada Lovelace')
            ->assertJsonPath('meta.summary.topTier.0.aiScore', 95)
            ->assertJsonPath('meta.summary.topTier.1.name', 'Margaret Hamilton')
            ->assertJsonPath('meta.page', ['currentPage' => 1, 'perPage' => 10, 'total' => 9, 'lastPage' => 1]);

        $this->assertSame([
            'Ada Lovelace', 'Margaret Hamilton', 'Hedy Lamarr', 'Grace Hopper', 'Mary Jackson',
            'Katherine Johnson', 'Dorothy Vaughan', 'Charles Babbage', 'Alan Turing',
        ], $this->names($response));
        $this->assertSame(
            ['top', 'top', 'eligible', 'eligible', 'eligible', 'eligible', 'below', 'below', 'unscored'],
            array_column(array_column($response->json('data'), 'attributes'), 'tier'),
        );
    }

    public function test_applicant_resource_shape(): void
    {
        $applicant = $this->makeApplicant($this->campaign, 95, 'Ada Lovelace', '2026-10-03 10:00:00', ['Led a robotics club', 'Strong maths portfolio']);

        $this->summary()->assertOk()->assertJsonPath('data.0', [
            'type' => 'applicants',
            'id' => $applicant->getId(),
            'attributes' => [
                'name' => 'Ada Lovelace',
                'email' => 'ada.lovelace@example.com',
                'aiScore' => 95,
                'tier' => 'top',
                'status' => 'pending',
                'appliedAt' => '2026-10-03T10:00:00+00:00',
                'highlights' => ['Led a robotics club', 'Strong maths portfolio'],
            ],
        ]);
    }

    public function test_min_score_filters_the_list_but_not_the_summary(): void
    {
        $this->seedApplicants();

        $response = $this->summary('?min_score=80')
            ->assertOk()
            ->assertJsonPath('meta.page.total', 4)
            ->assertJsonPath('meta.summary.totalApplicants', 9)
            ->assertJsonPath('meta.summary.averageAiScore', 76.4);

        $this->assertSame(['Ada Lovelace', 'Margaret Hamilton', 'Hedy Lamarr', 'Grace Hopper'], $this->names($response));
        $this->assertStringContainsString('min_score=80', $response->json('links.self'));
    }

    public function test_min_score_zero_leaves_out_unscored_applicants(): void
    {
        $this->seedApplicants();

        $this->summary('?min_score=0')->assertOk()->assertJsonPath('meta.page.total', 8);
    }

    public function test_paginates_with_json_api_links(): void
    {
        $this->seedApplicants();
        $base = url("/api/v1/campaigns/{$this->campaign->getId()}/applicant-summary");

        $response = $this->summary('?page[size]=2&page[number]=2')
            ->assertOk()
            ->assertJsonPath('meta.page', ['currentPage' => 2, 'perPage' => 2, 'total' => 9, 'lastPage' => 5])
            ->assertJsonPath('links', [
                'self' => "{$base}?page[number]=2&page[size]=2",
                'first' => "{$base}?page[number]=1&page[size]=2",
                'prev' => "{$base}?page[number]=1&page[size]=2",
                'next' => "{$base}?page[number]=3&page[size]=2",
                'last' => "{$base}?page[number]=5&page[size]=2",
            ]);

        $this->assertSame(['Hedy Lamarr', 'Grace Hopper'], $this->names($response));
    }

    public function test_first_page_has_no_prev_and_last_page_has_no_next(): void
    {
        $this->seedApplicants();

        $this->summary('?page[size]=5')->assertJsonPath('links.prev', null)->assertJsonPath('meta.page.lastPage', 2);
        $this->summary('?page[size]=5&page[number]=2')->assertJsonPath('links.next', null)->assertJsonCount(4, 'data');
    }

    public function test_page_past_the_end_is_empty(): void
    {
        $this->seedApplicants();

        $this->summary('?page[number]=9')
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.page', ['currentPage' => 9, 'perPage' => 10, 'total' => 9, 'lastPage' => 1]);

        $this->summary('?page[number]=9000000000000000000')->assertOk()->assertJsonPath('data', []);
    }

    public function test_zero_applicants_returns_a_zeroed_summary(): void
    {
        $this->summary()
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.summary', ['totalApplicants' => 0, 'eligibleApplicants' => 0, 'averageAiScore' => 0, 'topTier' => []])
            ->assertJsonPath('meta.page', ['currentPage' => 1, 'perPage' => 10, 'total' => 0, 'lastPage' => 1])
            ->assertJsonPath('links.next', null);
    }

    public function test_only_unscored_applicants_gives_a_zero_average(): void
    {
        $this->makeApplicant($this->campaign, null, 'Alan Turing');
        $this->makeApplicant($this->campaign, null, 'Joan Clarke');

        $this->summary()
            ->assertOk()
            ->assertJsonPath('meta.summary', ['totalApplicants' => 2, 'eligibleApplicants' => 0, 'averageAiScore' => 0, 'topTier' => []]);
    }

    public function test_top_tier_starts_at_90_and_is_capped_at_three(): void
    {
        $this->makeApplicant($this->campaign, 89, 'Below Top', '2026-10-01 09:00:00');
        $this->makeApplicant($this->campaign, 90, 'Exactly Ninety Late', '2026-10-03 09:00:00');
        $this->makeApplicant($this->campaign, 90, 'Exactly Ninety Early', '2026-10-02 09:00:00');
        $this->makeApplicant($this->campaign, 97, 'Second Best', '2026-10-01 09:00:00');
        $this->makeApplicant($this->campaign, 99, 'Best', '2026-10-05 09:00:00');

        $this->summary()
            ->assertOk()
            ->assertJsonCount(3, 'meta.summary.topTier')
            ->assertJsonPath('meta.summary.topTier.0.name', 'Best')
            ->assertJsonPath('meta.summary.topTier.1.name', 'Second Best')
            ->assertJsonPath('meta.summary.topTier.2.name', 'Exactly Ninety Early');
    }

    public function test_uppercase_campaign_id_finds_the_campaign(): void
    {
        $this->makeApplicant($this->campaign, 80);

        $this->summary('', strtoupper($this->campaign->getId()))->assertOk()->assertJsonPath('meta.summary.totalApplicants', 1);
    }

    public function test_malformed_campaign_id_is_an_invalid_identifier(): void
    {
        $this->summary('', 'not-a-uuid')
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.code', 'invalid_identifier')
            ->assertJsonMissingPath('errors.0.source');
    }

    public static function invalidQueries(): array
    {
        return [
            'page size zero' => ['?page[size]=0', 'page[size]'],
            'page size over 50' => ['?page[size]=51', 'page[size]'],
            'page size as array' => ['?page[size][]=5', 'page[size]'],
            'page number zero' => ['?page[number]=0', 'page[number]'],
            'page number not a number' => ['?page[number]=abc', 'page[number]'],
            'min score over 100' => ['?min_score=101', 'min_score'],
            'negative min score' => ['?min_score=-1', 'min_score'],
            'min score not a number' => ['?min_score=abc', 'min_score'],
            'fractional min score' => ['?min_score=80.5', 'min_score'],
        ];
    }

    #[DataProvider('invalidQueries')]
    public function test_invalid_query_parameters_are_rejected(string $query, string $parameter): void
    {
        $this->summary($query)
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.status', '422')
            ->assertJsonPath('errors.0.source.parameter', $parameter);
    }

    public function test_unknown_campaign_is_not_found(): void
    {
        $this->summary('', (string) Str::uuid())
            ->assertNotFound()
            ->assertExactJson(['errors' => [['status' => '404', 'title' => 'Not Found', 'detail' => 'Campaign not found.']]]);
    }

    public function test_another_partners_campaign_looks_the_same_as_an_unknown_one(): void
    {
        $other = $this->makeCampaign($this->makePartner('other-token', 'Other College'));
        $this->makeApplicant($other, 99);

        $this->summary('', $other->getId())
            ->assertNotFound()
            ->assertExactJson(['errors' => [['status' => '404', 'title' => 'Not Found', 'detail' => 'Campaign not found.']]]);
    }

    public function test_requires_a_valid_token(): void
    {
        $url = "/api/v1/campaigns/{$this->campaign->getId()}/applicant-summary";

        $this->getJson($url)->assertUnauthorized();
        $this->withToken('wrong-token')->getJson($url)->assertUnauthorized();
    }

    public function test_authentication_runs_before_validation(): void
    {
        $this->getJson('/api/v1/campaigns/not-a-uuid/applicant-summary?page[size]=999')->assertUnauthorized();
    }
}
