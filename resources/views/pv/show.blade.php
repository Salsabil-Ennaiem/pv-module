@extends('pv-module::layouts.app')

@section('content')
    @php
        $actor = auth()->user();
        $rules = app(\SalsabilEnnaiem\PvModule\Contracts\CanManagePv::class);
        $isOwner = $actor && $pv->created_by === $actor->getKey();
        $actorHasSignature = $actor ? app(\SalsabilEnnaiem\PvModule\Services\SignatureService::class)->hasSignature($actor) : false;
        $pendingForMe = $pv->validationsEnAttente()
            ->where('user_id', $actor?->getKey())
            ->exists();
        $deadlinePassed = $pv->deadlineIsPast();
        $totalV = $totalOk = $totalRej = 0;
        foreach ($pv->validations as $v) {
            $totalV++;
            if ($v->statut === \SalsabilEnnaiem\PvModule\Models\PvValidation::STATUT_VALIDE) $totalOk++;
            if ($v->statut === \SalsabilEnnaiem\PvModule\Models\PvValidation::STATUT_REJETE) $totalRej++;
        }
        $pct = $totalV > 0 ? round($totalOk / $totalV * 100) : 0;
        $allOk = $totalV > 0 && $totalOk === $totalV;
    @endphp

    @include('pv-module::partials.breadcrumb', [
        'crumbs' => [['label' => $pv->titre]],
    ])

    <div class="pvm-page-head">
        <div>
            <h1 class="pvm-title">
                {{ $pv->titre }}
                @include('pv-module::partials.status-badge', ['statut' => $pv->statut])
            </h1>
            <p class="pvm-subtitle">
                {{ __('Généré par') }} {{ $pv->createur?->name ?? '—' }} · {{ __('Généré le') }} {{ $pv->created_at?->format('d/m/Y à H:i') }}
                @if ($pv->source_type)
                    · {{ __('Source') }} : {{ $pv->source_type }} #{{ $pv->source_id }}
                @endif
            </p>
            <p class="pvm-subtitle" style="margin-top:2px;">
                {{ __('Type') }} : {{ $pv->type }}
                @if ($pv->signature_deadline)
                    · {{ __('Délai de signature') }} : {{ $pv->signature_deadline->format('d/m/Y à H:i') }}
                    @if ($deadlinePassed)<span class="pvm-badge pvm-badge-rejete">{{ __('délai dépassé') }}</span>@endif
                @else
                    · {{ __('Pas de délai de signature') }}
                @endif
            </p>
        </div>

        <div class="pvm-speed-dial pvm-no-print" data-pvm-speed-dial>
            <button type="button" class="pvm-speed-dial-main" data-pvm-speed-main aria-label="{{ __('Actions') }}" title="{{ __('Actions') }}">+</button>
            <div class="pvm-speed-list">
                @if ($rules->canUpdate($actor, $pv) && $pv->statut !== \SalsabilEnnaiem\PvModule\Models\Pv::STATUT_VALIDE)
                    <a href="{{ route('pv-module.edit', $pv) }}" class="pvm-speed-item">{{ __('Modifier') }}</a>
                @endif
                <a href="{{ route('pv-module.preview', $pv) }}" class="pvm-speed-item">{{ __('Aperçu') }}</a>
                <a href="{{ route('pv-module.pdf', $pv) }}" class="pvm-speed-item">{{ __('PDF') }}</a>
                <a href="{{ route('pv-module.versions', $pv) }}" class="pvm-speed-item">{{ __('Versions') }} ({{ count($pv->versions ?? []) }})</a>
            </div>
        </div>
    </div>

    @if ($pendingForMe)
        <div class="pvm-alert pvm-alert-info">
            <strong>{{ __('Action requise :') }}</strong> {{ __('vous devez valider et signer ce PV.') }}
            @if (! $actorHasSignature)
                <p class="pvm-action-hint">&#8592; {{ __('Pour signer, vous devez d\'abord enregistrer votre signature') }}
                    <a href="{{ route('pv-module.signature.index') }}">{{ __('Ma signature') }} &rarr;</a>
                </p>
            @endif
        </div>
    @endif

    @if ($errors->any())
        <div class="pvm-alert pvm-alert-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if ($isOwner && $pv->statut !== \SalsabilEnnaiem\PvModule\Models\Pv::STATUT_VALIDE && $rules->canSend($actor, $pv))
        <div class="pvm-card pvm-no-print">
            <div class="pvm-card-header">
                <h2>@if ($pv->statut === 'brouillon') {{ __('Envoyer aux participants') }} @else {{ __('Réenvoyer / changer les signataires') }} @endif</h2>
            </div>
            <form method="POST" action="{{ route('pv-module.send', $pv) }}" class="pvm-form">
                @csrf
                @php
                    $all = app((string) config('pv-module.user_model', \App\Models\User::class))::query()
                        ->get()
                        ->sortBy(fn ($u) => mb_strtolower((string) $u->name))
                        ->values();
                    $placedIds = collect($pv->receivers ?? [])
                        ->map(fn ($r) => is_array($r) ? (int) ($r['userId'] ?? 0) : (int) $r)
                        ->filter();
                @endphp
                <label>{{ __('Destinataires') }}</label>
                <div class="pvm-checkboxes">
                    @foreach ($all as $user)
                        <label>
                            <input type="checkbox" name="receivers[]" value="{{ $user->id }}" @checked($placedIds->contains((int) $user->id))>
                            {{ $user->name }}
                        </label>
                    @endforeach
                </div>
                <label for="signature_deadline">{{ __('Délai de signature') }}</label>
                <input type="datetime-local" name="signature_deadline" id="signature_deadline"
                       value="{{ $pv->signature_deadline?->format('Y-m-d\TH:i') }}">
                <label style="font-weight:400;display:flex;gap:6px;align-items:center;margin-top:10px;">
                    <input type="checkbox" name="update_deadline" value="1">
                    {{ __('Mettre à jour le délai avec la valeur saisie') }}
                </label>
                <div class="pvm-actions">
                    <button type="submit" class="pvm-btn pvm-btn-success">{{ __('Envoyer') }}</button>
                </div>
            </form>
        </div>
    @endif

    @if ($pendingForMe)
        <div class="pvm-card pvm-no-print">
            <div class="pvm-card-header"><h2>{{ __('Valider ce PV') }}</h2></div>
            @if ($deadlinePassed && $pv->signature_deadline)
                <p class="pvm-alert pvm-alert-error">{{ __('Le délai de signature est dépassé.') }}</p>
            @else
                <div class="pvm-grid" style="grid-template-columns:repeat(auto-fit,minmax(260px,1fr));">
                    <form method="POST" action="{{ route('pv-module.validate', $pv) }}" class="pvm-form" style="background:var(--pvm-surface-alt);border:1px solid var(--pvm-border);border-radius:var(--pvm-radius-sm);padding:14px;">
                        @csrf
                        <h3 class="pvm-card-title" style="font-size:14px;">{{ __('Valider et signer') }}</h3>
                        <label for="commentaire_validate">{{ __('Commentaire (optionnel)') }}</label>
                        <textarea name="commentaire" id="commentaire_validate" style="min-height:72px;"></textarea>
                        <div class="pvm-actions">
                            <button type="submit" class="pvm-btn pvm-btn-success">{{ __('Valider et signer') }}</button>
                        </div>
                    </form>
                    <div style="display:flex;flex-direction:column;gap:14px;">
                        <form method="POST" action="{{ route('pv-module.sign', $pv) }}" class="pvm-form" style="background:var(--pvm-surface-alt);border:1px solid var(--pvm-border);border-radius:var(--pvm-radius-sm);padding:14px;">
                            @csrf
                            <h3 class="pvm-card-title" style="font-size:14px;">{{ __('Signer uniquement') }}</h3>
                            <div class="pvm-help">{{ __('Apposez votre signature sans commentaire.') }}</div>
                            <div class="pvm-actions">
                                <button type="submit" class="pvm-btn">{{ __('Signer uniquement') }}</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('pv-module.reject', $pv) }}" class="pvm-form" style="background:var(--pvm-danger-soft);border:1px solid #fecaca;border-radius:var(--pvm-radius-sm);padding:14px;">
                            @csrf
                            <h3 class="pvm-card-title" style="font-size:14px;color:var(--pvm-danger-strong);">{{ __('Rejeter') }}</h3>
                            <label for="commentaire_reject">{{ __('Motif du rejet *') }}</label>
                            <textarea name="commentaire" id="commentaire_reject" style="min-height:72px;" required></textarea>
                            <div class="pvm-actions">
                                <button type="submit" class="pvm-btn pvm-btn-danger">{{ __('Rejeter') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if ($totalV > 0)
        <div class="pvm-card">
            <div class="pvm-card-header">
                <h2>{{ __('Progression des signatures') }}</h2>
                <span class="pvm-soft">{{ $totalOk }}/{{ $totalV }} {{ $totalRej ? '· '.$totalRej.' '.__('Refus') : '' }}</span>
            </div>
            <div class="pvm-progress @if($allOk) complete @endif">
                <span style="width:{{ $pct }}%;"></span>
            </div>
            <p class="pvm-help" style="margin-top:10px;margin-bottom:0;">{{ __('Les signatures manquantes sont indiquées en rouge.') }}</p>
        </div>
    @endif

    <div class="pvm-card">
        <div class="pvm-card-header"><h2>{{ __('Historique du PV') }}</h2></div>
        @php
            $validations = $pv->validations->sortByDesc('date_reponse');
        @endphp
        @if ($validations->count() === 0)
            <p class="pvm-muted">{{ __('Aucune validation à afficher.') }}</p>
        @else
            <ul class="pvm-timeline">
                @foreach ($validations as $validation)
                    @php
                        $dotClass = match ($validation->statut) {
                            'valide' => 'ok',
                            'rejete' => 'no',
                            default => 'wait',
                        };
                        $statusLabel = match ($validation->statut) {
                            'valide' => __('Validé'),
                            'rejete' => __('Rejeté'),
                            default => __('En attente'),
                        };
                    @endphp
                    <li>
                        <span class="pvm-timeline-dot {{ $dotClass }}"></span>
                        <div style="display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;">
                            <strong>{{ $validation->user?->name ?? __('Utilisateur')." #{$validation->user_id}" }}</strong>
                            @if ($validation->user_id === $pv->created_by)
                                <span class="pvm-muted">{{ __('(créateur)') }}</span>
                            @endif
                            <span class="pvm-badge pvm-badge-{{ $validation->statut }}">{{ $statusLabel }}</span>
                            <span class="pvm-muted">{{ $validation->date_reponse?->format('d/m/Y H:i') ?? '' }}</span>
                        </div>
                        @if ($validation->commentaire)
                            <div class="pvm-soft" style="margin-top:4px;">{{ $validation->commentaire }}</div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @if ($isOwner && $rules->canDelete($actor, $pv))
        <div class="pvm-card pvm-no-print">
            <form method="POST" action="{{ route('pv-module.destroy', $pv) }}"
                  onsubmit="return confirm('{{ __('Supprimer définitivement ce PV ?') }}');">
                @csrf
                @method('DELETE')
                <button type="submit" class="pvm-btn pvm-btn-danger">{{ __('Supprimer le PV') }}</button>
            </form>
        </div>
    @endif
@endsection