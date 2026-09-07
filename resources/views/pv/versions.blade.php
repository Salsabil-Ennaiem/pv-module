@extends('pv-module::layouts.app')

@section('content')
    @include('pv-module::partials.breadcrumb', [
        'crumbs' => [
            ['label' => $pv->titre, 'url' => route('pv-module.show', $pv)],
            ['label' => __('Historique des versions')],
        ],
    ])

    <div class="pvm-page-head">
        <div>
            <h1 class="pvm-title">
                {{ __('Historique des versions') }}
                @include('pv-module::partials.status-badge', ['statut' => $pv->statut])
            </h1>
            <p class="pvm-subtitle">{{ $pv->titre }}</p>
            <p class="pvm-help">{{ __('Le statut affiché correspond à l\'état du PV au moment de l\'enregistrement de la version.') }}</p>
        </div>
        <a href="{{ route('pv-module.show', $pv) }}" class="pvm-btn pvm-btn-line">&larr; {{ __('Retour au PV') }}</a>
    </div>

    <div class="pvm-card" style="padding:0;overflow:hidden;">
        @php
            $versions = array_reverse($pv->versions ?? []);
            $versionCount = count($versions);
        @endphp
        @if ($versions === [])
            <div class="pvm-empty" style="border:0;border-radius:0;">{{ __('Aucune version enregistrée.') }}</div>
        @else
            <div class="pvm-table-wrap" style="border:0;border-radius:0;">
                <table class="pvm-table">
                    <thead>
                        <tr>
                            <th>{{ __('Version') }}</th>
                            <th>{{ __('Titre') }}</th>
                            <th>{{ __('Statut') }}</th>
                            <th>{{ __('Enregistrée le') }}</th>
                            <th>{{ __('Rubriques') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($versions as $index => $version)
                            <tr>
                                <td><strong>v{{ $version['version'] }}</strong></td>
                                <td>{{ $version['titre'] }}</td>
                                <td>
                                    @include('pv-module::partials.status-badge', [
                                        'statut' => $index === 0 ? $pv->statut : ($version['statut'] ?? 'brouillon'),
                                        'label' => $index === 0
                                            ? __('Statut actuel : '.match ($pv->statut) {
                                                'en_attente' => 'En attente',
                                                'valide' => 'Validé',
                                                'rejete' => 'Rejeté',
                                                default => 'Brouillon',
                                            })
                                            : null,
                                    ])
                                </td>
                                <td>{{ isset($version['saved_at']) ? \Illuminate\Support\Carbon::parse($version['saved_at'])->format('d/m/Y H:i') : '—' }}</td>
                                <td>
                                    @php
                                        $nbRubriques = is_array($version['contenu'] ?? null)
                                            ? count(array_filter($version['contenu'], fn ($v) => $v !== null && $v !== ''))
                                            : ($version['contenu'] ? 1 : 0);
                                    @endphp
                                    {{ $nbRubriques }} {{ $nbRubriques > 1 ? __('rubriques') : __('rubrique') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection