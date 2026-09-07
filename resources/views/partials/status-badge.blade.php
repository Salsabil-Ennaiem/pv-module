{{-- Badge de statut PV : $statut, $label (optionnel) --}}
@php
    $label = $label ?? match ($statut) {
        'brouillon' => __('Brouillon'),
        'en_attente' => __('En attente'),
        'valide' => __('Validé'),
        'rejete' => __('Rejeté'),
        default => (string) $statut,
    };
@endphp
<span class="pvm-badge pvm-badge-{{ $statut }}">{{ $label }}</span>