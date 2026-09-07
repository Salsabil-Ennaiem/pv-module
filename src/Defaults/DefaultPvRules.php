<?php

namespace SalsabilEnnaiem\PvModule\Defaults;

use SalsabilEnnaiem\PvModule\Contracts\CanManagePv;
use SalsabilEnnaiem\PvModule\Models\Pv;

/**
 * Règles par défaut (génériques) :
 * - le créateur gère son PV (modifier, envoyer, supprimer, télécharger)
 * - un utilisateur peut valider/signer si une validation en attente lui est destinée
 * - la création est ouverte à tout acteur connecté
 */
class DefaultPvRules implements CanManagePv
{
    public function canCreate(mixed $actor): bool
    {
        return $actor !== null;
    }

    public function canUpdate(mixed $actor, Pv $pv): bool
    {
        return $this->isManager($actor, $pv);
    }

    public function canSend(mixed $actor, Pv $pv): bool
    {
        return $this->isManager($actor, $pv);
    }

    public function canValidate(mixed $actor, Pv $pv): bool
    {
        return $this->isValidator($actor, $pv);
    }

    public function canSign(mixed $actor, Pv $pv): bool
    {
        return $this->isValidator($actor, $pv);
    }

    public function canDelete(mixed $actor, Pv $pv): bool
    {
        return (bool) $actor && $pv->created_by === $actor->getKey();
    }

    public function canDownload(mixed $actor, Pv $pv): bool
    {
        if ($this->isManager($actor, $pv)) {
            return true;
        }

        return $this->isParticipant($actor, $pv);
    }

    public function canManageTemplates(mixed $actor): bool
    {
        return $actor !== null;
    }

    protected function isManager(mixed $actor, Pv $pv): bool
    {
        return $actor !== null && $pv->created_by === $actor->getKey();
    }

    protected function isParticipant(mixed $actor, Pv $pv): bool
    {
        if ($actor === null) {
            return false;
        }

        return $pv->validations()
            ->where('user_id', $actor->getKey())
            ->exists();
    }

    protected function isValidator(mixed $actor, Pv $pv): bool
    {
        if ($actor === null) {
            return false;
        }

        return $pv->validations()
            ->where('user_id', $actor->getKey())
            ->where('statut', \SalsabilEnnaiem\PvModule\Models\PvValidation::STATUT_EN_ATTENTE)
            ->exists();
    }
}