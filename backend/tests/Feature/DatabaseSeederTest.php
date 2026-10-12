<?php

namespace Tests\Feature;

use App\Entities\Partner;
use Database\Seeders\DatabaseSeeder;
use Tests\Concerns\RefreshesDoctrineSchema;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshesDoctrineSchema;

    public function test_demo_data_is_served_with_the_demo_token(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 24 applicants, 22 scored, sum 1552 -> average 70.5; 13 at or above 70; 97, 94 and 91 are top tier.
        $this->withToken(DatabaseSeeder::DEMO_TOKEN)
            ->getJson('/api/v1/campaigns/'.DatabaseSeeder::DEMO_CAMPAIGN_ID.'/applicant-summary')
            ->assertOk()
            ->assertJsonPath('meta.summary.totalApplicants', 24)
            ->assertJsonPath('meta.summary.eligibleApplicants', 13)
            ->assertJsonPath('meta.summary.averageAiScore', 70.5)
            ->assertJsonCount(3, 'meta.summary.topTier')
            ->assertJsonPath('meta.page.lastPage', 3);

        $this->withToken(DatabaseSeeder::DEMO_TOKEN)
            ->getJson('/api/v1/campaigns/'.DatabaseSeeder::EMPTY_CAMPAIGN_ID.'/applicant-summary')
            ->assertOk()
            ->assertJsonPath('meta.summary.totalApplicants', 0);
    }

    public function test_seeding_twice_does_not_duplicate_or_crash(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertCount(1, $this->em->getRepository(Partner::class)->findAll());
    }
}
