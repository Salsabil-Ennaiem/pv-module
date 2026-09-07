<?php

namespace SalsabilEnnaiem\PvModule\Defaults;

use SalsabilEnnaiem\PvModule\Contracts\ApprovalRules;
use SalsabilEnnaiem\PvModule\Models\Pv;
use SalsabilEnnaiem\PvModule\Models\PvValidation;

/**
 * Règle d'approbation par défaut : le PV devient 'valide' si
 * tous les validateurs ont validé OU le créateur a validé.
 */
class DefaultApprovalRules implements ApprovalRules
{
    public function isApproved(Pv $pv): bool
    {
        if ($pv->validationsValidees()->where('user_id', $pv->created_by)->exists()) {
            return true;
        }

        $total = $pv->validations()->count();
        $validees = $pv->validationsValidees()->count();

        return $total > 0 && $total === $validees;
    }
}