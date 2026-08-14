<?php

namespace Tests\Feature\Web;

use Tests\TestCase;

class FrontendTest extends TestCase
{
    public function test_spa_shell_is_served_on_home(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="app"', false)
            ->assertSee('GynTraining', false);
    }

    public function test_spa_shell_is_served_on_client_routes(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/treino')
            ->assertOk()
            ->assertSee('id="app"', false);
    }

    public function test_api_docs_remain_available(): void
    {
        $this->get('/docs/api')
            ->assertOk();
    }

    public function test_trainer_routes_serve_spa_shell(): void
    {
        $this->get('/professor/alunos')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/professor/fichas/nova')
            ->assertOk()
            ->assertSee('id="app"', false);
    }

    public function test_admin_routes_serve_spa_shell(): void
    {
        $this->get('/admin/academias')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/admin/alunos')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/admin/professores')
            ->assertOk()
            ->assertSee('id="app"', false);
    }
}
