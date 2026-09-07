<?php

namespace SalsabilEnnaiem\PvModule\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use SalsabilEnnaiem\PvModule\Contracts\ParticipantResolver;
use SalsabilEnnaiem\PvModule\Exceptions\PvModuleException;
use SalsabilEnnaiem\PvModule\Models\Pv;
use SalsabilEnnaiem\PvModule\Models\PvValidation;
use SalsabilEnnaiem\PvModule\Notifications\PvValidationRequest;
use SalsabilEnnaiem\PvModule\Notifications\PvValidated;

class PvService
{
    public function __construct(
        private SignatureService $signatures,
        private ParticipantResolver $participants,
    ) {
    }

    public function store(array $data, mixed $actor, ?string $sourceType = null, ?int $sourceId = null): Pv
    {
        $pv = Pv::create([
            'created_by' => $actor->getKey(),
            'titre' => $data['titre'],
            'contenu' => $data['contenu'] ?? [],
            'template_data' => $data['template_data'] ?? null,
            'statut' => Pv::STATUT_BROUILLON,
            'date_generation' => now(),
            'type' => $data['type'] ?? 'pv',
            'source_type' => $data['source_type'] ?? $sourceType,
            'source_id' => $data['source_id'] ?? $sourceId,
            'versions' => [[
                'version' => 1,
                'titre' => $data['titre'],
                'contenu' => $data['contenu'] ?? [],
                'template_data' => $data['template_data'] ?? null,
                'receivers' => null,
                'signature_deadline' => null,
                'statut' => Pv::STATUT_BROUILLON,
                'saved_by' => $actor->getKey(),
                'saved_at' => now()->toIso8601String(),
            ]],
        ]);

        return $pv;
    }

    public function storeAndSend(array $data, mixed $actor): Pv
    {
        if (!$this->signatures->hasSignature($actor)) {
            throw PvModuleException::create("Vous devez d'abord enregistrer votre signature avant de créer et envoyer un PV.");
        }

        $placements = $this->participants->normalizePlacements($data['receivers'] ?? []);

        if (empty($placements)) {
            throw PvModuleException::create('Au moins un participant est requis.');
        }

        $deadline = $this->parseDeadline($data['signature_deadline'] ?? null);

        $pv = $this->store($data, $actor);

        $pv->update([
            'receivers' => $placements,
            'signature_deadline' => $deadline,
        ]);

        $this->sendToParticipants($pv);

        return $pv;
    }

    public function send(Pv $pv, array $receivers, ?string $signatureDeadline = null, bool $updateDeadline = false): void
    {
        $placements = $this->participants->normalizePlacements($receivers);
        if (empty($placements)) {
            throw PvModuleException::create('Aucun participant valide sélectionné.');
        }

        $pv->update([
            'receivers' => $placements,
            'signature_deadline' => $updateDeadline
                ? $this->parseDeadline($signatureDeadline)
                : $pv->signature_deadline,
        ]);

        $this->sendToParticipants($pv);
    }

    public function validate(Pv $pv, mixed $actor, ?string $commentaire = null): Pv
    {
        $this->assertBeforeDeadline($pv);

        $validation = $this->pendingValidation($pv, $actor);
        $validation->valider($commentaire);

        $this->notifyCreator($pv, $actor, true, $commentaire);

        return $pv->fresh();
    }

    public function sign(Pv $pv, mixed $actor): Pv
    {
        $this->assertBeforeDeadline($pv);

        if (!$this->signatures->hasSignature($actor)) {
            throw PvModuleException::create("Vous devez d'abord enregistrer votre signature dans les paramètres.");
        }

        $validation = $this->pendingValidation($pv, $actor);
        $validation->valider(null);

        $this->notifyCreator($pv, $actor, true, null);

        return $pv->fresh();
    }

    public function reject(Pv $pv, mixed $actor, string $commentaire): Pv
    {
        $validation = $this->pendingValidation($pv, $actor);
        $validation->rejeter($commentaire);

        $this->notifyCreator($pv, $actor, false, $commentaire);

        return $pv->fresh();
    }

