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
@endphp
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 11px; line-height: 1.5; }
        .pv-sheet { width: 100%; }
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
        .pv-body { white-space: pre-line; }
        .pv-participants { width: 100%; border-collapse: collapse; }
        .pv-participants th, .pv-participants td {
            text-align: left; border: 1px solid #cbd5e1; padding: 5px 8px; font-size: 11px;
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
<div class="pv-sheet">
    @foreach ($sections as $section)
        @php
            $style = array_merge($sectionStyles, $section['styles'] ?? []);
            $id = $section['id'] ?? '';
        @endphp

        @if ($id === 'header')
            <div class="pv-title" style="color: {{ $style['titleColor'] ?? '#1d4ed8' }};">{{ $pv->titre }}</div>
            <table class="pv-meta">
                <tr>
                    <td style="color:#64748b;padding-right:16px;">{{ __('Généré le') }}</td>
                    <td>{{ $pv->date_generation?->format('d/m/Y à H:i') ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="color:#64748b;padding-right:16px;">{{ __('Généré par') }}</td>
                    <td>{{ $userNames[$pv->created_by] ?? $pv->createur?->name ?? '—' }}</td>
                </tr>
                @if ($pv->signature_deadline)
                    <tr>
                        <td style="color:#64748b;padding-right:16px;">{{ __('Délai de signature') }}</td>
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
                <div class="pv-section">
                    <div class="pv-section-title" style="color: {{ $style['titleColor'] ?? '#059669' }}; text-align: {{ $textAlignCss($style, 'left') }};">{{ $section['title'] ?? 'Participants' }}</div>
                    <table class="pv-participants" style="text-align: {{ $textAlignCss($style, 'left') }};">
                        <thead>
                            <tr>
                                <th style="width:30px; text-align: {{ $textAlignCss($style, 'left') }};">#</th>
                                <th style="text-align: {{ $textAlignCss($style, 'left') }};">{{ __('Participant') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($participantIds as $i => $userId)
                                <tr>
                                    <td style="width:30px; text-align: {{ $textAlignCss($style, 'left') }};">{{ $i + 1 }}</td>
                                    <td style="text-align: {{ $textAlignCss($style, 'left') }};">{{ $userNames[$userId] ?? "Utilisateur #{$userId}" }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        @elseif ($id === 'signature')
            <div class="pv-section">
                <div class="pv-section-title" style="color: {{ $style['titleColor'] ?? '#64748b' }};">{{ $section['title'] ?? 'Clôture & Signatures' }}</div>
                <table class="pv-signatures">
                    <tbody>
                        @foreach ($pv->validations as $validation)
                            @if (isset($userNames[$validation->user_id]) && $validation->statut === \SalsabilEnnaiem\PvModule\Models\PvValidation::STATUT_VALIDE)
                                <tr>
                                    <td style="width:28%;">
                                        <strong>{{ $userNames[$validation->user_id] }}</strong>
                                        <div class="pv-signature-date">{{ $validation->date_reponse?->format('d/m/Y') ?? '' }}</div>
                                    </td>
                                    <td class="pv-signature-cell">
                                        @if (!empty($signatures[$validation->user_id]))
                                            <img src="{{ $signatures[$validation->user_id] }}" style="max-height:46px;max-width:150px;">
                                        @else
                                            <span class="pv-muted" style="color:#64748b;">signature enregistrée</span>
                                        @endif
                                    </td>
                                </tr>
                            @elseif (isset($userNames[$validation->user_id]))
                                <tr>
                                    <td style="width:28%;">
                                        {{ $userNames[$validation->user_id] }}
                                        @if ($validation->statut === \SalsabilEnnaiem\PvModule\Models\PvValidation::STATUT_REJETE)
                                            <div class="pv-signature-date" style="color:#dc2626;">rejeté</div>
                                        @elseif ($validation->statut === \SalsabilEnnaiem\PvModule\Models\PvValidation::STATUT_EN_ATTENTE && $validation->user_id !== $pv->created_by)
                                            <div class="pv-signature-date">en attente</div>
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
            <div class="pv-section">
                <div class="pv-section-title" style="color: {{ $style['titleColor'] ?? '#334155' }}; text-align: {{ $textAlignCss($style, 'left') }};">{{ $section['title'] ?? $id }}</div>
                @php
                    $content = $pv->contenu[$id] ?? $pv->contenu[$section['title'] ?? ''] ?? null;
                    if ($content === null) {
                        $content = implode("\n", is_array($pv->contenu ?? []) ? array_values($pv->contenu) : []);
                    }
                @endphp
                @if (trim((string) $content) !== '')
                    <div class="pv-body" style="text-align: {{ $textAlignCss($style, 'left') }};">{{ $content }}</div>
                @else
                    <div style="color:#94a3b8;">—</div>
                @endif
            </div>
        @endif
    @endforeach
</div>
</body>
</html>