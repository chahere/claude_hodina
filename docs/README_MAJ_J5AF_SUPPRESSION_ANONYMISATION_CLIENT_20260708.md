# J5AF — Suppression pilote corrigée + anonymisation client

Date : 2026-07-08
Statut : codé, à valider en local puis en recette.

## Objectif

Corriger la suppression physique d'un client (« Supprimer pilote » dans EasyAdmin), cassée depuis l'ajout des conversations avec l'assistant IA (J5AD), et ajouter une anonymisation RGPD pour les vrais clients — l'historique métier (commandes, tickets support, conversations IA, paiements livreur) reste traçable, seule l'identité personnelle du client devient anonyme.

## Décision produit

Deux actions distinctes, volontairement différentes :

- **« Supprimer pilote »** (existant, corrigé) : suppression physique en cascade, réservée au nettoyage des comptes de test de la phase pilote. Toujours documentée comme telle dans l'interface — déconseillée pour un vrai client ayant un historique.
- **« Anonymiser »** (nouveau) : remplace les données personnelles par des valeurs génériques, bloque la connexion, conserve l'historique intact. C'est la voie recommandée pour un vrai client demandant la suppression de ses données.

Anonymisation non réversible par nature : aucune donnée d'origine n'est conservée ailleurs.

## Décision DB

