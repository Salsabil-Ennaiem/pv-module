<?php

namespace SalsabilEnnaiem\PvModule\Tests\Feature;

use SalsabilEnnaiem\PvModule\Models\Pv;
use SalsabilEnnaiem\PvModule\Models\PvSignature;
use SalsabilEnnaiem\PvModule\Models\PvValidation;
use SalsabilEnnaiem\PvModule\Seeders\DefaultPvTemplateSeeder;
use SalsabilEnnaiem\PvModule\Services\PdfService;
use SalsabilEnnaiem\PvModule\Services\SignatureService;
use SalsabilEnnaiem\PvModule\Tests\Models\User;
use SalsabilEnnaiem\PvModule\Tests\TestCase;

class PdfGenerationTest extends TestCase
{
    private const TINY_GIF = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    private const LONG_ARABIC = 'هذا نص طويل باللغة العربية يُستخدم للتحقق من أن محرك توليد ملفات PDF يعرض المحتوى من اليمين إلى اليسار دون كسر الأسطر أو قصّ الكلمات، مع الحفاظ على تشكيل الحروف واتصالها في الكلمات الطويلة مثل المصادقة والتوقيع الإلكتروني ووثائق اجتماعات اللجان.';

    protected function seedTemplates(): void
    {
        $this->seed(DefaultPvTemplateSeeder::class);
    }

    private function validSigner(): User
    {
        $user = User::create(['name' => 'Signataire', 'email' => 'sig@example.com', 'password' => 'secret']);
        app(SignatureService::class)->storeFromBase64('data:image/gif;base64,'.self::TINY_GIF, $user);

        return $user;
    }

    public function test_signature_records_mechanism_and_timestamp(): void
    {
        config(['pv-module.signature_mechanism' => 'simple_image']);

        $user = $this->validSigner();
        $signature = PvSignature::where('user_id', $user->getKey())->first();

        $this->assertNotNull($signature);
        $this->assertEquals('simple_image', $signature->signed_mechanism);
        $this->assertNotNull($signature->signed_at);
    }

    public function test_pdf_payload_exposes_signature_conformity_trace(): void
    {
        $this->seedTemplates();

        $creator = User::create(['name' => 'Creator', 'email' => 'creator@example.com', 'password' => 'secret']);
        $signer = $this->validSigner();

        $pv = Pv::create([
            'titre' => 'Réunion comité',
            'contenu' => ['intro' => 'Sujet', 'contenu' => 'Contenu du PV'],
            'statut' => Pv::STATUT_VALIDE,
            'type' => 'pv',
            'created_by' => $creator->id,
        ]);

        PvValidation::create([
            'pv_id' => $pv->id,
            'user_id' => $signer->id,
            'version' => 1,
            'statut' => PvValidation::STATUT_VALIDE,
            'date_reponse' => now(),
        ]);

        $payload = app(PdfService::class)->pvPayload($pv);

        $this->assertArrayHasKey($signer->id, $payload['signatures']);
        $this->assertArrayHasKey($signer->id, $payload['signatureMeta']);
        $this->assertEquals('simple_image', $payload['signatureMeta'][$signer->id]['mechanism']);
        $this->assertNotNull($payload['signatureMeta'][$signer->id]['signed_at']);
    }

    public function test_pdf_payload_html_asserts_rtl_direction_for_arabic(): void
    {
        $this->seedTemplates();
        config(['pv-module.default_locale' => 'ar']);

        $creator = User::create(['name' => 'Créateur', 'email' => 'c@example.com', 'password' => 'secret']);

        $pv = Pv::create([
            'titre' => 'محضر اجتماع',
            'contenu' => ['contenu' => self::LONG_ARABIC],
            'statut' => Pv::STATUT_BROUILLON,
            'type' => 'pv',
            'created_by' => $creator->id,
        ]);

        $payload = app(PdfService::class)->pvPayload($pv);
        $html = app(PdfService::class)->renderHtml($payload);

        $this->assertStringContainsString('lang="ar"', $html);
        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString(self::LONG_ARABIC, $html);
    }

    public function test_generate_pv_produces_valid_pdf_with_long_arabic(): void
    {
        $this->seedTemplates();
        config(['pv-module.default_locale' => 'ar']);

        $creator = User::create(['name' => 'Créateur', 'email' => 'c@example.com', 'password' => 'secret']);

        $pv = Pv::create([
            'titre' => 'محضر اجتماع اللجنة',
            'contenu' => ['contenu' => self::LONG_ARABIC],
            'statut' => Pv::STATUT_BROUILLON,
            'type' => 'pv',
            'created_by' => $creator->id,
        ]);

        $pdf = app(PdfService::class)->generatePv($pv);

        $this->assertGreaterThan(1000, strlen($pdf));
        $this->assertStringStartsWith('%PDF', $pdf);
    }
}