# COMMIT — J5AF Suppression pilote corrigée + anonymisation client

Date : 2026-07-08

## Commits

```text
fix(customer): la suppression pilote gere desormais les conversations IA
feat(customer): anonymisation RGPD (nouvelle action admin + blocage connexion)
docs(j5af): commit et README de mise à jour du lot
fix(customer): migration corrective is_active (largeur/DEFAULT non conformes au mapping Doctrine)
docs(j5af): documenter l'incident migration is_active (piège n°12)
```

## Tags

Aucun tag recette/production créé à ce stade : le lot n'a pas encore été validé en environnement réel (voir `README_MAJ_J5AF_SUPPRESSION_ANONYMISATION_CLIENT_20260708.md` § Validation). À poser après recette : `recette-j5af-suppression-anonymisation-client-20260708`.

## Résumé

Corrige la suppression physique d'un client (« Supprimer pilote » dans EasyAdmin), en échec (500) depuis que le lot J5AD a ajouté une relation `NOT NULL` non gérée entre `ChatbotConversation` et `Customer`. Ajoute, en complément, une anonymisation RGPD (nouvelle action « Anonymiser ») pour les vrais clients : scrub des données personnelles, blocage de connexion, conservation intégrale de l'historique métier (commandes, tickets support, conversations IA, paiements livreur).

## Fichiers principaux

**Correctif suppression pilote**
- `src/Service/CustomerPilotCascadeDeleter.php` : supprime désormais les `ChatbotConversation` du client avant lui (les `ChatbotMessage` liés partent automatiquement, `ON DELETE CASCADE` déjà en place depuis J5AD) ; ajoute un comptage informatif des paiements livreur.
- `src/Controller/Admin/CustomerCrudController.php` : message flash mis à jour avec les nouveaux compteurs.
- `templates/admin/customer/pilot_cascade_delete.html.twig` : compteurs conversations IA / paiements livreur ajoutés à l'écran de confirmation.

**Anonymisation (nouveau)**
- `migrations/Version20260708120000.php` : ajoute `customer.is_active` et `customer.anonymized_at` (idempotent, défensif).
- `migrations/Version20260708130000.php` : corrective — normalise `customer.is_active` en `TINYINT NOT NULL` (sans largeur ni `DEFAULT`) pour correspondre exactement au mapping Doctrine ; trouvé via `doctrine:schema:validate` en test local, cf. `docs/NOTES_ENVIRONNEMENT_LOCAL_20260707.md` §13.
- `src/Entity/Customer.php` : champs `isActive`/`anonymizedAt` + accesseurs, `isAnonymized()`.
- `src/Service/CustomerAnonymizerService.php` (nouveau) : scrub nom/prénom/téléphone/email/mot de passe/jeton reset, suppression des adresses, `isActive=false` + `anonymizedAt`.
- `src/Security/CustomerUserChecker.php` (nouveau) : bloque la connexion des comptes `isActive=false`.
- `config/packages/security.yaml` : câble `CustomerUserChecker` sur le firewall `main`.
- `src/Controller/Admin/CustomerCrudController.php` : nouvelle action « Anonymiser » (mêmes garde-fous que « Supprimer pilote »), champs `isActive`/`anonymizedAt` en lecture seule.
- `templates/admin/customer/anonymize_confirm.html.twig` (nouveau) : écran de confirmation.

**Documentation**
- `docs/COMMIT_J5AF_SUPPRESSION_ANONYMISATION_CLIENT_20260708.md` (ce fichier)
- `docs/README_MAJ_J5AF_SUPPRESSION_ANONYMISATION_CLIENT_20260708.md`

## Root cause du bug corrigé

Migration `Version20260706120000` (J5AD) crée `chatbot_conversation.customer_id INT NOT NULL` avec une FK **sans clause `ON DELETE`** (RESTRICT par défaut MariaDB/InnoDB), contrairement à `support_ticket.customer_id` (nullable, `ON DELETE SET NULL`) créée dans la même migration. `CustomerPilotCascadeDeleter` existait déjà avant ce lot et n'avait jamais été mis à jour pour cette nouvelle relation — d'où le blocage systématique dès qu'un client a échangé au moins une fois avec l'assistant IA.

## Décisions importantes

- **Deux actions, deux usages distincts.** « Supprimer pilote » reste une suppression physique en cascade, explicitement réservée au nettoyage des comptes de test (texte mis à jour dans l'écran de confirmation pour le rappeler). « Anonymiser » est la voie recommandée pour un vrai client — non réversible, mais garde l'historique métier intact.
- **Correction par le code, pas par le schéma.** Pas de nouvelle contrainte `ON DELETE CASCADE` sur `chatbot_conversation.customer_id` : la suppression explicite en PHP suit le même principe déjà appliqué aux commandes et adresses dans ce service, sans toucher à une contrainte déjà déployée en recette.
- **Blocage de connexion via le mécanisme standard Symfony** (`UserCheckerInterface`), pas de logique ad hoc dans `AppAuthenticator`.
- **Aucune PII résiduelle dans l'historique** : vérifié que `CustomerOrder` ne stocke aucune copie du nom/téléphone/e-mail du client (seule l'adresse de livraison est déjà instantanée séparément, lot antérieur J5G-E0) — anonymiser le client ne laisse pas de PII orpheline ailleurs.
