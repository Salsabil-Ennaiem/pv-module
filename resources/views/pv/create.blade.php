@extends('pv-module::layouts.app')

@section('content')
    @include('pv-module::partials.breadcrumb', [
        'crumbs' => [['label' => __('Nouveau procès-verbal')]],
    ])

    @if (!$hasSignature)
        <div class="pvm-alert pvm-alert-warning">
            <strong>{{ __('Signature requise :') }}</strong> {{ __('pour « Enregistrer et envoyer », vous devez d\'abord enregistrer votre signature.') }}
            <a href="{{ route('pv-module.signature.index') }}" class="pvm-btn pvm-btn-line pvm-btn-sm" style="margin-inline-start:8px;">{{ __('Enregistrer ma signature') }}</a>
        </div>
    @endif

    <div class="pvm-page-head">
        <div>
            <h1 class="pvm-title">{{ __('Nouveau procès-verbal') }}</h1>
            <p class="pvm-subtitle">{{ __('Renseignez le contenu et sélectionnez les signataires, puis enregistrez en brouillon ou envoyez directement.') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('pv-module.store') }}" class="pvm-form" id="pv-create-form">
        @csrf

        <div class="pvm-card">
            <div class="pvm-card-header"><h2>{{ __('Informations') }}</h2></div>

            <label for="titre">{{ __('Titre du PV *') }}</label>
            <input type="text" name="titre" id="titre" value="{{ old('titre') }}"
                   placeholder="{{ __('Ex : PV de la réunion de la commission informatique') }}" required>
            @error('titre')<div class="pvm-errors">{{ $message }}</div>@enderror

            @if (count($types) > 1)
                <label for="type">{{ __('Type') }}</label>
                <select name="type" id="type">
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected(old('type', 'pv') === $type)>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            @else
                <input type="hidden" name="type" value="{{ $types[0] ?? 'pv' }}">
            @endif
        </div>

        <div class="pvm-card">
            <div class="pvm-card-header"><h2>{{ __('Contenu & décisions') }}</h2></div>

            <label for="contenu">{{ __('Contenu & décisions') }}</label>
            <textarea name="contenu[contenu]" id="contenu" placeholder="{{ $template ? __('Rédigez ici le contenu du PV et les décisions prises.') : '' }}">{{ old('contenu.contenu') }}</textarea>
            @error('contenu')<div class="pvm-errors">{{ $message }}</div>@enderror
        </div>

        <div class="pvm-card">
            <div class="pvm-card-header"><h2>{{ __('Participants / signataires') }}</h2></div>

            @foreach ($users as $user)
                <div class="pvm-checkboxes">
                    <label>
                        <input type="checkbox" name="receivers[]" value="{{ $user->id }}"
                               @checked(in_array($user->id, old('receivers', [])) || (empty(old('receivers')) && $user->id === auth()->id()))>
                        {{ $user->name }}
                    </label>
                </div>
            @endforeach
            <p class="pvm-help">{{ __('Décochez le créateur pour ne pas l\'inclure comme signataire.') }}</p>

            <div style="margin-top:14px;">
                <label for="signature_deadline">{{ __('Délai de signature') }}</label>
                <input type="datetime-local" name="signature_deadline" id="signature_deadline"
                       value="{{ old('signature_deadline') }}">
                @error('signature_deadline')<div class="pvm-errors">{{ $message }}</div>@enderror
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
            <a href="{{ route('pv-module.index') }}" class="pvm-btn pvm-btn-line" data-pvm-cancel>{{ __('Annuler') }}</a>
            <button type="submit" class="pvm-btn" data-pvm-submit>{{ __('Enregistrer le brouillon') }}</button>
            <button type="submit" class="pvm-btn pvm-btn-success" formaction="{{ route('pv-module.store-and-send') }}" data-pvm-submit>
                {{ __('Enregistrer et envoyer aux participants') }}
            </button>
        </div>
    </form>

    @push('scripts')
        <script>
            (function () {
                var form = document.getElementById('pv-create-form');
                if (!form) return;

                form.addEventListener('submit', function (e) {
                    var submitter = e.submitter;
                    if (!submitter || !submitter.matches('[data-pvm-submit]')) {
                        e.preventDefault();
                        return;
                    }
                    var stamps = form.querySelectorAll('[data-pvm-submit]');
                    stamps.forEach(function (btn) {
                        btn.disabled = true;
                        btn.classList.add('is-loading');
                        btn.dataset.original = btn.dataset.original || btn.textContent;
                        btn.textContent = '{{ __('Traitement…') }}';
                    });
                });
            })();
        </script>
    @endpush
@endsection