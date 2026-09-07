<?php

namespace SalsabilEnnaiem\PvModule\Contracts;

/**
 * Contrat de résolution des participants/validateurs.
 * L'app hôte fournit la liste des validateurs d'un PV et leur placement.
 */
interface ParticipantResolver
{
    /**
     * Résoudre les participants à partir du contexte de création.
     *
     * @return array<int, int|array{user_id?:int, section_id?:string}>
     */
    public function resolveParticipants(mixed $actor, array $context): array;

    /**
     * Normaliser la liste des participants en placements structurés.
     *
     * @param array<int, int|array{user_id?:int, section_id?:string}> $participants
     * @return array<int, array{userId:int, sectionId:string}>
     */
    public function normalizePlacements(array $participants): array;
}