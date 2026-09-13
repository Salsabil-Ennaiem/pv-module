{{-- Template de rendu du PV (aperçu HTML + PDF mpdf). --}}
@php
    $sectionStyles = [
        'titleColor' => '#1d4ed8',
        'titleSize' => 13,
        'font' => 'DejaVu Sans',
        'textColor' => '#0f172a',
        'textSize' => 11,
    ];

    $signaturePlacements = array_values(array_unique(array_merge(
        $placementsBySection['signature'] ?? [],
        $placementsBySection['participants'] ?? [],
    )));

    $hasPlacements = ($placementsBySection['contenu'] ?? []) || ($placementsBySection['participants'] ?? []);

    $textAlignCss = fn (array $styles, string $fallback = 'left') => (string) ($styles['textAlign'] ?? $fallback);

    // Directionnalité du document (R3) — décision purement visuelle, aucun impact métier.
    $docLocale = (string) config('pv-module.default_locale', 'fr') ?: 'fr';
    $rtlLocales = (array) config('pv-module.rtl_locales', ['ar', 'he', 'fa', 'ur']);
    $isRtlDoc = in_array($docLocale, $rtlLocales, true);
    $htmlDir = $isRtlDoc ? 'rtl' : 'ltr';

    $dirFor = fn (array $styles) => (string) ($styles['direction'] ?? $htmlDir);
