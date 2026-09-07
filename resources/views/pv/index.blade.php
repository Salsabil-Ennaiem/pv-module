@extends('pv-module::layouts.app')

@section('content')
    <div class="pvm-page-head">
        <div>
            <h1 class="pvm-title">{{ __('Mes procès-verbaux') }}</h1>
            <p class="pvm-subtitle">{{ __('Gérez vos procès-verbaux et le cycle de validation des signataires.') }}</p>
        </div>
        <div class="pvm-actions" style="margin-top:0;">
            <a href="{{ route('pv-module.templates.index') }}" class="pvm-btn pvm-btn-line">{{ __('Modèles de documents') }}</a>
            <a href="{{ route('pv-module.create') }}" class="pvm-btn">+ {{ __('Nouveau PV') }}</a>
        </div>
    </div>

    <nav class="pvm-tabs pvm-no-print" aria-label="Statuts">
        @php
            $statutOptions = ['tout' => __('Tout')] + $statuts;
        @endphp
        @foreach ($statutOptions as $cle => $tabLabel)
            <a href="{{ request()->fullUrlWithQuery(array_filter(['statut' => $cle === 'tout' ? null : $cle, 'page' => null], fn ($v) => $v !== null)) }}"
               @class(['is-active' => ($filters['statut'] ?? '_aucun') === ($cle === 'tout' ? null : $cle) || (empty($filters['statut']) && $cle === 'tout')])>
                {{ $tabLabel }}
                <span class="pvm-tab-count">{{ $comptesParStatut[$cle] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>

    <div class="pvm-card pvm-no-print" style="padding-top:16px;padding-bottom:16px;">
        <form method="GET" action="{{ route('pv-module.index') }}" class="pvm-form" id="pvm-filter-form">
            <div class="pvm-field-grid" style="grid-template-columns:2fr 1fr 1fr 1fr 1fr;align-items:end;">
                <div class="pvm-field">
                    <label for="q">{{ __('Rechercher un PV…') }}</label>
                    <input type="text" name="q" id="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Titre, référence, contenu…') }}" data-pvm-auto>
                </div>
                <div class="pvm-field">
                    <label for="fdep">{{ __('Du') }}</label>
                    <input type="date" name="from" id="fdep" value="{{ $filters['from'] ?? '' }}" data-pvm-auto>
                </div>
                <div class="pvm-field">
                    <label for="ffin">{{ __('Au') }}</label>
                    <input type="date" name="to" id="ffin" value="{{ $filters['to'] ?? '' }}" data-pvm-auto>
                </div>
                <div class="pvm-field">
                    <label for="tri">{{ __('Trier par') }}</label>
                    <select name="sort" id="tri" data-pvm-auto>
                        <option value="latest" @selected(($filters['sort'] ?? 'latest') === 'latest')>{{ __('Plus récents') }}</option>
                        <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>{{ __('Plus anciens') }}</option>
                        <option value="titre" @selected(($filters['sort'] ?? '') === 'titre')>{{ __('Titre (A→Z)') }}</option>
                    </select>
                </div>
                <div class="pvm-field">
                    <label>&nbsp;</label>
                    <div class="pvm-actions" style="margin-top:0;">
                        <button class="pvm-btn pvm-btn-line" type="button" id="pvm-filter-clear">{{ __('Réinitialiser') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @if ($pvs->isEmpty())
        @if (! $filters)
            <div class="pvm-empty">
                <div style="font-size:16px;margin-bottom:10px;">{{ __('Aucun PV pour le moment. Créez votre premier PV pour démarrer le workflow.') }}</div>
                <a href="{{ route('pv-module.create') }}" class="pvm-btn">+ {{ __('Nouveau PV') }}</a>
            </div>
        @else
            <div class="pvm-empty">{{ __('Aucun résultat pour ces filtres.') }}</div>
        @endif
    @else
        @php
            $actor = auth()->user();
            $rules = app(\SalsabilEnnaiem\PvModule\Contracts\CanManagePv::class);
            $allCols = ['titre', 'statut', 'deadline', 'date', 'validations'];
        @endphp
        <div class="pvm-card" style="padding:0;overflow:hidden;">
            <div class="pvm-table-wrap" style="border:0;border-radius:0;">
                <table class="pvm-table" id="pvm-pv-table">
                    <thead>
                        <tr>
                            <th data-col="titre" style="min-width:220px;">{{ __('Titre') }}</th>
                            <th data-col="statut">{{ __('Statut') }}</th>
                            <th data-col="deadline">{{ __('Délai de signature') }}</th>
                            <th data-col="date">{{ __('Date de génération') }}</th>
                            <th data-col="validations" style="min-width:150px;">{{ __('Validations') }}</th>
                            <th style="text-align:end;white-space:nowrap;">
                                <span style="display:inline-flex;align-items:center;gap:8px;">
                                    {{ __('Colonnes') }}
                                    <span class="pvm-hamburger-wrap" data-pvm-hamburger>
                                        <button type="button" class="pvm-hamburger" data-pvm-hamburger-btn aria-label="{{ __('Colonnes à afficher') }}" title="{{ __('Colonnes à afficher') }}">&#9776;</button>
                                        <div class="pvm-hamburger-menu">
                                            <div class="pvm-hamburger-menu-title">{{ __('Colonnes à afficher') }}</div>
                                            @foreach (['titre' => __('Titre'), 'statut' => __('Statut'), 'deadline' => __('Délai de signature'), 'date' => __('Date de génération'), 'validations' => __('Validations')] as $col => $colLabel)
                                                <label class="pvm-hamburger-item">
                                                    <input type="checkbox" class="pvm-col-toggle" value="{{ $col }}" data-col-key="{{ $col }}" @checked(in_array($col, $allCols))>
                                                    {{ $colLabel }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </span>
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pvs as $pv)
                            <tr>
                                <td data-col="titre">
                                    <a href="{{ route('pv-module.show', $pv) }}" style="font-weight:600;text-decoration:none;">{{ $pv->titre }}</a>
                                    @if ($pv->source_type)
                                        <div class="pvm-muted">{{ $pv->source_type }} #{{ $pv->source_id }}</div>
                                    @endif
                                    @if ($pv->deadlineIsPast())
                                        <div><span class="pvm-badge pvm-badge-rejete">{{ __('délai dépassé') }}</span></div>
                                    @endif
                                </td>
                                <td data-col="statut">
                                    @include('pv-module::partials.status-badge', ['statut' => $pv->statut])
                                </td>
                                <td data-col="deadline">{{ $pv->signature_deadline?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td data-col="date">{{ $pv->created_at?->format('d/m/Y H:i') }}</td>
                                <td data-col="validations">
                                    @php
                                        $total = $pv->validations_count ?? 0;
                                        $ok = $pv->validations_validees_count ?? 0;
                                        $rej = $pv->validations_rejetees_count ?? 0;
                                        $pct = $total > 0 ? round($ok / $total * 100) : 0;
                                    @endphp
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div class="pvm-progress @if($total > 0 && $ok === $total) complete @endif" style="flex:1;max-width:80px;">
                                            <span style="width:{{ $pct }}%;"></span>
                                        </div>
                                        <span class="pvm-soft" style="font-size:13px;white-space:nowrap;">{{ $ok }}/{{ $total }}</span>
                                        @if ($rej > 0 && $pv->statut !== \SalsabilEnnaiem\PvModule\Models\Pv::STATUT_VALIDE)
                                            <span class="pvm-badge pvm-badge-rejete" title="{{ $rej }} rejet">{{ $rej }}✕</span>
                                        @endif
                                    </div>
                                </td>
                                <td style="text-align:end;white-space:nowrap;">
                                    <div class="pvm-row-dial" data-pvm-speed-dial>
                                        <button type="button" class="pvm-row-dial-main" data-pvm-speed-main aria-label="{{ __('Actions') }}" title="{{ __('Actions') }}">&#8942;</button>
                                        <div class="pvm-row-dial-list">
                                            <a href="{{ route('pv-module.show', $pv) }}" class="pvm-row-dial-item">{{ __('Voir') }}</a>
                                            @if ($rules->canUpdate($actor, $pv) && $pv->statut !== \SalsabilEnnaiem\PvModule\Models\Pv::STATUT_VALIDE)
                                                <a href="{{ route('pv-module.edit', $pv) }}" class="pvm-row-dial-item">{{ __('Modifier') }}</a>
                                            @endif
                                            <a href="{{ route('pv-module.pdf', $pv) }}" class="pvm-row-dial-item">{{ __('PDF') }}</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($pvs->hasPages())
                <div style="padding:14px 16px;border-top:1px solid var(--pvm-border);">
                    {{ $pvs->links() }}
                </div>
            @endif
        </div>
    @endif
@endsection

@push('styles')
    <style>
        .pvm-badge-late { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .pvm-table-wrap { transition: opacity .25s ease; }
        .pvm-table-wrap.is-loading { opacity: .45; pointer-events: none; }
        .pvm-col-hidden { display: none !important; }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            var form = document.getElementById('pvm-filter-form');
            var clear = document.getElementById('pvm-filter-clear');
            if (!form) return;

            var timer = null;

            function submit() {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    var wrap = document.querySelector('.pvm-table-wrap');
                    if (wrap) wrap.classList.add('is-loading');
                    form.submit();
                }, 350);
            }

            form.querySelectorAll('[data-pvm-auto]').forEach(function (ctrl) {
                ctrl.addEventListener('input', submit);
                ctrl.addEventListener('change', submit);
                ctrl.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') { e.preventDefault(); submit(); }
                });
            });

            if (clear) {
                clear.addEventListener('click', function () {
                    window.location.href = '{{ route('pv-module.index') }}';
                });
            }

            /* ----- Choix des colonnes (persisté en localStorage) ----- */
            var KEY = 'pvm_columns';
            var table = document.getElementById('pvm-pv-table');
            var toggles = document.querySelectorAll('.pvm-col-toggle');

            function savedColumns() {
                var raw = null;
                try { raw = JSON.parse(localStorage.getItem(KEY) || 'null'); } catch (e) { raw = null; }
                return Array.isArray(raw) ? raw : null;
            }

            function applyColumns() {
                var cols = savedColumns();
                if (!cols || !table) return;
                [].forEach.call(table.querySelectorAll('th[data-col], td[data-col]'), function (cell) {
                    cell.classList.toggle('pvm-col-hidden', cols.indexOf(cell.dataset.col) === -1);
                });
            }

            function persist() {
                var cols = [].filter.call(toggles, function (t) { return t.checked; }).map(function (t) { return t.value; });
                try { localStorage.setItem(KEY, JSON.stringify(cols)); } catch (e) {}
                applyColumns();
            }

            applyColumns();

            if (toggles.length) {
                toggles.forEach(function (t) {
                    var saved = savedColumns();
                    if (saved) { t.checked = saved.indexOf(t.value) !== -1; }
                    t.addEventListener('change', persist);
                });
            }
        })();
    </script>
@endpush