    public function update(Pv $pv, array $data, mixed $actor): Pv
    {
        $templateData = (array) ($data['template_data'] ?? $pv->template_data ?? []);
        $templateChoice = $data['template_choice'] ?? 'current';
        if ($templateChoice === 'new' && isset($data['new_template_config'])) {
            $templateData = (array) $data['new_template_config'];
        }

        $placementsChanged = isset($data['receiver_placements'])
            && json_encode($data['receiver_placements']) !== json_encode($pv->receivers);

        if ($placementsChanged && $pv->validations()->exists()) {
            return $this->duplicateFromChanges($pv, $data, $templateData, $actor);
        }

        $versions = $pv->versions ?? [];
        $significantChange = ($data['contenu'] ?? []) != $pv->contenu
            || $templateData != $pv->template_data
            || $templateChoice === 'new'
            || $placementsChanged;

        if ($significantChange) {
            $versions[] = $this->snapshot($pv);
        } elseif (!empty($versions)) {
            $lastKey = count($versions) - 1;
            $versions[$lastKey]['titre'] = $data['titre'] ?? $pv->titre;
            $versions[$lastKey]['statut'] = $pv->statut;
        }

        $updateData = [
            'titre' => $data['titre'] ?? $pv->titre,
            'contenu' => $data['contenu'] ?? $pv->contenu,
            'template_data' => $templateData,
            'versions' => $versions,
            'updated_by' => $actor->getKey(),
        ];

        if (array_key_exists('signature_deadline', $data)) {
            $updateData['signature_deadline'] = $this->parseDeadline($data['signature_deadline']);
        }

        if (array_key_exists('receiver_placements', $data)) {
            $updateData['receivers'] = $this->participants->normalizePlacements($data['receiver_placements']);
        } elseif (array_key_exists('receivers', $data)) {
            $updateData['receivers'] = $this->participants->normalizePlacements($data['receivers']);
        }

        $pv->update($updateData);

        return $pv->fresh();
    }

    protected function duplicateFromChanges(Pv $pv, array $data, array $templateData, mixed $actor): Pv
    {
        $newPv = $pv->replicate();
        $newPv->created_by = $actor->getKey();
        $newPv->statut = Pv::STATUT_BROUILLON;
        $newPv->date_generation = now();
        $newPv->date_validation = null;
        $newPv->receivers = isset($data['receiver_placements'])
            ? $this->participants->normalizePlacements($data['receiver_placements'])
            : $pv->receivers;
        $newPv->titre = $data['titre'] ?? $pv->titre;
        $newPv->contenu = $data['contenu'] ?? $pv->contenu;
        $newPv->template_data = $templateData;

        if (array_key_exists('signature_deadline', $data)) {
            $newPv->signature_deadline = $this->parseDeadline($data['signature_deadline']);
        }

        $versions = $pv->versions ?? [];
        $versions[] = $this->snapshot($pv);
        $newPv->versions = $versions;

        $newPv->save();

        Log::info("PvModule: created PV {$newPv->id} from PV {$pv->id} after receiver change.");

        return $newPv;
    }

    protected function snapshot(Pv $pv): array
    {
        return [
            'version' => count($pv->versions ?? []) + 1,
            'titre' => $pv->titre,
            'contenu' => $pv->contenu,
            'template_data' => $pv->template_data,
            'receivers' => $pv->receivers,
            'signature_deadline' => $pv->signature_deadline?->toIso8601String(),
            'statut' => $pv->statut,
            'saved_by' => $pv->created_by,
            'saved_at' => now()->toIso8601String(),
        ];
    }

