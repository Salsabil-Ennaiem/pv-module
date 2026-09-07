<?php

namespace SalsabilEnnaiem\PvModule\Tests\Feature;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use SalsabilEnnaiem\PvModule\Models\Pv;
use SalsabilEnnaiem\PvModule\Models\PvValidation;
use SalsabilEnnaiem\PvModule\Services\SignatureService;
use SalsabilEnnaiem\PvModule\Tests\Models\User;
use SalsabilEnnaiem\PvModule\Tests\TestCase;

class PvSecurityTest extends TestCase
{
    public function test_random_user_cannot_view_someone_elses_pv(): void
    {
        $owner = User::create(['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'secret']);
        $intruder = User::create(['name' => 'Intruder', 'email' => 'intruder@example.com', 'password' => 'secret']);

        $pv = Pv::create([
            'titre' => 'PV secret',
            'contenu' => ['intro' => 'confidentiel'],
            'statut' => Pv::STATUT_BROUILLON,
            'type' => 'pv',
            'created_by' => $owner->id,
        ]);

        $this->actingAs($intruder)
            ->get(route('pv-module.show', $pv))
            ->assertForbidden();
    }

    public function test_participant_can_view_pv(): void
    {
        $owner = User::create(['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'secret']);
        $validator = User::create(['name' => 'Validator', 'email' => 'validator@example.com', 'password' => 'secret']);

        $pv = Pv::create([
            'titre' => 'PV partagé',
            'contenu' => ['intro' => 'x'],
            'statut' => Pv::STATUT_EN_ATTENTE,
            'type' => 'pv',
            'created_by' => $owner->id,
        ]);

        PvValidation::create([
            'pv_id' => $pv->id,
            'user_id' => $validator->id,
            'version' => 1,
            'statut' => PvValidation::STATUT_EN_ATTENTE,
        ]);

        $this->actingAs($validator)
            ->get(route('pv-module.show', $pv))
            ->assertOk();
    }

    public function test_index_only_shows_own_pvs(): void
    {
        $userA = User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'secret']);
        $userB = User::create(['name' => 'B', 'email' => 'b@example.com', 'password' => 'secret']);

        Pv::create([
            'titre' => 'PV de A',
            'contenu' => ['intro' => 'x'],
            'statut' => Pv::STATUT_BROUILLON,
            'type' => 'pv',
            'created_by' => $userA->id,
        ]);

        Pv::create([
            'titre' => 'PV de B',
            'contenu' => ['intro' => 'x'],
            'statut' => Pv::STATUT_BROUILLON,
            'type' => 'pv',
            'created_by' => $userB->id,
        ]);

        $this->actingAs($userA)
            ->get('/pv-module')
            ->assertOk()
            ->assertSee('PV de A')
            ->assertDontSee('PV de B');
    }

    public function test_cannot_read_another_users_notification(): void
    {
        $userA = User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'secret']);
        $userB = User::create(['name' => 'B', 'email' => 'b@example.com', 'password' => 'secret']);

        $notification = DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'dummy',
            'notifiable_type' => User::class,
            'notifiable_id' => $userA->id,
            'data' => json_encode(['url' => '/pv-module']),
        ]);

        $this->actingAs($userB)
            ->post(route('pv-module.notifications.read', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->refresh()->read_at);
    }

    public function test_open_redirect_blocked_for_external_url_in_notification(): void
    {
        $userA = User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'secret']);

        $notification = DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'dummy',
            'notifiable_type' => User::class,
            'notifiable_id' => $userA->id,
            'data' => json_encode(['url' => 'https://evil.example.com/phish']),
        ]);

        $this->actingAs($userA)
            ->post(route('pv-module.notifications.read', $notification->id))
            ->assertRedirect(route('pv-module.index'));
    }

    public function test_invalid_image_upload_is_rejected_by_service(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'secret']);

        app(SignatureService::class)
            ->storeFromBase64(base64_encode('not an image, just text'), $user);
    }
}