@extends('pv-module::layouts.app')

@section('content')
    @include('pv-module::partials.breadcrumb', [
        'crumbs' => [
            ['label' => $pv->titre, 'url' => route('pv-module.show', $pv)],
            ['label' => __('Modifier le PV')],
        ],
    ])

    <div class="pvm-page-head">
        <div>
            <h1 class="pvm-title">{{ __('Modifier le PV') }}</h1>
            <p class="pvm-subtitle">{{ $pv->titre }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('pv-module.update', $pv) }}" class="pvm-form">
        @csrf
        @method('PUT')

        <div class="pvm-card">
            <div class="pvm-card-header"><h2>{{ __('Informations') }}</h2></div>

            <label for="titre">{{ __('Titre du PV *') }}</label>
            <input type="text" name="titre" id="titre" value="{{ old('titre', $pv->titre) }}" required>
            @error('titre')<div class="pvm-errors">{{ $message }}</div>@enderror

            <label for="contenu">{{ __('Contenu & décisions') }}</label>
            <textarea name="contenu[contenu]" id="contenu">{{ old('contenu.contenu', $pv->contenu['contenu'] ?? '') }}</textarea>
        </div>

        <div class="pvm-card">
            <div class="pvm-card-header"><h2>{{ __('Participants / signataires') }}</h2></div>

            @php
                $currentReceivers = collect($pv->receivers ?? [])
                    ->pluck('userId')
                    ->filter()
                    ->all();
            @endphp

            @foreach ($users as $user)
                <div class="pvm-checkboxes">
                    <label>
                        <input type="checkbox" name="receiver_placements[]" value="{{ $user->id }}"
                               @checked(in_array($user->id, $currentReceivers))>
                        {{ $user->name }}
                    </label>
                </div>
            @endforeach

            <p class="pvm-help">
                @if (($pv->validations_count ?? $pv->validations()->count()) > 0)
                    {{ __('Des validations existent : modifier les participants créera une copie du PV (versionning).') }}
                @else
                    {{ __('Aucune validation enregistrée : la modification sera directement appliquée.') }}
                @endif
            </p>

            <div style="margin-top:14px;">
                <label for="signature_deadline">{{ __('Délai de signature') }}</label>
                <input type="datetime-local" name="signature_deadline" id="signature_deadline"
                       value="{{ old('signature_deadline', $pv->signature_deadline?->format('Y-m-d\TH:i')) }}">
            </div>
        </div>

        @if ($errors->any())
            <div class="pvm-alert pvm-alert-error">
                <ul style="margin:0;padding-inline-start:20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="pvm-actions-sticky pvm-no-print">
            <a href="{{ route('pv-module.show', $pv) }}" class="pvm-btn pvm-btn-line">{{ __('Retour') }}</a>
            <button type="submit" class="pvm-btn pvm-btn-success" data-pvm-submit>{{ __('Enregistrer') }}</button>
        </div>
    </form>

    @push('scripts')
        <script>
            (function () {
                var form = document.querySelector('form.pvm-form');
                if (!form) return;

                form.addEventListener('submit', function (e) {
                    var submitter = e.submitter;
                    if (!submitter || !submitter.matches('[data-pvm-submit]')) {
                        e.preventDefault();
                        return;
                    }
                    submitter.disabled = true;
                    submitter.classList.add('is-loading');
                    submitter.textContent = '{{ __('Traitement…') }}';
                });
            })();
        </script>
    @endpush
@endsection