@endphp
<html lang="{{ $docLocale }}" dir="{{ $htmlDir }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 11px; line-height: 1.5; }
        .pv-sheet { width: 100%; direction: {{ $htmlDir }}; unicode-bidi: embed; }
        .pv-title { text-align: center; font-size: 16px; font-weight: bold; color: #1d4ed8; margin-bottom: 24px; }
        .pv-meta { margin-bottom: 20px; font-size: 12px; }
        .pv-meta td { padding: 2px 0; }
        .pv-section { margin-bottom: 14px; }
        .pv-section-title {
            font-size: 13px;
            font-weight: bold;
            color: #334155;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }
        .pv-body { white-space: pre-line; direction: {{ $htmlDir }}; unicode-bidi: embed; }
        .pv-participants { width: 100%; border-collapse: collapse; }
        .pv-participants th, .pv-participants td {
            border: 1px solid #cbd5e1; padding: 5px 8px; font-size: 11px;
        }
        .pv-participants th { background: #f1f5f9; }
        .pv-signatures { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .pv-signatures td { padding: 10px 8px; vertical-align: bottom; }
        .pv-signature-cell { text-align: center; }
        .pv-signature-date { font-size: 10px; color: #64748b; }
        .pv-signature-line { border-bottom: 1px dotted #94a3b8; height: 40px; }
    </style>
</head>
<body>
<div class="pv-sheet" lang="{{ $docLocale }}" dir="{{ $htmlDir }}">
    @foreach ($sections as $section)
        @php
            $style = array_merge($sectionStyles, $section['styles'] ?? []);
            $id = $section['id'] ?? '';
            $dir = $dirFor($style ?? []);
        @endphp

        @if ($id === 'header')
            <div class="pv-title" style="color: {{ $style['titleColor'] ?? '#1d4ed8' }};">{{ $pv->titre }}</div>
            <table class="pv-meta" dir="{{ $dir }}">
                <tr>
                    <td style="color:#64748b;padding-inline-end:16px;">{{ __('Généré le') }}</td>
                    <td>{{ $pv->date_generation?->format('d/m/Y à H:i') ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="color:#64748b;padding-inline-end:16px;">{{ __('Généré par') }}</td>
                    <td>{{ $userNames[$pv->created_by] ?? $pv->createur?->name ?? '—' }}</td>
                </tr>
                @if ($pv->signature_deadline)
                    <tr>
                        <td style="color:#64748b;padding-inline-end:16px;">{{ __('Délai de signature') }}</td>
                        <td>{{ $pv->signature_deadline->format('d/m/Y à H:i') }}</td>
                    </tr>
                @endif
            </table>

        @elseif ($id === 'participants')
            @php $participantIds = array_values(array_unique(array_merge(
                $placementsBySection['participants'] ?? [],
                $placementsBySection['signature'] ?? [],
            ))); @endphp
            @if ($participantIds)
                <div class="pv-section" dir="{{ $dir }}" style="direction: {{ $dir }}; unicode-bidi: embed;">
                    <div class="pv-section-title" style="color: {{ $style['titleColor'] ?? '#059669' }}; text-align: {{ $textAlignCss($style, 'left') }};">{{ $section['title'] ?? 'Participants' }}</div>
                    <table class="pv-participants" dir="{{ $dir }}" style="text-align: {{ $textAlignCss($style, 'left') }};">
                        <thead>
                            <tr>
                                <th style="width:30px;">#</th>
                                <th>{{ __('Participant') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($participantIds as $i => $userId)
                                <tr>
                                    <td style="width:30px;">{{ $i + 1 }}</td>
                                    <td>{{ $userNames[$userId] ?? "Utilisateur #{$userId}" }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        @elseif ($id === 'signature')
            <div class="pv-section" dir="{{ $dir }}" style="direction: {{ $dir }}; unicode-bidi: embed;">
                <div class="pv-section-title" style="color: {{ $style['titleColor'] ?? '#64748b' }};">{{ $section['title'] ?? 'Clôture & Signatures' }}</div>
                <table class="pv-signatures" dir="{{ $dir }}">
                    <tbody>
                        @foreach ($pv->validations as $validation)
                            @if (isset($userNames[$validation->user_id]) && $validation->statut === \SalsabilEnnaiem\PvModule\Models\PvValidation::STATUT_VALIDE)
                                @php
                                    $meta = $signatureMeta[$validation->user_id] ?? null;
                                @endphp
                                <tr>
                                    <td style="width:28%;">
                                        <strong>{{ $userNames[$validation->user_id] }}</strong>
                                        <div class="pv-signature-date">{{ $validation->date_reponse?->format('d/m/Y') ?? '' }}</div>
                                        @if ($meta && $meta['mechanism'])
                                            <div class="pv-signature-date">{{ __('Mécanisme de signature') }} : {{ $meta['mechanism'] }}</div>
                                            @if ($meta['signed_at'])
                                                <div class="pv-signature-date">{{ __('Signé le') }} {{ $meta['signed_at']->format('d/m/Y à H:i') }}</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="pv-signature-cell">
                                        @if (!empty($signatures[$validation->user_id]))
                                            <img src="{{ $signatures[$validation->user_id] }}" style="max-height:46px;max-width:150px;">
                                        @else
                                            <span class="pv-muted" style="color:#64748b;">{{ __('Signature enregistrée.') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @elseif (isset($userNames[$validation->user_id]))
                                <tr>
                                    <td style="width:28%;">
                                        {{ $userNames[$validation->user_id] }}
                                        @if ($validation->statut === \SalsabilEnnaiem\PvModule\Models\PvValidation::STATUT_REJETE)
                                            <div class="pv-signature-date" style="color:#dc2626;">{{ __('Rejeté') }}</div>
                                        @elseif ($validation->statut === \SalsabilEnnaiem\PvModule\Models\PvValidation::STATUT_EN_ATTENTE && $validation->user_id !== $pv->created_by)
                                            <div class="pv-signature-date">{{ __('En attente') }}</div>
                                        @endif
                                    </td>
                                    <td class="pv-signature-cell">
                                        <div class="pv-signature-line"></div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

        @else
            <div class="pv-section" dir="{{ $dir }}" style="direction: {{ $dir }}; unicode-bidi: embed;">
                <div class="pv-section-title" style="color: {{ $style['titleColor'] ?? '#334155' }}; text-align: {{ $textAlignCss($style, 'left') }};">{{ $section['title'] ?? $id }}</div>
                @php
                    $content = $pv->contenu[$id] ?? $pv->contenu[$section['title'] ?? ''] ?? null;
                    if ($content === null) {
                        $content = implode("\n", is_array($pv->contenu ?? []) ? array_values($pv->contenu) : []);
                    }
                @endphp
                @if (trim((string) $content) !== '')
                    <div class="pv-body" dir="{{ $dir }}" style="text-align: {{ $textAlignCss($style, 'left') }}; direction: {{ $dir }}; unicode-bidi: embed;">{{ $content }}</div>
                @else
                    <div style="color:#94a3b8;">—</div>
                @endif
            </div>
        @endif
    @endforeach
</div>
</body>
</html>