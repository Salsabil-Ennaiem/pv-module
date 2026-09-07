@extends('pv-module::layouts.app')

@section('content')
    @include('pv-module::partials.breadcrumb', [
        'crumbs' => [
            ['label' => __('Modèles de documents'), 'url' => route('pv-module.templates.index')],
            ['label' => __('Mon modèle personnalisé')],
        ],
    ])

    <div class="pvm-page-head">
        <div>
            <h1 class="pvm-title">{{ __('Mon modèle personnalisé') }} — {{ $template->type }}</h1>
            <p class="pvm-subtitle">{{ __('Ajustez l\'orientation, les marges, l\'ordre des sections et le style du rendu PDF.') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('pv-module.templates.update', $template) }}" class="pvm-form" id="template-form">
        @csrf
        @method('PUT')

        <div class="pvm-card">
            <div class="pvm-card-header"><h2>{{ __('Paramètres du modèle') }}</h2></div>

            <div class="pvm-field-grid">
                <div>
                    <label for="orientation">{{ __('Orientation') }}</label>
                    <select name="orientation" id="orientation">
                        <option value="portrait" @selected($template->orientation === 'portrait')>{{ __('Portrait') }}</option>
                        <option value="landscape" @selected($template->orientation === 'landscape')>{{ __('Paysage') }}</option>
                    </select>
                </div>
            </div>

            <div class="pvm-field-grid" style="grid-template-columns:repeat(auto-fit,minmax(120px,1fr));">
                <div>
                    <label for="margins_top">{{ __('Haut') }} (mm)</label>
                    <input type="number" name="margins[top]" id="margins_top" min="0" max="80" value="{{ $margins['top'] ?? 20 }}">
                </div>
                <div>
                    <label for="margins_bottom">{{ __('Bas') }} (mm)</label>
                    <input type="number" name="margins[bottom]" id="margins_bottom" min="0" max="80" value="{{ $margins['bottom'] ?? 20 }}">
                </div>
                <div>
                    <label for="margins_left">{{ __('Gauche') }} (mm)</label>
                    <input type="number" name="margins[left]" id="margins_left" min="0" max="80" value="{{ $margins['left'] ?? 20 }}">
                </div>
                <div>
                    <label for="margins_right">{{ __('Droite') }} (mm)</label>
                    <input type="number" name="margins[right]" id="margins_right" min="0" max="80" value="{{ $margins['right'] ?? 20 }}">
                </div>
            </div>
        </div>

        <div class="pvm-card">
            <div class="pvm-card-header">
                <h2>{{ __('Sections du document') }}</h2>
                <span class="pvm-muted">{{ count($sections) }} section(s) — {{ __('glissez pour réordonner') }}</span>
            </div>

            <input type="hidden" name="section_order" id="section_order" value="{{ collect($sections)->pluck('id')->implode(',') }}">

            <div id="sections-list">
                @foreach ($sections as $i => $section)
                    @php
                        $id = $section['id'] ?? ('s'.$i);
                        $align = $section['styles']['textAlign'] ?? 'left';
                    @endphp
                    <div class="pvm-section-card" data-section-id="{{ $id }}" data-section-index="{{ $i }}" @if(!empty($section['fixed'])) data-fixed="1" @endif>
                        <div class="pvm-section-head">
                            @if (empty($section['fixed']))
                                <span class="pvm-drag-handle" draggable="true" title="{{ __('Glisser pour réordonner') }}" aria-hidden="true">⠿</span>
                            @endif
                            <button type="button" class="pvm-section-toggle" data-toggle-type="title"
                                    data-toggle-id="{{ $id }}"><strong>{{ $section['title'] ?? $id }}</strong></button>
                            <span class="pvm-section-collapse">▾</span>
                            <div class="pvm-actions" style="margin-top:0;gap:6px;">
                                @if (empty($section['fixed']))
                                    <button type="button" class="pvm-btn pvm-btn-line pvm-btn-sm" data-move="up" title="{{ __('Monter') }}">↑</button>
                                    <button type="button" class="pvm-btn pvm-btn-line pvm-btn-sm" data-move="down" title="{{ __('Descendre') }}">↓</button>
                                @endif
                                <button type="button" class="pvm-btn pvm-btn-line pvm-btn-sm" data-toggle-type="button"
                                        data-toggle-id="{{ $id }}">{{ __('Voir') }}</button>
                                @if (empty($section['fixed']))
                                    <button type="button" class="pvm-btn pvm-btn-danger-line pvm-btn-sm" data-delete-section
                                            data-section-id="{{ $id }}" title="{{ __('Supprimer cette section') }}">{{ __('Supprimer') }}</button>
                                @endif
                            </div>
                        </div>
                        <div class="pvm-section-body" id="section-body-{{ $id }}">
                            @if (!empty($section['fixed']))
                                <p class="pvm-help" style="margin:0 0 8px;">{{ __('Section fixe — le contenu est géré automatiquement.') }}</p>
                            @else
                                <div class="pvm-field" style="margin-bottom:8px;">
                                    <label for="sec-{{ $id }}-title">{{ __('Titre de la section') }}</label>
                                    <input type="text" id="sec-{{ $id }}-title" name="sections[{{ $id }}][title]"
                                           value="{{ $section['title'] ?? '' }}">
                                </div>
                            @endif
                            <input type="hidden" name="sections[{{ $id }}][id]" value="{{ $id }}">
                            <div class="pvm-field-grid" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr));">
                                <div class="pvm-field">
                                    <label for="sec-{{ $id }}-color">{{ __('Couleur du titre') }}</label>
                                    <input type="color" id="sec-{{ $id }}-color" name="sections[{{ $id }}][titleColor]"
                                           value="{{ $section['styles']['titleColor'] ?? '#334155' }}" style="height:40px;padding:4px;">
                                </div>
                                <div class="pvm-field">
                                    <label for="sec-{{ $id }}-tsize">{{ __('Taille du titre (pt)') }}</label>
                                    <input type="number" id="sec-{{ $id }}-tsize" name="sections[{{ $id }}][titleSize]"
                                           min="8" max="40" value="{{ $section['styles']['titleSize'] ?? 13 }}">
                                </div>
                                <div class="pvm-field">
                                    <label for="sec-{{ $id }}-xsize">{{ __('Taille du texte (pt)') }}</label>
                                    <input type="number" id="sec-{{ $id }}-xsize" name="sections[{{ $id }}][textSize]"
                                           min="7" max="32" value="{{ $section['styles']['textSize'] ?? 11 }}">
                                </div>
                                @if (empty($section['fixed']))
                                    <div class="pvm-field">
                                        <label for="sec-{{ $id }}-align">{{ __('Alignement') }}</label>
                                        <select id="sec-{{ $id }}-align" name="sections[{{ $id }}][textAlign]">
                                            <option value="left" @selected($align === 'left')>{{ __('Gauche') }}</option>
                                            <option value="center" @selected($align === 'center')>{{ __('Centre') }}</option>
                                            <option value="right" @selected($align === 'right')>{{ __('Droite') }}</option>
                                        </select>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="margin-top:4px;">
                <button type="button" class="pvm-btn pvm-btn-line" id="pvm-add-section">{{ __('+ Ajouter une section') }}</button>
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
            <a href="{{ route('pv-module.templates.index') }}" class="pvm-btn pvm-btn-line">{{ __('Retour') }}</a>
            <a href="{{ route('pv-module.index') }}" class="pvm-btn pvm-btn-secondary">{{ __('Annuler') }}</a>
            <button type="submit" class="pvm-btn pvm-btn-success">{{ __('Sauvegarder le modèle') }}</button>
        </div>
    </form>

    @push('styles')
        <style>
            .pvm-section-card {
                border: 1px solid var(--pvm-border);
                border-radius: var(--pvm-radius-sm);
                padding: 12px 16px; margin-bottom: 12px;
                background: var(--pvm-surface);
                box-shadow: var(--pvm-shadow-sm);
            }
            .pvm-section-card.is-moving { opacity: .4; }
            .pvm-section-head {
                display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
            }
            .pvm-drag-handle {
                cursor: grab; color: var(--pvm-text-soft); font-size: 18px; line-height: 1;
                user-select: none;
            }
            .pvm-section-card.is-dragging .pvm-drag-handle { cursor: grabbing; }
            .pvm-section-toggle {
                background: none; border: 0; padding: 0; margin: 0; cursor: pointer; text-align: left;
            }
            .pvm-section-toggle strong { font-size: 13px; }
            .pvm-section-toggle:hover strong { color: var(--pvm-primary); }
            html[dir="rtl"] .pvm-section-toggle { text-align: right; }
            .pvm-section-collapse {
                color: var(--pvm-text-soft); font-size: 13px; transition: transform .2s ease;
            }
            .pvm-section-card.is-collapsed .pvm-section-collapse { transform: rotate(-90deg); }
            .pvm-section-body { margin-top: 10px; }
            .pvm-section-card.is-collapsed .pvm-section-body { display: none; }
        </style>
    @endpush

    @push('scripts')
        <script>
            (function () {
                var list = document.getElementById('sections-list');
                var orderInput = document.getElementById('section_order');
                if (!list || !orderInput) return;

                function sync() {
                    var ids = Array.prototype.map.call(list.children, function (el) { return el.dataset.sectionId; });
                    orderInput.value = ids.join(',');
                }

                /* ----- Réordonner : flèches ↑ / ↓ ----- */
                function available(el, dir) {
                    var nodes = list.children;
                    var idx = Array.prototype.indexOf.call(nodes, el);
                    var target = dir === 'up' ? idx - 1 : idx + 1;
                    return target >= 0 && target < nodes.length ? nodes[target] : null;
                }

                list.addEventListener('click', function (e) {
                    var btn = e.target.closest('[data-move]');
                    if (!btn) return;
                    var card = btn.closest('.pvm-section-card');
                    if (!card || card.dataset.fixed) return;
                    var target = available(card, btn.dataset.move);
                    if (!target || target.dataset.fixed) return;
                    if (btn.dataset.move === 'up') { list.insertBefore(card, target); }
                    else { list.insertBefore(target, card); }
                    sync();
                });

                /* ----- Réordonner : drag & drop natif ----- */
                var dragEl = null;

                list.addEventListener('dragstart', function (e) {
                    var card = e.target.closest('.pvm-section-card');
                    if (!card || card.dataset.fixed) return;
                    if (e.target.closest('input, select, textarea, button, a, label')) return;
                    dragEl = card;
                    card.classList.add('is-moving', 'is-dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', card.dataset.sectionId);
                });

                list.addEventListener('dragenter', function (e) { e.preventDefault(); });

                list.addEventListener('dragover', function (e) {
                    if (!dragEl) return;
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    var over = e.target.closest('.pvm-section-card');
                    if (!over || over === dragEl || over.dataset.fixed) return;
                    var rect = over.getBoundingClientRect();
                    var after = (e.clientY - rect.top) > (rect.height / 2);
                    if (after) { over.after(dragEl); } else { over.before(dragEl); }
                });

                list.addEventListener('drop', function (e) {
                    if (!dragEl) return;
                    e.preventDefault();
                    dragEl.classList.remove('is-moving', 'is-dragging');
                    dragEl = null;
                    sync();
                });

                list.addEventListener('dragend', function () {
                    if (!dragEl) return;
                    dragEl.classList.remove('is-moving', 'is-dragging');
                    dragEl = null;
                    sync();
                });

                /* ----- Afficher / masquer une section (titre cliquable ou bouton Voir) ----- */
                list.addEventListener('click', function (e) {
                    var toggle = e.target.closest('[data-toggle-type]');
                    if (!toggle) return;
                    var id = toggle.dataset.toggleId;
                    var card = document.querySelector('.pvm-section-card[data-section-id="' + id + '"]');
                    if (!card) return;
                    card.classList.toggle('is-collapsed');
                    var collapsed = card.classList.contains('is-collapsed');
                    card.querySelectorAll('[data-toggle-type="button"]').forEach(function (b) {
                        b.textContent = collapsed ? '{{ __('Voir') }}' : '{{ __('Masquer') }}';
                    });
                });

                /* ----- Supprimer une section (sauf fixes) ----- */
                list.addEventListener('click', function (e) {
                    var del = e.target.closest('[data-delete-section]');
                    if (!del) return;
                    if (!window.confirm('{{ __('Supprimer cette section ?') }}')) return;
                    var card = del.closest('.pvm-section-card');
                    if (!card) return;
                    card.remove();
                    sync();
                });

                /* ----- Ajouter une section ----- */
                var addBtn = document.getElementById('pvm-add-section');
                if (addBtn) {
                    addBtn.addEventListener('click', function () {
                        var newId = 'sec-' + Date.now();
                        var card = document.createElement('div');
                        card.className = 'pvm-section-card';
                        card.dataset.sectionId = newId;

                        var head = document.createElement('div');
                        head.className = 'pvm-section-head';

                        var handle = document.createElement('span');
                        handle.className = 'pvm-drag-handle';
                        handle.setAttribute('draggable', 'true');
                        handle.setAttribute('title', '{{ __('Glisser pour réordonner') }}');
                        handle.textContent = '⠿';

                        var titleBtn = document.createElement('button');
                        titleBtn.type = 'button';
                        titleBtn.className = 'pvm-section-toggle';
                        titleBtn.dataset.toggleType = 'title';
                        titleBtn.dataset.toggleId = newId;
                        var strong = document.createElement('strong');
                        strong.textContent = '{{ __('Nouvelle section') }}';
                        titleBtn.appendChild(strong);

                        var chevron = document.createElement('span');
                        chevron.className = 'pvm-section-collapse';
                        chevron.textContent = '▾';

                        var actions = document.createElement('div');
                        actions.className = 'pvm-actions';
                        actions.style.marginTop = '0';
                        actions.style.gap = '6px';

                        var btnUp = document.createElement('button');
                        btnUp.type = 'button'; btnUp.className = 'pvm-btn pvm-btn-line pvm-btn-sm';
                        btnUp.dataset.move = 'up'; btnUp.title = '{{ __('Monter') }}'; btnUp.textContent = '↑';
                        var btnDown = document.createElement('button');
                        btnDown.type = 'button'; btnDown.className = 'pvm-btn pvm-btn-line pvm-btn-sm';
                        btnDown.dataset.move = 'down'; btnDown.title = '{{ __('Descendre') }}'; btnDown.textContent = '↓';
                        var btnSee = document.createElement('button');
                        btnSee.type = 'button'; btnSee.className = 'pvm-btn pvm-btn-line pvm-btn-sm';
                        btnSee.dataset.toggleType = 'button'; btnSee.dataset.toggleId = newId;
                        btnSee.textContent = '{{ __('Voir') }}';
                        var btnDel = document.createElement('button');
                        btnDel.type = 'button'; btnDel.className = 'pvm-btn pvm-btn-danger-line pvm-btn-sm';
                        btnDel.dataset.deleteSection = ''; btnDel.dataset.sectionId = newId;
                        btnDel.title = '{{ __('Supprimer cette section') }}';
                        btnDel.textContent = '{{ __('Supprimer') }}';

                        actions.appendChild(btnUp);
                        actions.appendChild(btnDown);
                        actions.appendChild(btnSee);
                        actions.appendChild(btnDel);

                        head.appendChild(handle);
                        head.appendChild(titleBtn);
                        head.appendChild(chevron);
                        head.appendChild(actions);

                        var body = document.createElement('div');
                        body.className = 'pvm-section-body';
                        body.id = 'section-body-' + newId;
                        body.innerHTML =
                            '<div class="pvm-field" style="margin-bottom:8px;">' +
                            '  <label for="sec-' + newId + '-title">{{ __('Titre de la section') }}</label>' +
                            '  <input type="text" id="sec-' + newId + '-title" name="sections[' + newId + '][title]" value="{{ __('Nouvelle section') }}">' +
                            '</div>' +
                            '<input type="hidden" name="sections[' + newId + '][id]" value="' + newId + '">' +
                            '<div class="pvm-field-grid" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr));">' +
                            '  <div class="pvm-field"><label for="sec-' + newId + '-color">{{ __('Couleur du titre') }}</label>' +
                            '      <input type="color" id="sec-' + newId + '-color" name="sections[' + newId + '][titleColor]" value="#334155" style="height:40px;padding:4px;"></div>' +
                            '  <div class="pvm-field"><label for="sec-' + newId + '-tsize">{{ __('Taille du titre (pt)') }}</label>' +
                            '      <input type="number" id="sec-' + newId + '-tsize" name="sections[' + newId + '][titleSize]" min="8" max="40" value="13"></div>' +
                            '  <div class="pvm-field"><label for="sec-' + newId + '-xsize">{{ __('Taille du texte (pt)') }}</label>' +
                            '      <input type="number" id="sec-' + newId + '-xsize" name="sections[' + newId + '][textSize]" min="7" max="32" value="11"></div>' +
                            '  <div class="pvm-field"><label for="sec-' + newId + '-align">{{ __('Alignement') }}</label>' +
                            '      <select id="sec-' + newId + '-align" name="sections[' + newId + '][textAlign]">' +
                            '          <option value="left">{{ __('Gauche') }}</option>' +
                            '          <option value="center">{{ __('Centre') }}</option>' +
                            '          <option value="right">{{ __('Droite') }}</option>' +
                            '      </select></div>' +
                            '</div>';

                        card.appendChild(head);
                        card.appendChild(body);
                        list.appendChild(card);
                        sync();
                    });
                }

                sync();
            })();
        </script>
    @endpush
@endsection