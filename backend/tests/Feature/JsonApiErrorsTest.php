<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\Concerns\CreatesEntities;
use Tests\Concerns\RefreshesDoctrineSchema;
use Tests\TestCase;

class JsonApiErrorsTest extends TestCase
{
    use RefreshesDoctrineSchema, CreatesEntities;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['api', 'partner'])->prefix('api/v1/_test')->group(function (): void {
            Route::get('ok', fn () => ['ok' => true]);
            Route::get('missing', fn () => abort(404, 'Campaign not found.'));
            Route::get('boom', fn () => throw new RuntimeException('database password is hunter2'));
            Route::get('invalid', fn () => throw ValidationException::withMessages([
                'page.size' => ['The page[size] field must be between 1 and 50.'],
                'min_score' => ['The min score field must be an integer.'],
                'data.attributes.status' => ['The selected status is invalid.'],
            ]));
        });
    }

    public function test_missing_token_is_a_json_api_401(): void
    {
        $this->getJson('/api/v1/_test/ok')
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson(['errors' => [[
                'status' => '401',
                'title' => 'Unauthenticated',
                'detail' => 'A valid bearer token is required.',
            ]]]);
    }

    public function test_unknown_token_is_a_401(): void
    {
        $this->makePartner('right-token');

        $this->withToken('wrong-token')->getJson('/api/v1/_test/ok')->assertUnauthorized();
    }

    public function test_valid_token_passes_and_success_uses_the_json_api_content_type(): void
    {
        $this->makePartner('right-token');

        $this->withToken('right-token')->getJson('/api/v1/_test/ok')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson(['ok' => true]);
    }

    public function test_http_exception_keeps_its_status_and_message(): void
    {
        $this->makePartner('right-token');

        $this->withToken('right-token')->getJson('/api/v1/_test/missing')
            ->assertNotFound()
            ->assertExactJson(['errors' => [['status' => '404', 'title' => 'Not Found', 'detail' => 'Campaign not found.']]]);
    }

    public function test_unknown_api_route_is_a_json_api_404(): void
    {
        $this->getJson('/api/v1/does-not-exist')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonPath('errors.0.status', '404');
    }

    public function test_unexpected_exception_hides_its_details(): void
    {
        $this->makePartner('right-token');

        $response = $this->withToken('right-token')->getJson('/api/v1/_test/boom');

        $response->assertStatus(500)->assertExactJson(['errors' => [['status' => '500', 'title' => 'Server Error']]]);
        $this->assertStringNotContainsString('hunter2', $response->getContent());
    }

    public function test_validation_errors_map_to_parameters_and_pointers(): void
    {
        $this->makePartner('right-token');

        $this->withToken('right-token')->getJson('/api/v1/_test/invalid')
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson(['errors' => [
                ['status' => '422', 'title' => 'Invalid parameter', 'detail' => 'The page[size] field must be between 1 and 50.', 'source' => ['parameter' => 'page[size]']],
                ['status' => '422', 'title' => 'Invalid parameter', 'detail' => 'The min score field must be an integer.', 'source' => ['parameter' => 'min_score']],
                ['status' => '422', 'title' => 'Invalid attribute', 'detail' => 'The selected status is invalid.', 'source' => ['pointer' => '/data/attributes/status']],
            ]]);
    }
}
