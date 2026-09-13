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
    | Signature — trace de conformité (R2)
    |--------------------------------------------------------------------------
    | Mécanisme de signature enregistré sur la table `pv_module_signatures`
    | (colonne `signed_mechanism`) et reporté dans le PDF généré.
    | 'simple_image' = image apposée + horodatage (`signed_at`).
    | Architecture prête pour un mécanisme qualifié via SignatureStrategy (P8).
    */

    'signature_mechanism' => 'simple_image',

    /*
    |--------------------------------------------------------------------------
    | Rendu PDF — directionnalité (R3)
    |--------------------------------------------------------------------------
    | default_locale : langue par défaut utilisée pour baliser les documents
    | (attribut html `lang`).
    | rtl_locales : locales dont le contenu doit être rendu de droite à gauche
    | (attribut html `dir`, CSS `direction` sur les sections du template).
    */

    'default_locale' => 'fr',
    'rtl_locales' => ['ar', 'he', 'fa', 'ur'],

    /*
    |--------------------------------------------------------------------------
    | Types de PV du module
    |--------------------------------------------------------------------------
    | Libre : l'hôte peut déclarer ses types ('commission', 'jury', ...).
    | Le type générique par défaut suffit pour une utilisation simple.
    */

    'types' => ['pv'],

];