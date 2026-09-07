@extends('pv-module::layouts.app')

@section('content')
    @include('pv-module::partials.breadcrumb', [
        'crumbs' => [['label' => __('Notifications')]],
    ])

    <div class="pvm-page-head">
        <div>
            <h1 class="pvm-title">{{ __('Notifications') }}</h1>
            <p class="pvm-subtitle">{{ __('Suivez les demandes de signature et les validations de vos PV.') }}</p>
        </div>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('pv-module.notifications.read-all') }}">
                @csrf
                <button type="submit" class="pvm-btn pvm-btn-line">{{ __('Tout marquer comme lu') }}</button>
            </form>
        @endif
    </div>

    <div class="pvm-card" style="padding:0;overflow:hidden;">
        @if ($notifications->isEmpty())
            <div class="pvm-empty" style="border:0;border-radius:0;">{{ __('Aucune notification.') }}</div>
        @else
            <ul style="list-style:none;margin:0;padding:0;">
                @foreach ($notifications as $notification)
                    @php
                        $data = $notification->data ?? [];
                        $title = $data['titre'] ?? __('PV');
                        $type = $data['type'] ?? '';
                        $msg = $type === 'pv_validation_request'
                            ? __('La signature de ce PV est attendue.')
                            : ($type === 'pv_rejected' ? __('Ce PV a été rejeté.') : __('Ce PV a été validé et signé.'));
                        $url = !empty($data['url']) ? $data['url'] : route('pv-module.show', $data['pv_id'] ?? 0);
                    @endphp
                    <li style="display:flex;align-items:flex-start;gap:12px;padding:14px 20px;border-bottom:1px solid var(--pvm-border);{!! $notification->read_at ? '' : 'background:var(--pvm-primary-soft);' !!}">
                        <span style="width:10px;height:10px;border-radius:50%;flex:none;margin-top:5px;background:{!! $notification->read_at ? 'var(--pvm-border-strong)' : 'var(--pvm-primary)' !!};"></span>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;">{{ $title }}</div>
                            <div class="pvm-soft" style="font-size:14px;margin-top:2px;">{{ $msg }}</div>
                            <div class="pvm-muted" style="margin-top:4px;">{{ $notification->created_at?->format('d/m/Y H:i') }}</div>
                        </div>
                        <a href="{{ $url }}" class="pvm-btn pvm-btn-line pvm-btn-sm">{{ __('Ouvrir le PV') }}</a>
                        <form method="POST" action="{{ route('pv-module.notifications.read', $notification->id) }}">
                            @csrf
                            <button type="submit" class="pvm-btn pvm-btn-line pvm-btn-sm">{{ __('Marquer comme lu') }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @if ($notifications->hasPages())
        <div class="pvm-card pvm-no-print">
            {{ $notifications->links() }}
        </div>
    @endif
@endsection