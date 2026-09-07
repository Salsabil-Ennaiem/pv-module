@extends('pv-module::layouts.app')

@section('content')
    @include('pv-module::partials.breadcrumb', [
        'crumbs' => [
            ['label' => $payload['pv']->titre, 'url' => route('pv-module.show', $payload['pv'])],
            ['label' => __('Aperçu du PV')],
        ],
    ])

    <div class="pvm-card pvm-no-print" style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
        <a href="{{ route('pv-module.show', $payload['pv']) }}" class="pvm-btn pvm-btn-secondary">&larr; {{ __('Retour') }}</a>
        <a href="{{ route('pv-module.pdf', $payload['pv']) }}" class="pvm-btn pvm-btn-success">{{ __('Télécharger le PDF') }}</a>
    </div>

    <div class="pvm-card" style="padding:0;overflow:hidden;">
        <div style="background:#fff;padding:48px 56px;">
            @include('pv-module::pdfs.pv_template', $payload)
        </div>
    </div>
@endsection