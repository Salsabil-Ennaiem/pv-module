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

## Prérequis (obligatoire)

Le module exige que l'utilisateur soit **connecté** : toutes les routes sont protégées par le middleware `auth`. Votre application doit donc fournir :

1. **Une authentification** — Breeze, Jetstream, ou votre propre système.
2. **Une route nommée `login`** — `Route::get('/login', ...)->name('login')`. Avec Breeze c'est automatique.

### Symptôme typique sans route de login

```
Symfony\Component\Routing\Exception\RouteNotFoundException
Route [login] not defined.
GET /pv-module → 500
```

- **Cause** : un visiteur non connecté accède au module ; Laravel veut le rediriger vers la route de login, qui n'existe pas dans votre app.
- **Solution** : créer une route nommée `login` (ou installer pack d'authentifier). Le middleware `auth` du module fonctionne alors normalement.
- **Si votre app gère l'auth différemment**, remplacez le middleware par le vôtre dans `config/pv-module.php` :

```php
'routes' => [
    'prefix'      => 'pv-module',
    'name_prefix' => 'pv-module.',
    'middleware'  => ['web', 'your.auth'], // par défaut : ['web', 'auth']
],
```

## Démarrage rapide

Ordre exact pour une première installation sans erreur  :

```bash
# 1. Application Laravel avec authentification
composer create-project laravel/laravel mon-app
--- installer votre pack pour authentifier ou bien le crée ---
npm install && npm run build
php artisan migrate

# 2. Installer le module
composer require salsabil-ennaiem/pv-module
php artisan migrate
php artisan db:seed --class="SalsabilEnnaiem\PvModule\Seeders\DefaultPvTemplateSeeder"
php artisan storage:link

# 3. Se connecter puis accéder
# http://mon-app.test/pv-module
```

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

```php
// config/pv-module.php
return [
    'user_model' => \App\Models\User::class,   // modèle de l'app hôte (jamais un modèle du module)

    'routes' => [
        'prefix'      => 'pv-module',           // URL du module
        'name_prefix' => 'pv-module.',
        'middleware'  => ['web', 'auth'],       // protection des routes
    ],

    'storage_disk' => 'local',                  // 'local' = privé (prod) | 'public' = accessible (démo)

    // Implémentations des contrats — remplaçables par vos propres classes
    'can_manage_pv'        => \SalsabilEnnaiem\PvModule\Defaults\DefaultPvRules::class,
    'approval_rules'       => \SalsabilEnnaiem\PvModule\Defaults\DefaultApprovalRules::class,
    'participant_resolver' => \SalsabilEnnaiem\PvModule\Defaults\DefaultParticipantResolver::class,

    'max_signature_size_kb' => 2048,
    'allowed_signature_mimes' => ['image/jpeg', 'image/png', 'image/gif'],

    // Types de documents déclarables
    'types' => ['pv'],                          // 'pv', 'commission', 'jury', 'deliberation'…
];
```

## Personnalisation pour vos besoins

Le module est conçu pour s'adapter **sans modification de son code**. Voici les 4 leviers, du plus simple au plus avancé :

### 1. Régler la config (sans coder)

- **URL / routes** : `routes.prefix`, `routes.middleware` — ex. mettre le module sous `/admin/documents`.
- **Types de documents** : ajouter `'commission'`, `'jury'`… dans `types`. Chaque type aura ses propres PV, templates et PDF.
- **Signature** : tailles max et formats d'image acceptés.
- **Stockage** : `storage_disk` (privé recommandé en prod, `public` pratique en dev).

### 2. Remplacer les contrats (votre logique métier)

Chaque colonne vertébrale du moteur est une interface dans `src/Contracts/`, implémentable par vos propres classes :

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

| Contrat | Rôle | Exemple de personnalisation |
|---|---|---|
| `CanManagePv` | RBAC : qui peut créer, envoyer, valider, signer, supprimer… | N'autoriser que les rôles `admin` / `secretaire` |
| `ApprovalRules` | Règles de passage à « valide » | Exiger X validations avant signature |
| `ParticipantResolver` | Résolution/normalisation des participants | Résoudre les participants depuis une table métier |

Implémentez l'interface, adaptez les signatures aux méthodes du contrat, et un **simple `bind`** suffit : le module utilise vos classes partout.

### 3. Surcharger les vues et le PDF

Première étape, récupérez les vues du module dans votre app, puis modifiez-les librement (marque, CSS, champs) :

```bash
php artisan vendor:publish --tag=pv-views
```

Le template PDF est généré via mPDF et personnalisable de la même façon (publié avec les vues ou remplacé dans votre code).

### 4. Gérer les templates de documents

Les templates réutilisables (le contenu de base de vos PV) sont gérés par le module : le seeder fournit un template par défaut, et votre application peut en créer/sélectionner d'autres via l'interface du module (pour chaque type déclaré).

## Routes

Prefix par défaut : `pv-module` (configurable via `routes.prefix`). Les routes chargent les contrôleurs sous `SalsabilEnnaiem\PvModule\Http\Controllers`.

## Langues

Chargées automatiquement en JSON (`ar.json`, `en.json`, `fr.json`). Publiez-les pour les surcharger :

```bash
php artisan vendor:publish --tag=pv-lang
```

## Tests

```bash
composer test
```

## Licence

Publié sous licence MIT. Voir `LICENSE`.