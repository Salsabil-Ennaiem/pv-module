@extends('pv-module::layouts.app')

@section('content')
    @include('pv-module::partials.breadcrumb', [
        'crumbs' => [['label' => __('Ma signature numérique')]],
    ])

    <div class="pvm-page-head">
        <div>
            <h1 class="pvm-title">{{ __('Ma signature numérique') }}</h1>
            <p class="pvm-subtitle">
                {{ __('Votre signature est utilisée pour signer les PV que vous validez. Taille max :') }}
                <span class="pvm-soft">{{ config('pv-module.max_signature_size_kb', 2048) }} {{ __('KB') }}</span>
            </p>
        </div>
    </div>

    @if ($signature)
        <div class="pvm-card">
            <div class="pvm-card-header"><h2>{{ __('Signature actuelle') }}</h2></div>
            <div class="pvm-signature-preview" style="margin:8px 0 16px;">
                <img src="{{ route('pv-module.signature.image') }}" alt="{{ __('Signature actuelle') }}">
            </div>
            <form method="POST" action="{{ route('pv-module.signature.delete') }}"
                  onsubmit="return confirm('{{ __('Supprimer ma signature ?') }}');">
                @csrf
                @method('DELETE')
                <button type="submit" class="pvm-btn pvm-btn-danger">{{ __('Supprimer ma signature') }}</button>
            </form>
        </div>
    @else
        <div class="pvm-alert pvm-alert-error">{{ __('Vous n\'avez pas encore de signature enregistrée.') }}</div>
    @endif

    <div class="pvm-card">
        <div class="pvm-card-header"><h2>{{ __('Option 1 — Importer une image') }}</h2></div>
        <form method="POST" action="{{ route('pv-module.signature.upload') }}" enctype="multipart/form-data" class="pvm-form">
            @csrf
            <label for="signature">{{ __('Fichier image') }}</label>
            <input type="file" name="signature" id="signature" accept="image/png,image/jpeg,image/gif" required>
            <div class="pvm-actions">
                <button type="submit" class="pvm-btn">{{ __('Importer') }}</button>
            </div>
        </form>
    </div>

    <div class="pvm-card">
        <div class="pvm-card-header"><h2>{{ __('Option 2 — Dessiner ma signature') }}</h2></div>
        <canvas id="pvm-sign-canvas" class="pvm-signature-canvas" width="560" height="220"></canvas>
        <div class="pvm-actions">
            <button type="button" id="pvm-sign-clear" class="pvm-btn pvm-btn-secondary">{{ __('Effacer') }}</button>
            <button type="button" id="pvm-sign-save" class="pvm-btn pvm-btn-success">{{ __('Enregistrer la signature') }}</button>
        </div>
        <div id="pvm-sign-msg" class="pvm-muted" style="margin-top:8px;"></div>
    </div>

    @push('scripts')
        <script>
            (function () {
                var canvas = document.getElementById('pvm-sign-canvas');
                if (!canvas) return;
                var ctx = canvas.getContext('2d');
                var drawing = false;

                function pos(e) {
                    var rect = canvas.getBoundingClientRect();
                    var scaleX = canvas.width / rect.width;
                    var scaleY = canvas.height / rect.height;
                    var point = (e.touches && e.touches[0]) || e;
                    return {
                        x: (point.clientX - rect.left) * scaleX,
                        y: (point.clientY - rect.top) * scaleY,
                    };
                }

                canvas.addEventListener('mousedown', function (e) { drawing = true; ctx.beginPath(); ctx.moveTo(pos(e).x, pos(e).y); e.preventDefault(); });
                canvas.addEventListener('mousemove', function (e) {
                    if (!drawing) return;
                    var p = pos(e);
                    ctx.lineTo(p.x, p.y);
                    ctx.strokeStyle = '#0f172a';
                    ctx.lineWidth = 2;
                    ctx.stroke();
                });
                canvas.addEventListener('mouseup', function () { drawing = false; });
                canvas.addEventListener('mouseleave', function () { drawing = false; });
                canvas.addEventListener('touchstart', function (e) { drawing = true; ctx.beginPath(); ctx.moveTo(pos(e).x, pos(e).y); e.preventDefault(); });
                canvas.addEventListener('touchmove', function (e) { if (drawing) { var p = pos(e); ctx.lineTo(p.x, p.y); ctx.strokeStyle = '#0f172a'; ctx.lineWidth = 2; ctx.stroke(); } e.preventDefault(); });
                canvas.addEventListener('touchend', function () { drawing = false; });

                document.getElementById('pvm-sign-clear').addEventListener('click', function () {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                });

                document.getElementById('pvm-sign-save').addEventListener('click', function () {
                    var data = canvas.toDataURL('image/png');
                    if (data === 'data:,' ) { return; }
                    var msg = document.getElementById('pvm-sign-msg');
                    msg.textContent = '{{ __('Enregistrement…') }}';

                    fetch('{{ route('pv-module.signature.save') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify({ signature_data: data }),
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res.success) {
                            msg.textContent = '{{ __('Signature enregistrée avec succès.') }}';
                            location.reload();
                        } else {
                            msg.textContent = '{{ __('Échec de l\'enregistrement : ') }}' + (res.message || '');
                        }
                    })
                    .catch(function () { msg.textContent = '{{ __('Erreur réseau lors de l\'enregistrement.') }}'; });
                });
            })();
        </script>
    @endpush
@endsection