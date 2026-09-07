@extends('pv-module::layouts.app')

@section('content')
    @include('pv-module::partials.breadcrumb', [
        'crumbs' => [['label' => __('Modèles de documents')]],
    ])

    <div class="pvm-page-head">
        <div>
            <h1 class="pvm-title">{{ __('Modèles de documents') }}</h1>
            <p class="pvm-subtitle">{{ __('Personnalisez le rendu de vos procès-verbaux (orientation, marges, sections, couleurs).') }}</p>
        </div>
    </div>

    <div class="pvm-card">
        <div class="pvm-card-header"><h2>{{ __('Défaut du système') }}</h2></div>

        <table class="pvm-table">
            <thead>
                <tr>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Orientation') }}</th>
                    <th>{{ __('Statut') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($defaults as $item)
                    <tr>
                        <td><strong>{{ $item['type'] }}</strong></td>
                        <td>{{ $item['template'] ? ($item['template']->orientation === 'landscape' ? __('Paysage') : __('Portrait')) : '—' }}</td>
                        <td>
                            @if ($item['template'])
                                <span class="pvm-badge pvm-badge-brouillon">{{ __('Défaut du système') }}</span>
                            @else
                                <span class="pvm-muted">{{ __('Aucun modèle par défaut pour ce type.') }}</span>
                            @endif
                        </td>
                        <td style="text-align:end;">
                            @if ($item['template'])
                                <form method="POST" action="{{ route('pv-module.templates.create') }}" class="pvm-inline-form">
                                    @csrf
                                    <input type="hidden" name="type" value="{{ $item['type'] }}">
                                    @if ($customs->contains(fn ($c) => $c->type === $item['type']))
                                        <button type="submit" class="pvm-btn pvm-btn-line pvm-btn-sm" disabled>{{ __('Personnaliser') }}</button>
                                    @else
                                        <button type="submit" class="pvm-btn pvm-btn-line pvm-btn-sm">{{ __('Personnaliser') }}</button>
                                    @endif
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="pvm-muted">—</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pvm-card">
        <div class="pvm-card-header"><h2>{{ __('Mon modèle personnalisé') }}</h2></div>

        @if ($customs->isEmpty())
            <div class="pvm-empty">{{ __('Aucun modèle personnalisé. Cliquez sur « Personnaliser » pour adapter le modèle par défaut.') }}</div>
        @else
            <table class="pvm-table">
                <thead>
                    <tr>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Orientation') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customs as $custom)
                        <tr>
                            <td><strong>{{ $custom->type }}</strong></td>
                            <td>{{ $custom->orientation === 'landscape' ? __('Paysage') : __('Portrait') }}</td>
                            <td>
                                <div class="pvm-actions" style="margin-top:0;">
                                    <a href="{{ route('pv-module.templates.edit', $custom) }}" class="pvm-btn pvm-btn-sm">{{ __('Modifier') }}</a>
                                    <form method="POST" action="{{ route('pv-module.templates.reset') }}" class="pvm-inline-form">
                                        @csrf
                                        <input type="hidden" name="type" value="{{ $custom->type }}">
                                        <button type="submit" class="pvm-btn pvm-btn-line pvm-btn-sm">{{ __('Repartir du modèle par défaut') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('pv-module.templates.destroy', $custom) }}"
                                          onsubmit="return confirm('{{ __('Supprimer ce modèle ?') }}');" class="pvm-inline-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="pvm-btn pvm-btn-danger-line pvm-btn-sm">{{ __('Supprimer') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection