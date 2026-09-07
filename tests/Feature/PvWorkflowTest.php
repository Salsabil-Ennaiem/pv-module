<?php

namespace SalsabilEnnaiem\PvModule\Tests\Feature;

use SalsabilEnnaiem\PvModule\Contracts\ApprovalRules;
use SalsabilEnnaiem\PvModule\Contracts\CanManagePv;
use SalsabilEnnaiem\PvModule\Contracts\ParticipantResolver;
use SalsabilEnnaiem\PvModule\Models\Pv;
use SalsabilEnnaiem\PvModule\Tests\Models\User;
use SalsabilEnnaiem\PvModule\Tests\TestCase;

class PvWorkflowTest extends TestCase
{
    public function test_contracts_resolve_from_container(): void
    {
        $this->assertInstanceOf(CanManagePv::class, app(CanManagePv::class));
        $this->assertInstanceOf(ApprovalRules::class, app(ApprovalRules::class));
        $this->assertInstanceOf(ParticipantResolver::class, app(ParticipantResolver::class));
    }

    public function test_pv_module_routes_are_registered(): void
    {
        $routes = app('router')->getRoutes()->getRoutesByMethod();

        collect(['GET', 'POST', 'PUT', 'DELETE'])->each(function ($method) use ($routes) {
            foreach ($routes[$method] ?? [] as $route) {
                if (str_contains($route->uri(), 'pv-module')) {
                    $this->assertStringContainsString('SalsabilEnnaiem\\PvModule', (string) $route->getActionName());
                }
            }
        });
    }

    public function test_default_pv_template_seeder_runs(): void
    {
        $this->seed(\SalsabilEnnaiem\PvModule\Seeders\DefaultPvTemplateSeeder::class);

        $this->assertDatabaseHas('pv_module_pdf_templates', [
            'type' => 'pv',
            'is_default' => true,
        ]);
    }

    public function test_pv_can_be_created_and_versions_recorded(): void
    {
        $user = User::create([
            'name' => 'Creator',
            'email' => 'creator@example.com',
            'password' => 'secret',
        ]);

        $pv = Pv::create([
            'titre' => 'Réunion du comité',
            'contenu' => ['intro' => 'Ordre du jour'],
            'statut' => Pv::STATUT_BROUILLON,
            'type' => 'pv',
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('pv_module_pvs', ['titre' => 'Réunion du comité']);

        $pv->recordVersion();
        $pv->save();
        $this->assertCount(1, $pv->fresh()->versions);

        $this->assertEquals(Pv::STATUT_BROUILLON, $pv->statut);
        $this->assertInstanceOf(User::class, $pv->createur);
    }

    public function test_config_types_is_array(): void
    {
        $types = config('pv-module.types', ['pv']);

        $this->assertIsArray($types);
        $this->assertContains('pv', $types);
    }

    public function test_views_render(): void
    {
        $html = view('pv-module::partials.status-badge', [
            'statut' => Pv::STATUT_EN_ATTENTE,
        ])->render();

        $this->assertStringContainsString('en_attente', $html);
    }
}