    protected function sendToParticipants(Pv $pv): void
    {
        $receivers = $pv->receivers ?? [];

        if (empty($receivers)) {
            return;
        }

        $wasEnAttente = $pv->statut === Pv::STATUT_EN_ATTENTE;
        $pv->statut = Pv::STATUT_EN_ATTENTE;
        if (! $wasEnAttente) {
            $pv->recordVersion();
        }
        $pv->save();

        $currentVersion = count($pv->versions ?? []) ?: 1;

        foreach ($receivers as $receiver) {
            $userId = is_array($receiver) ? (int) ($receiver['userId'] ?? 0) : (int) $receiver;
            if ($userId <= 0) {
                continue;
            }

            if ($pv->validations()->where('user_id', $userId)->exists()) {
                continue;
            }

            if ($userId === $pv->created_by && $this->signatures->hasSignature($pv->createur)) {
                PvValidation::create([
                    'pv_id' => $pv->id,
                    'user_id' => $userId,
                    'version' => $currentVersion,
                    'statut' => PvValidation::STATUT_VALIDE,
                    'date_reponse' => now(),
                ]);

                continue;
            }

            $PVrs = $this->userModel()::find($userId);
            if (!$PVrs) {
                continue;
            }

            PvValidation::create([
                'pv_id' => $pv->id,
                'user_id' => $userId,
                'version' => $currentVersion,
                'statut' => PvValidation::STATUT_EN_ATTENTE,
            ]);

            $PVrs->notify(new PvValidationRequest($pv, $PVrs));
        }

        if ($pv->estCompletementValide()) {
            $pv->statut = Pv::STATUT_VALIDE;
            $pv->date_validation = now();
            $pv->recordVersion(Pv::STATUT_VALIDE);
            $pv->save();
        }
    }

    protected function pendingValidation(Pv $pv, mixed $actor): PvValidation
    {
        $validation = $pv->validations()
            ->where('user_id', $actor->getKey())
            ->where('statut', PvValidation::STATUT_EN_ATTENTE)
            ->first();

        if (!$validation) {
            throw PvModuleException::create('Aucune validation en attente pour cet utilisateur sur ce PV.');
        }

        return $validation;
    }

    protected function assertBeforeDeadline(Pv $pv): void
    {
        if ($pv->deadlineIsPast()) {
            throw PvModuleException::create('Le délai de signature pour ce PV est dépassé.');
        }
    }

    protected function notifyCreator(Pv $pv, mixed $actor, bool $isValidation, ?string $commentaire): void
    {
        try {
            $creator = $pv->createur;
            if ($creator && $creator->getKey() !== $actor->getKey()) {
                $creator->notify(new PvValidated($pv, $actor, $isValidation, $commentaire));
            }
        } catch (\Throwable $e) {
            Log::error('PvModule: failed to notify creator: '.$e->getMessage());
        }
    }

    protected function parseDeadline(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }

    protected function userModel(): string
    {
        return (string) config('pv-module.user_model', \App\Models\User::class);
    }

    /**
     * Nettoyer les PVs en double pour une même source (réunion, commission, ...).
     * - Contenu identique → supprimer le doublon, garder le plus récent
     * - Seul le titre ou la deadline diffère → fusionner dans le plus récent
     * - Contenu différent → préserver l'ancien comme version dans le plus récent
     */
    public function cleanupDuplicates(string $sourceType, int $sourceId): void
    {
        $pvs = Pv::bySource($sourceType, $sourceId)->orderByDesc('created_at')->get();

        if ($pvs->count() < 2) {
            return;
        }

        $latest = $pvs->first();

        foreach ($pvs->slice(1) as $pv) {
            $sameContenu = $pv->contenu == $latest->contenu;
            $sameTitre = $pv->titre == $latest->titre;
            $sameDeadline = $pv->signature_deadline?->toIso8601String() === $latest->signature_deadline?->toIso8601String();

            if ($sameContenu && $sameTitre && $sameDeadline) {
                $pv->validations()->delete();
                $pv->delete();

                continue;
            }

            if ($sameContenu) {
                if (!$sameTitre) {
                    $latest->titre = $pv->titre;
                }
                if (!$sameDeadline) {
                    $latest->signature_deadline = $pv->signature_deadline;
                }
                $latest->save();
                $pv->validations()->delete();
                $pv->delete();

                continue;
            }

            $versions = $latest->versions ?? [];
            $versions[] = [
                'version' => count($versions) + 1,
                'titre' => $pv->titre,
                'contenu' => $pv->contenu,
                'template_data' => $pv->template_data,
                'receivers' => $pv->receivers,
                'signature_deadline' => $pv->signature_deadline?->toIso8601String(),
                'statut' => $pv->statut,
                'saved_by' => $pv->created_by,
                'saved_at' => $pv->created_at?->toIso8601String(),
            ];
            $latest->versions = $versions;
            $latest->save();
            $pv->validations()->delete();
            $pv->delete();
        }
    }
}