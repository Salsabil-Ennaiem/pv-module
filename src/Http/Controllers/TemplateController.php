<?php

namespace SalsabilEnnaiem\PvModule\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use SalsabilEnnaiem\PvModule\Contracts\CanManagePv;
use SalsabilEnnaiem\PvModule\Models\PvTemplate;

class TemplateController extends Controller
{
    public function __construct(private CanManagePv $rules)
    {
    }

    /**
     * Capacité optionnelle : l'hôte peut l'ajouter à sa classe de règles
     * (ex. DefaultPvRules) sans que le contrat soit modifié.
     */
    protected function canManageTemplates(mixed $actor): bool
    {
        return method_exists($this->rules, 'canManageTemplates') && $this->rules->canManageTemplates($actor);
    }

    public function index(Request $request): View
    {
        if (!$this->canManageTemplates($request->user())) {
            abort(Response::HTTP_FORBIDDEN, __('Action non autorisée.'));
        }

        $user = $request->user();
        $types = (array) config('pv-module.types', ['pv']);

        $defaults = collect($types)->map(fn (string $type) => [
            'type' => $type,
            'template' => PvTemplate::getDefault($type),
        ]);

        $customs = PvTemplate::where('user_id', $user->getKey())
            ->where('is_custom', true)
            ->orderBy('type')
            ->get();

        return view('pv-module::templates.index', [
            'defaults' => $defaults,
            'customs' => $customs,
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        if (!$this->canManageTemplates($request->user())) {
            abort(Response::HTTP_FORBIDDEN, __('Action non autorisée.'));
        }

        $type = (string) $request->input('type', 'pv');
        $default = PvTemplate::getDefault($type);

        if ($default === null) {
            return redirect()
                ->route('pv-module.templates.index')
                ->with('error', __("Aucun modèle par défaut pour ce type."));
        }

        PvTemplate::saveForUser(
            $request->user()->getKey(),
            $type,
            $default->config ?? [],
            $default->orientation ?? 'portrait',
        );

        return redirect()
            ->route('pv-module.templates.index')
            ->with('success', __('Modèle personnalisé créé.'));
    }

    public function edit(Request $request, PvTemplate $template): View
    {
        if (!$this->canManageTemplates($request->user())) {
            abort(Response::HTTP_FORBIDDEN, __('Action non autorisée.'));
        }

        if ((int) $template->user_id !== (int) $request->user()->getKey()) {
            abort(Response::HTTP_FORBIDDEN, __('Action non autorisée.'));
        }

        return view('pv-module::templates.edit', [
            'template' => $template,
            'sections' => $template->getSectionsOrdered(),
            'margins' => $template->getMargins(),
        ]);
    }

    public function update(Request $request, PvTemplate $template): RedirectResponse
    {
        if (!$this->canManageTemplates($request->user())) {
            abort(Response::HTTP_FORBIDDEN, __('Action non autorisée.'));
        }

        if ((int) $template->user_id !== (int) $request->user()->getKey()) {
            abort(Response::HTTP_FORBIDDEN, __('Action non autorisée.'));
        }

        $validator = Validator::make($request->all(), [
            'orientation' => ['required', Rule::in(['portrait', 'landscape'])],
            'margins.top' => ['nullable', 'integer', 'between:0,80'],
            'margins.bottom' => ['nullable', 'integer', 'between:0,80'],
            'margins.left' => ['nullable', 'integer', 'between:0,80'],
            'margins.right' => ['nullable', 'integer', 'between:0,80'],
            'sections' => ['nullable', 'array'],
            'sections.*.id' => ['required', 'string'],
            'sections.*.title' => ['nullable', 'string', 'max:190'],
            'sections.*.titleColor' => ['nullable', 'string', 'max:20'],
            'sections.*.titleSize' => ['nullable', 'integer', 'between:8,40'],
            'sections.*.textSize' => ['nullable', 'integer', 'between:7,32'],
            'sections.*.textAlign' => ['nullable', Rule::in(['left', 'center', 'right'])],
            'section_order' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        $order = array_values(array_filter(preg_split(
            '/[,\s]+/',
            (string) ($data['section_order'] ?? ''),
            -1,
            PREG_SPLIT_NO_EMPTY
        )));

        $existing = collect($template->config['sections'] ?? []);
        $incoming = $data['sections'] ?? [];
        $byId = collect($incoming)->keyBy('id');

        $newSections = collect($order)
            ->filter(fn (string $id) => $id !== '')
            ->unique()
            ->map(function (string $id) use ($existing, $byId) {
                $original = $existing->firstWhere('id', $id) ?? ['id' => $id];
                $patch = $byId->get($id) ?? [];

                return [
                    'id' => $id,
                    'fixed' => (bool) ($original['fixed'] ?? false),
                    'title' => ($patch['title'] ?? null) !== '' ? ($patch['title'] ?? ($original['title'] ?? $id)) : ($original['title'] ?? $id),
                    'styles' => [
                        'titleColor' => $patch['titleColor'] ?? ($original['styles']['titleColor'] ?? '#334155'),
                        'titleSize' => $patch['titleSize'] ?? ($original['styles']['titleSize'] ?? 13),
                        'font' => $original['styles']['font'] ?? 'DejaVu Sans',
                        'textColor' => $original['styles']['textColor'] ?? '#0f172a',
                        'textSize' => $patch['textSize'] ?? ($original['styles']['textSize'] ?? 11),
                        'textAlign' => $patch['textAlign'] ?? ($original['styles']['textAlign'] ?? 'left'),
                    ],
                ];
            })
            ->values();

        foreach ($existing->where('fixed', true) as $fixedSection) {
            $already = $newSections->contains(fn (array $s) => ($s['id'] ?? null) === ($fixedSection['id'] ?? null));
            if (!$already) {
                $newSections->push($fixedSection);
            }
        }

        $config = $template->config ?? [];
        $config['sections'] = $newSections->values()->all();
        $config['margins'] = [
            'top' => (int) ($data['margins']['top'] ?? 20),
            'bottom' => (int) ($data['margins']['bottom'] ?? 20),
            'left' => (int) ($data['margins']['left'] ?? 20),
            'right' => (int) ($data['margins']['right'] ?? 20),
        ];

        $template->update([
            'config' => $config,
            'orientation' => $data['orientation'],
            'is_custom' => true,
            'is_default' => false,
        ]);

        return redirect()
            ->route('pv-module.templates.index')
            ->with('success', __('Modèle mis à jour.'));
    }

    public function destroy(Request $request, PvTemplate $template): RedirectResponse
    {
        if (!$this->canManageTemplates($request->user())) {
            abort(Response::HTTP_FORBIDDEN, __('Action non autorisée.'));
        }

        if ((int) $template->user_id === (int) $request->user()->getKey()) {
            $template->delete();
        }

        return redirect()
            ->route('pv-module.templates.index')
            ->with('success', __('Modèle supprimé.'));
    }

    public function reset(Request $request): RedirectResponse
    {
        if (!$this->canManageTemplates($request->user())) {
            abort(Response::HTTP_FORBIDDEN, __('Action non autorisée.'));
        }

        $type = (string) $request->input('type', 'pv');
        PvTemplate::resetForUser($request->user()->getKey(), $type);

        return redirect()
            ->route('pv-module.templates.index')
            ->with('success', __('Votre modèle personnalisé a été réinitialisé.'));
    }
}