<?php

namespace SalsabilEnnaiem\PvModule\Services;

use Mpdf\Mpdf;
use SalsabilEnnaiem\PvModule\Models\Pv;
use SalsabilEnnaiem\PvModule\Models\PvTemplate;

class PdfService
{
    public function mpdf(string $orientation = 'portrait', array $margins = []): Mpdf
    {
        return new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => $orientation === 'landscape' ? 'L' : 'P',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'autoArabic' => true,
            'default_font' => 'dejavusans',
            'margin_top' => $margins['top'] ?? 20,
            'margin_bottom' => $margins['bottom'] ?? 20,
            'margin_left' => $margins['left'] ?? 20,
            'margin_right' => $margins['right'] ?? 20,
        ]);
    }

    public function generatePv(Pv $pv): string
    {
        $payload = $this->pvPayload($pv);

        $mpdf = $this->mpdf($payload['orientation'], $payload['margins']);
        $mpdf->WriteHTML($this->renderHtml($payload));

        return $mpdf->Output('', 'S');
    }

    /**
     * Données partagées pour générer le document (aperçu HTML ou PDF).
     */
    public function pvPayload(Pv $pv): array
    {
        $pv->load(['validations.user', 'createur']);

        $template = PvTemplate::getActive($pv->created_by, $pv->type ?? 'pv')
            ?? PvTemplate::getDefault('pv');
        $sections = $template ? $template->getSectionsOrdered() : [];
        $margins = $template ? $template->getMargins() : [];

        $placementsBySection = [];
        foreach ($pv->receivers ?? [] as $receiver) {
            $userId = is_array($receiver) ? ($receiver['userId'] ?? null) : (is_numeric($receiver) ? (int) $receiver : null);
            $sectionId = is_array($receiver) ? ($receiver['sectionId'] ?? 'signature') : 'signature';
            if ($userId) {
                $placementsBySection[$sectionId][] = (int) $userId;
            }
        }

        $validationByUser = [];
        $userNames = [];
        foreach ($pv->validations as $validation) {
            $validationByUser[$validation->user_id] = $validation;
            if ($validation->user) {
                $userNames[$validation->user_id] = $validation->user->name;
            }
        }
        if ($pv->createur) {
            $userNames[$pv->created_by] = $pv->createur->name;
        }

        $signatureService = app(SignatureService::class);
        $signatures = [];
        foreach ($pv->validations as $validation) {
            if ($validation->statut === $validation::STATUT_VALIDE && $validation->user) {
                $data = $signatureService->getSignatureBase64($validation->user);
                if ($data) {
                    $signatures[$validation->user_id] = $data;
                }
            }
        }

        $userModel = $this->userModel();
        foreach ($pv->receivers ?? [] as $receiver) {
            $userId = is_array($receiver) ? (int) ($receiver['userId'] ?? 0) : (int) $receiver;
            if ($userId > 0 && !isset($userNames[$userId])) {
                $user = $userModel::find($userId);
                if ($user) {
                    $userNames[$userId] = $user->name;
                }
            }
        }

        return [
            'pv' => $pv,
            'template' => $template,
            'orientation' => $template?->orientation ?? 'portrait',
            'margins' => $margins,
            'sections' => $sections,
            'placementsBySection' => $placementsBySection,
            'validationByUser' => $validationByUser,
            'signatures' => $signatures,
            'userNames' => $userNames,
        ];
    }

    public function renderHtml(array $payload): string
    {
        return view('pv-module::pdfs.pv_template', $payload)->render();
    }

    protected function userModel(): string
    {
        return (string) config('pv-module.user_model', \App\Models\User::class);
    }
}