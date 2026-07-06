# Commit — J5Q-C-2 — Branding e-mail paramétrable

## Objet

Ajouter une sous-section EasyAdmin `Branding e-mail` et appliquer un branding centralisé à tous les e-mails Hodina.

## Changements principaux

- Ajout des constantes de réglages e-mail dans `HodinaSetting`.
- Ajout du groupe `email_branding` / `Branding e-mail`.
- Ajout du contrôleur EasyAdmin `HodinaSettingEmailBrandingCrudController`.
- Ajout de la migration `Version20260625090000` pour initialiser les quatre réglages :
  - `email_branding_subject_prefix` ;
  - `email_branding_opening_formula` ;
  - `email_branding_closing_formula` ;
  - `email_branding_signature`.
- Ajout de `EmailBrandingService`.
- Application du branding aux e-mails :
  - commande créée ;
  - notification statut client ;
  - code réception client ;
  - code collecte vendeur ;
  - récap paiements livreurs admin ;
  - confirmation e-mail SymfonyCasts dormant.
- Mise à jour des templates HTML d'e-mail pour utiliser `emailBranding`.

## Règles métier / UX

- Le préfixe d'objet permet d'identifier l'environnement à la réception : `[Dev]`, `[Recette]`, ou rien en production.
- La formule de début est combinée avec le nom du destinataire quand il est disponible.
- La formule de fin et la signature sont affichées dans tous les e-mails.
- Le sujet stocké dans `EmailLog` est le sujet envoyé réellement.
- Les SMS restent inchangés.

## Validation attendue

- PHP lint OK.
- Migration jouée sans erreur.
- `doctrine:schema:validate` OK.
- `lint:twig` OK sur les templates e-mail.
- EasyAdmin > Réglages > Branding e-mail affiche les quatre réglages.
- Un sujet d'e-mail de recette est préfixé après configuration `[Recette]`.

---

# Validation recette

Tag : `j5q-c2-branding-email-recette`
Commit : `3586560`

Validation technique recette : OK.

- migration `Version20260625090000` jouée ;
- schema Doctrine OK ;
- Twig e-mails OK ;
- cache prod OK ;
- groupe `Branding e-mail` visible ;
- réglages `email_branding_*` présents.

Validation fonctionnelle e-mails réels : à compléter après configuration `[Recette]` et déclenchement des familles d'e-mails.
