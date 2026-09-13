<?php

namespace SalsabilEnnaiem\PvModule\Tests\Feature;

use SalsabilEnnaiem\PvModule\Models\Pv;
use SalsabilEnnaiem\PvModule\Notifications\PvValidationRequest;
use SalsabilEnnaiem\PvModule\Notifications\PvValidated;
use SalsabilEnnaiem\PvModule\Tests\Models\User;
use SalsabilEnnaiem\PvModule\Tests\TestCase;

class NotificationRoutePrefixTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('pv-module.routes.prefix', 'documents');
        $app['config']->set('pv-module.routes.name_prefix', 'documents.');
    }

    private function pv(): Pv
    {
        $creator = User::create(['name' => 'Creator', 'email' => 'creator@example.com', 'password' => 'secret']);

        return Pv::create([
            'titre' => 'PV routage',
            'contenu' => ['intro' => 'x'],
            'statut' => Pv::STATUT_EN_ATTENTE,
            'type' => 'pv',
            'created_by' => $creator->id,
        ]);
    }

    public function test_routes_are_registered_with_custom_prefix(): void
    {
        $this->expectException(\Symfony\Component\Routing\Exception\RouteNotFoundException::class);

        route('pv-module.show', 1);
    }

    public function test_validation_request_notification_urls_use_custom_name_prefix(): void
    {
        $pv = $this->pv();
        $notification = new PvValidationRequest($pv, $pv->createur);

        $expected = route('documents.show', $pv);
        $this->assertEquals($expected, $notification->toArray($pv->createur)['url']);
        $this->assertEquals($expected, $notification->toMail($pv->createur)->actionUrl);
    }

    public function test_validated_notification_urls_use_custom_name_prefix(): void
    {
        $pv = $this->pv();
        $notification = new PvValidated($pv, $pv->createur, true, null);

        $expected = route('documents.show', $pv);
        $this->assertEquals($expected, $notification->toArray($pv->createur)['url']);
        $this->assertEquals($expected, $notification->toMail($pv->createur)->actionUrl);
    }
}