<?php

namespace SalsabilEnnaiem\PvModule\Contracts;

use SalsabilEnnaiem\PvModule\Models\Pv;

/**
 * Contrat d'autorisation : l'app hôte implémente qui peut
 * créer / modifier / envoyer / valider / signer / supprimer un PV.
 */
interface CanManagePv
{
    public function canCreate(mixed $actor): bool;

    public function canUpdate(mixed $actor, Pv $pv): bool;

    public function canSend(mixed $actor, Pv $pv): bool;

    public function canValidate(mixed $actor, Pv $pv): bool;

    public function canSign(mixed $actor, Pv $pv): bool;

    public function canDelete(mixed $actor, Pv $pv): bool;

    public function canDownload(mixed $actor, Pv $pv): bool;
}