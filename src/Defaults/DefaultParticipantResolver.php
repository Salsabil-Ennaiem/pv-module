<?php

namespace SalsabilEnnaiem\PvModule\Defaults;

use SalsabilEnnaiem\PvModule\Contracts\ParticipantResolver;

/**
 * Résolution des participants par défaut :
 * lit la clé 'participants' du contexte (liste d'identifiants utilisateurs
 * ou de tableaux {user_id, section_id}) et normalise en placements.
 */
class DefaultParticipantResolver implements ParticipantResolver
{
    public function resolveParticipants(mixed $actor, array $context): array
    {
        return $context['participants'] ?? [];
    }

    public function normalizePlacements(array $participants): array
    {
        $placements = [];

        foreach ($participants as $entry) {
            if (is_int($entry) || is_numeric($entry)) {
                $placements[] = [
                    'userId' => (int) $entry,
                    'sectionId' => 'signature',
                ];

                continue;
            }

            if (is_array($entry)) {
                $placements[] = [
                    'userId' => (int) ($entry['user_id'] ?? $entry['userId'] ?? 0),
                    'sectionId' => (string) ($entry['section_id'] ?? $entry['sectionId'] ?? 'signature'),
                ];
            }
        }

        return array_values(array_filter($placements, fn ($p) => $p['userId'] > 0));
    }
}