{{-- Fil d'Ariane : $crumbs = [['label' => '...', 'url' => '...'], ...] (dernier sans url) --}}
<nav class="pvm-breadcrumb" aria-label="Fil d'Ariane">
    <a href="{{ route('pv-module.index') }}">{{ __('Mes PV') }}</a>
    @foreach ($crumbs ?? [] as $crumb)
        <span class="pvm-breadcrumb-sep">/</span>
        @if (!empty($crumb['url']))
            <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
        @else
            <span>{{ $crumb['label'] }}</span>
        @endif
    @endforeach
</nav>