<?php

namespace Tests\Feature;

use App\Entities\Applicant;
use App\Enums\ApplicantStatus;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreatesEntities;
use Tests\Concerns\RefreshesDoctrineSchema;
use Tests\TestCase;

class UpdateApplicantStatusTest extends TestCase
{
    use RefreshesDoctrineSchema, CreatesEntities;

    private const TOKEN = 'partner-token';

    private function patchStatus(string $routeId, string $status, ?string $bodyId = null, string $type = 'applicants'): TestResponse
    {
        return $this->withToken(self::TOKEN)->patchJson("/api/v1/applicants/{$routeId}", [
            'data' => ['type' => $type, 'id' => $bodyId ?? $routeId, 'attributes' => ['status' => $status]],
        ]);
    }

    private function storedStatus(string $id): ApplicantStatus
    {
        $this->em->clear();

        return $this->em->find(Applicant::class, $id)->getStatus();
    }

    private function ownApplicant(): Applicant
    {
        return $this->makeApplicant($this->makeCampaign($this->makePartner(self::TOKEN)), 91, 'Ada Lovelace');
    }

    public function test_status_change_is_saved_and_returned(): void
    {
        $id = $this->ownApplicant()->getId();

        $this->patchStatus($id, 'shortlisted')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonPath('data.type', 'applicants')
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.attributes.status', 'shortlisted')
            ->assertJsonPath('data.attributes.tier', 'top');

        $this->assertSame(ApplicantStatus::Shortlisted, $this->storedStatus($id));
    }

    public function test_status_can_go_back_to_pending(): void
    {
        $id = $this->ownApplicant()->getId();
        $this->patchStatus($id, 'rejected')->assertOk();

        $this->patchStatus($id, 'pending')->assertOk()->assertJsonPath('data.attributes.status', 'pending');
        $this->assertSame(ApplicantStatus::Pending, $this->storedStatus($id));
    }

    public function test_unknown_status_is_rejected_with_a_pointer(): void
    {
        $id = $this->ownApplicant()->getId();

        $this->patchStatus($id, 'hired')
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/status');
        $this->assertSame(ApplicantStatus::Pending, $this->storedStatus($id));
    }

    public function test_body_id_must_match_the_route(): void
    {
        $id = $this->ownApplicant()->getId();

        $this->patchStatus($id, 'shortlisted', (string) Str::uuid())
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/id');
    }

    public function test_resource_type_must_be_applicants(): void
    {
        $id = $this->ownApplicant()->getId();

        $this->patchStatus($id, 'shortlisted', type: 'people')
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/type');
    }

    public function test_empty_body_lists_every_missing_member(): void
    {
        $id = $this->ownApplicant()->getId();

        $response = $this->withToken(self::TOKEN)->patchJson("/api/v1/applicants/{$id}", [])->assertUnprocessable();

        $this->assertSame(
            ['/data/type', '/data/id', '/data/attributes/status'],
            array_column(array_column($response->json('errors'), 'source'), 'pointer'),
        );
    }

    public function test_malformed_applicant_id_is_not_found(): void
    {
        $this->makePartner(self::TOKEN);

        $this->patchStatus('not-a-uuid', 'shortlisted')
            ->assertNotFound()
            ->assertJsonPath('errors.0.detail', 'Applicant not found.');
    }

    public function test_an_array_as_body_id_is_rejected_not_a_crash(): void
    {
        $id = $this->ownApplicant()->getId();

        $this->withToken(self::TOKEN)
            ->patchJson("/api/v1/applicants/{$id}", ['data' => ['type' => 'applicants', 'id' => [$id], 'attributes' => ['status' => 'shortlisted']]])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/id');
    }

    public function test_uppercase_route_id_with_a_lowercase_body_id_is_accepted(): void
    {
        $id = $this->ownApplicant()->getId();

        $this->patchStatus(strtoupper($id), 'shortlisted', $id)->assertOk()->assertJsonPath('data.attributes.status', 'shortlisted');
    }

    public function test_another_partners_applicant_is_not_found(): void
    {
        $this->makePartner(self::TOKEN);
        $id = $this->makeApplicant($this->makeCampaign($this->makePartner('other-token', 'Other College')), 95)->getId();

        $this->patchStatus($id, 'rejected')
            ->assertNotFound()
            ->assertJsonPath('errors.0.detail', 'Applicant not found.');
        $this->assertSame(ApplicantStatus::Pending, $this->storedStatus($id));
    }

    public function test_unknown_applicant_is_not_found(): void
    {
        $this->makePartner(self::TOKEN);

        $this->patchStatus((string) Str::uuid(), 'shortlisted')->assertNotFound();
    }

    public function test_requires_a_token(): void
    {
        $id = $this->ownApplicant()->getId();

        $this->patchJson("/api/v1/applicants/{$id}", [])->assertUnauthorized();
    }

    public function test_json_api_media_type_body_is_accepted(): void
    {
        $id = $this->ownApplicant()->getId();
        $body = json_encode(['data' => ['type' => 'applicants', 'id' => $id, 'attributes' => ['status' => 'rejected']]]);

        // call() skips the default headers that withToken() sets, so the token goes in the server variables.
        $this->call('PATCH', "/api/v1/applicants/{$id}", [], [], [], [
            'CONTENT_TYPE' => 'application/vnd.api+json',
            'HTTP_ACCEPT' => 'application/vnd.api+json',
            'HTTP_AUTHORIZATION' => 'Bearer '.self::TOKEN,
        ], $body)
            ->assertOk()
            ->assertJsonPath('data.attributes.status', 'rejected');
    }
}
