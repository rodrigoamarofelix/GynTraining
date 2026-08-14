<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    public function test_openapi_ui_is_available_in_local_environment(): void
    {
        $this->get('/docs/api')
            ->assertOk();
    }

    public function test_openapi_json_spec_is_available(): void
    {
        $response = $this->getJson('/docs/api.json');

        $response->assertOk()
            ->assertJsonStructure([
                'openapi',
                'info' => ['title', 'version'],
                'paths',
            ])
            ->assertJsonPath('info.title', 'GynTraining API');
    }

    public function test_openapi_spec_documents_auth_login_route(): void
    {
        $paths = $this->getJson('/docs/api.json')->json('paths');

        $this->assertArrayHasKey('/v1/auth/login', $paths);
        $this->assertArrayHasKey('post', $paths['/v1/auth/login']);
    }

    public function test_openapi_spec_documents_protected_workouts_route(): void
    {
        $paths = $this->getJson('/docs/api.json')->json('paths');

        $this->assertArrayHasKey('/v1/workouts', $paths);
    }
}
