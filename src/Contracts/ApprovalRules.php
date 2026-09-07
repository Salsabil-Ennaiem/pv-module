<?php

namespace SalsabilEnnaiem\PvModule\Contracts;

use SalsabilEnnaiem\PvModule\Models\Pv;

/**
 * Contrat de règle métier : décide quand un PV est considéré
 * comme complètement validé (passage au statut 'valide').
 */
interface ApprovalRules
{
    public function isApproved(Pv $pv): bool;
}