# salsabil-ennaiem/pv-module

Moteur générique de **documents de travail** pour Laravel : workflow complet (brouillon → en attente → valide), versions, signatures, notifications et génération PDF.

Bien que nommé `pv-module` (routes/vues « procès-verbaux »), c'est un **moteur de documents réutilisable** : `config('pv-module.types')` permet de déclarer n'importe quel type (commission, jury, délibération, conventions…). Aujourd'hui seul `pv` est fourni par défaut.

## Fonctionnalités

- **Workflow complet** : création, brouillon, envoi aux participants, validation, signature, archivage.
- **Versions & historiques** : chaque modification enregistre une version consultable.
- **Signatures & validation** : signature dessinée/téléchargée, règles d'approbation configurables.
- **Notifications** par email + canal `database`.
- **Génération PDF** via mPDF (template personnalisable).
- **Templates réutilisables** + seeder par défaut.
- **Personnalisable par contrats** : l'hôte adapte RBAC, résolution des participants et règles d'approbation sans toucher au code.
- **Multilingue** : fr / ar / en, vues / config / migrations publishables.
- **Multi-types** : déclarable dans `config/pv-module.php` (commission, jury, délibération…).

## Installation

```bash
composer require salsabil-ennaiem/pv-module
```

## Configuration

Publiez la config, les migrations, les vues et la langue :

```bash
php artisan vendor:publish --provider="SalsabilEnnaiem\PvModule\PvModuleServiceProvider"
```

Ou par tags, selon vos besoins :

```bash
php artisan vendor:publish --tag=pv-config        # config/pv-module.php
php artisan vendor:publish --tag=pv-migrations     # database/migrations
php artisan vendor:publish --tag=pv-views          # resources/views/vendor/pv-module
php artisan vendor:publish --tag=pv-lang           # lang/vendor/pv-module
```

### Migrations

```bash
php artisan migrate
```

### Seeder des templates par défaut

```bash
php artisan db:seed --class="SalsabilEnnaiem\PvModule\Seeders\DefaultPvTemplateSeeder"
```

## Configuration

```php
// config/pv-module.php
return [
    'user_model' => \App\Models\User::class,

    'routes_prefix' => 'pv-module',
    'storage_disk'  => 'public',

    // Implémentations des contrats — remplaçables par vos propres classes
    'can_manage_pv'        => \SalsabilEnnaiem\PvModule\Defaults\DefaultPvRules::class,
    'approval_rules'       => \SalsabilEnnaiem\PvModule\Defaults\DefaultApprovalRules::class,
    'participant_resolver' => \SalsabilEnnaiem\PvModule\Defaults\DefaultParticipantResolver::class,

    'max_signature_size_kb' => 2048,
    'allowed_signature_mimes' => ['image/jpeg', 'image/png', 'image/gif'],

    // Types de documents déclarables (commission, jury, délibération…)
    'types' => ['pv'],
];
```

## Personnalisation par contrats

L'hôte peut substituer les implémentations dans son propre `AppServiceProvider` (ou via la config) :

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->bind(
        \SalsabilEnnaiem\PvModule\Contracts\ParticipantResolver::class,
        \App\Services\MyParticipantResolver::class,
    );

    $this->app->bind(
        \SalsabilEnnaiem\PvModule\Contracts\CanManagePv::class,
        \App\Services\MyPvRules::class,
    );

    $this->app->bind(
        \SalsabilEnnaiem\PvModule\Contracts\ApprovalRules::class,
        \App\Services\MyApprovalRules::class,
    );
}
```

Les contrats disponibles dans `src/Contracts/` :

| Contrat | Rôle |
|---|---|
| `CanManagePv` | RBAC : qui peut créer, envoyer, valider, signer, supprimer… |
| `ApprovalRules` | Règles de passage à « valide ». |
| `ParticipantResolver` | Résolution/normalisation des participants (user_id, sections…). |

## Routes

Prefix par défaut : `pv-module` (configurable via `routes_prefix`). Les routes chargent les contrôleurs sous `SalsabilEnnaiem\PvModule\Http\Controllers`.

## Langues

Chargées automatiquement en JSON (`ar.json`, `en.json`, `fr.json`). Publiez-les pour les surcharger.

## Tests

```bash
composer test
```

## Licence

Publié sous licence MIT. Voir `LICENSE`.
