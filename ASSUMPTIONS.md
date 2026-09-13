# ASSUMPTIONS.md — Registre d'hypothèses de `salsabil-ennaiem/pv-module`

> Registre des décisions par **hypothèse par défaut** prises pour la livraison `v1.0.0`.
> Chaque hypothèse dispose d'un identifiant fixe (**ID-xx**) et d'un **mécanisme de bascule**
> pour être levée sans rupture lorsque l'information réelle sera connue.

---

## ID-01 — Propriété intellectuelle / namespace (R1)

**Constat** : la plateforme et le package sont rattachés à la personne `salsabil-ennaiem`
(namespace `SalsabilEnnaiem\PvModule`).

**Hypothèse par défaut** : le transfert de propriété / la cession se fait par **contrat juridique**
séparé ; tant qu'aucune consigne explicite ne l'interdit, **le namespace n'est pas renommé**.

**Ce que cette hypothèse active**
- Aucune modification des métadonnées d'auteur/licence dans `composer.json`, `LICENSE` ni des
  classes du namespace `SalsabilEnnaiem\PvModule`.

**Mécanisme de bascule**
- **Quand** : en réception (P9/P10), si le contrat impose un namespace ou un auteur différent.
- **Comment** : renommer `SalsabilEnnaiem\PvModule` → nouveau namespace, mettre à jour
  `composer.json` (autoload, providers), les routes/contrats et les publications `vendor:publish`.
  Ce renommage est **mécanique** (tout est PSR-4) et doit être accompagné d'un diff + tests verts.
- **Conséquence du choix** : ce tag `v1.0.0` porte le namespace actuel ; le **tag de livraison final**
  (nom/licence/décision R1) sera posé en P10 sur le dépôt de livraison.

---

## ID-02 — Conformité de la signature (R2)

**Constat** : le CDC (§1.13) exige une **trace de conformité** pour les signatures.

**Hypothèse par défaut** : la conformité actuelle est une **signature simple**
(`simple_image` : image apposée + horodatage), sans chiffrement ni certificat.

**Ce que cette hypothèse active**
- Chaque enregistrement de signature stocke en base (`pv_module_signatures`) :
  `signed_mechanism = 'simple_image'` et `signed_at = now()`.
- Le PDF généré reporte, pour chaque signataire validé : le **mécanisme** et l'**horodatage**.
- Config : `signature_mechanism` (défaut `simple_image`).

**Mécanisme de bascule**
- **Quand** : passage à une **signature qualifiée** (eIDAS / PKI / certificat) en P8
  (moteur de workflow) ou P9/P10.
- **Comment** : introduire une `SignatureStrategy` (simple / qualifiée) ; le champ
  `signed_mechanism` et l'horodatage `signed_at` sont **déjà présents** — la bascule se fait
  par remplacement de la stratégie et ajout des colonnes de preuve (certificat, hash) sans
  migration destructive. Aucun changement RTU dans `PvService` : la trace est portée par
  `SignatureService` + le rendu (templates).

---

## ID-03 — Rendu RTL / arabe (R3)

**Constat** : un PV peut être rédigé en **arabe** (long texte, ≥ 200 caractères), le CDC exige
un visuel correct dans le PDF et l'aperçu HTML.

**Hypothèse par défaut** : la directionnalité du document est pilotée par la **locale par défaut**
du package (`config('pv-module.default_locale')`, défaut `fr`) ; pour une locale de
`pv-module.rtl_locales` (`ar`, `he`, `fa`, `ur`), le template bascule en
`dir="rtl"` / `direction: rtl` / `unicode-bidi: embed` (mPDF : `autoScriptToLang`,
`autoLangToFont`, `autoArabic`, police `dejavusans`).

**Ce que cette hypothèse active**
- Tout le RTL vit dans les **templates Blade du package** (`pdfs/pv_template.blade.php`).
- **Aucune** logique métier RTL dans `PvService` ni `PdfService` (seule la config locale est lue
  au rendu).

**Mécanisme de bascule**
- **Quand** : besoin d'un rendu multilingue en fonction du document (et non d'une locale globale).
- **Comment** : passer la locale à `PdfService` en paramètre de rendu (dérive de
  `config('pv-module.default_locale')`) ou déclarer `direction: rtl` dans les `styles` d'une
  section du template ; la mécanique template (balises `dir`, CSS `direction`) ne change pas.

---

## Autres hypothèses notables

| ID | Sujet | Hypothèse par défaut | Mécanisme de bascule |
|---|---|---|---|
| ID-04 | App hôte toujours authentifiée | Routes protégées par middleware `auth` (`routes.middleware`) | Remplacer le middleware via config | 
| ID-05 | Stockage des signatures | `storage_disk = local` (privé) | Passer à `public`/S3 via config |
| ID-06 | Types de documents | Seul `pv` est livré par défaut | Déclarer d'autres types dans `config('pv-module.types')` |