Deux migrations :
- `Version20260708120000` : ajoute `customer.is_active` (booléen, défaut vrai) et `customer.anonymized_at` (nullable). Idempotente et défensive (vérifie l'existence des colonnes avant de les ajouter).
- `Version20260708130000` : corrective, trouvée lors du test local — l'`ALTER` de la première migration créait `is_active` en `TINYINT(1) NOT NULL DEFAULT 1`, ne correspondant pas exactement au mapping Doctrine (`#[ORM\Column]` sans `options`, qui attend `TINYINT NOT NULL` sans largeur ni `DEFAULT`), ce qui faisait échouer `doctrine:schema:validate`. Idempotente et défensive (vérifie le type réel via `information_schema`, no-op si déjà normalisé). Détail de l'incident : `docs/NOTES_ENVIRONNEMENT_LOCAL_20260707.md` §13.

Aucun changement de contrainte de clé étrangère : la correction de la suppression pilote se fait entièrement côté application (suppression explicite des conversations IA avant le client), pas en base — cohérent avec la façon dont les commandes et adresses sont déjà traitées dans `CustomerPilotCascadeDeleter` depuis l'origine du service.

## Périmètre technique

- `src/Service/CustomerPilotCascadeDeleter.php` — supprime désormais aussi les conversations IA (`ChatbotConversation`) du client avant de le supprimer ; les messages associés partent automatiquement (`ON DELETE CASCADE` déjà en place depuis J5AD, `migrations/Version20260706120000.php`). L'écran de confirmation affiche en plus le nombre de paiements livreur qui seraient supprimés en cascade si le client est aussi livreur (déjà en `ON DELETE CASCADE` depuis J5Q-C, non modifié, juste rendu visible).
- `src/Service/CustomerAnonymizerService.php` (nouveau) — scrub nom/prénom/téléphone/email/mot de passe/jeton de réinitialisation, suppression des adresses, passage `isActive=false` + `anonymizedAt`.
- `src/Security/CustomerUserChecker.php` (nouveau) — bloque la connexion des comptes `isActive=false` via le mécanisme standard `UserCheckerInterface` de Symfony, câblé dans `config/packages/security.yaml` (`firewalls.main.user_checker`).
- `src/Controller/Admin/CustomerCrudController.php` — nouvelle action « Anonymiser » (mêmes garde-fous que « Supprimer pilote » : jamais sur un compte `ROLE_ADMIN`, jamais sur son propre compte connecté) ; champs `isActive`/`anonymizedAt` affichés en lecture seule (jamais éditables directement, pour ne pas désynchroniser l'état).
- `templates/admin/customer/anonymize_confirm.html.twig` (nouveau) — écran de confirmation, même structure que celui de la suppression pilote.
- `templates/admin/customer/pilot_cascade_delete.html.twig` — compteurs conversations IA / paiements livreur ajoutés.
- `src/Entity/Customer.php` — champs `isActive`/`anonymizedAt` (additif uniquement).

## Hors périmètre volontaire

- Pas de « dé-anonymisation » : par nature irréversible.
- Pas de suppression/anonymisation en masse (batch) : action unitaire depuis la fiche client uniquement.
- Pas d'export RGPD (« droit à la portabilité ») : hors périmètre de cette demande.
- Pas de changement du comportement de « Supprimer pilote » sur les relations déjà correctement gérées (tickets support, avis clients, logs e-mail) : elles étaient déjà en `ON DELETE SET NULL`, non touchées.

## Anti-régression

- Aucun fichier des lots fermés (J5Z/J5AB/J5AC/J5AA) modifié.
- `Customer.php` et `CustomerCrudController.php` modifiés de façon strictement additive (nouveaux champs, nouvelle action) — aucun champ ni action existante retirée.
- Le flux de connexion existant (`AppAuthenticator`) n'est pas modifié : le blocage des comptes désactivés passe par le mécanisme standard Symfony (`UserCheckerInterface`), invoqué automatiquement par le firewall avant `AppAuthenticator`.
- Les relations déjà en `SET NULL`/`CASCADE` (support, avis, e-mails, paiements livreur) ne sont pas touchées par la correction de la suppression pilote — seule la relation `ChatbotConversation`, seule relation `NOT NULL` non gérée, est corrigée.

## Commandes locales

```powershell
cd D:\hodina\claude_hodina
git pull origin <branche>
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
```

## Tests locaux recommandés

1. EasyAdmin > Utilisateurs : un client de test ayant échangé au moins une fois avec l'assistant IA connecté (`/mon-compte/assistant`) → cliquer « Supprimer pilote ». Doit réussir (avant ce correctif : 500 Internal Server Error).
2. Vérifier en base que la conversation et ses messages ont disparu (`chatbot_conversation`, `chatbot_message`).
3. Un autre client de test, avec commande + ticket support + conversation IA → cliquer « Anonymiser », confirmer.
4. Vérifier : nom/prénom/téléphone/email remplacés par des valeurs génériques, `isActive` à faux, `anonymizedAt` renseigné, adresses supprimées.
5. Vérifier que la commande, le ticket support et la conversation IA existent toujours, rattachés au client anonymisé (juste son identité a changé).
6. Tenter de se connecter avec les anciens identifiants du compte anonymisé → doit échouer avec le message « Ce compte est désactivé. ».
7. Tenter d'anonymiser ou de supprimer un compte `ROLE_ADMIN` → doit être refusé avec un message clair.
8. Tenter de s'anonymiser ou de se supprimer soi-même (connecté en tant qu'admin sur sa propre fiche) → doit être refusé.
9. Si le client de test est aussi livreur avec des paiements enregistrés : vérifier que le compteur « paiement(s) livreur » sur l'écran de suppression pilote est cohérent avant de confirmer.
10. `doctrine:schema:validate` reste vert après la migration.

## Limites connues du MVP (non faites, volontairement)

- Pas d'anonymisation automatique programmée (ex. après X mois d'inactivité) : action manuelle uniquement.
- Pas de distinction « anonymisation partielle » (ex. garder l'e-mail pour recontacter) : tout ou rien.
- Pas de filtre EasyAdmin dédié « comptes anonymisés » dans la liste (le champ `isActive` est visible mais non filtrable pour l'instant).

## Points à prévoir plus tard

- Si besoin d'un export RGPD (« droit à la portabilité »), prévoir un lot dédié.
- Si le volume de comptes anonymisés devient significatif, envisager un filtre EasyAdmin dédié.
- Revoir la dette technique plus large sur `Seller.isActive`, qui existe déjà en base mais n'est exposé/lu nulle part dans le code actuel (hors périmètre de ce lot, signalé pour information).

## Commit conseillé

Deux commits distincts, une préoccupation chacun :
```text
fix(customer): la suppression pilote gere desormais les conversations IA
feat(customer): anonymisation RGPD (nouvelle action admin + blocage connexion)
```

## Validation recette / production

À faire après validation locale (cf. § Tests locaux) :
1. Rejouer les tests manuels en recette avec un client de test ayant une conversation IA existante.
2. Vérifier `doctrine:schema:validate` en recette après migration.
3. Tag `recette-j5af-suppression-anonymisation-client-*` après validation, suivant la procédure habituelle (`tools/deploy-hodina-by-tag.sh`).
