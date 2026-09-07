<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modèle utilisateur de l'app hôte
    |--------------------------------------------------------------------------
    | Le module référence les utilisateurs (créateur, validateurs, signataires)
    | via ce modèle. Ne jamais mettre un modèle du module ici.
    */

    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    | prefix, name_prefix et middleware des routes du module.
    | L'app hôte peut les personnaliser (ex: 'admin/documents').
    */

    'routes' => [
        'prefix'      => 'pv-module',
        'name_prefix' => 'pv-module.',
        'middleware'  => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Stockage des signatures
    |--------------------------------------------------------------------------
    | 'local' = storage/app (privé, recommandé en production)
    | 'public' = storage/app/public (accessible par URL, pratique en dev/démo)
    */

    'storage_disk' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Contrats : personnalisation par l'app hôte
    |--------------------------------------------------------------------------
    | L'app hôte remplace ces classes par les siennes (implémentation des
    | interfaces SalsabilEnnaiem\PvModule\Contracts\*). C'est ainsi que le module
    | s'adapte à la RBAC, aux types de PV et au domaine de l'hôte.
    */

    'can_manage_pv'        => \SalsabilEnnaiem\PvModule\Defaults\DefaultPvRules::class,
    'approval_rules'       => \SalsabilEnnaiem\PvModule\Defaults\DefaultApprovalRules::class,
    'participant_resolver' => \SalsabilEnnaiem\PvModule\Defaults\DefaultParticipantResolver::class,

    /*
    |--------------------------------------------------------------------------
    | Signature — validation des fichiers
    |--------------------------------------------------------------------------
    */

    'max_signature_size_kb' => 2048,
    'allowed_signature_mimes' => ['image/jpeg', 'image/png', 'image/gif'],

    /*
    |--------------------------------------------------------------------------
    | Types de PV du module
    |--------------------------------------------------------------------------
    | Libre : l'hôte peut déclarer ses types ('commission', 'jury', ...).
    | Le type générique par défaut suffit pour une utilisation simple.
    */

    'types' => ['pv'],

];