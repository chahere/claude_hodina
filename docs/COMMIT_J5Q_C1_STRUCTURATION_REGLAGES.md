# Commit — J5Q-C-1 — Structuration des réglages en groupes

## Objet

Structurer `HodinaSetting` pour éviter que les paramètres globaux Hodina restent dans une liste EasyAdmin en vrac.

## Changements principaux

- Ajout des métadonnées de groupe dans `HodinaSetting` :
  - `groupKey` ;
  - `groupLabel` ;
  - `sortOrder` ;
  - `isEditable` ;
  - `isSensitive`.
- Ajout de la migration `Version20260624233000`.
- Classement initial des réglages existants dans les groupes :
  - Général ;
  - Commerce & commandes ;
  - Livraison & logistique ;
  - Notifications ;
  - Paiements ;
  - Technique / maintenance.
- Réorganisation du menu EasyAdmin `Réglages` avec une vue experte `Tous les paramètres` et des sous-vues filtrées par groupe.
- Mise à jour de `SalesOpeningService::ensureDefaultSettings()` pour créer les réglages commerce avec leur groupe et leur ordre d'affichage.

## Règles métier / UX

- `HodinaSetting` reste la source centrale des réglages globaux.
- Les écrans EasyAdmin spécialisés ne créent pas de nouvelle table : ils filtrent la même entité par `groupKey`.
- La vue `Tous les paramètres` reste disponible pour l'admin expert.
- Le branding e-mail est volontairement exclu du lot ; il sera traité dans `J5Q-C-2`.

## Validation attendue

- PHP lint OK sur l'entité, les contrôleurs EasyAdmin et le service modifié.
- Migration jouée sans erreur.
- `doctrine:schema:validate` OK.
- EasyAdmin affiche les sous-sections de réglages.
- Aucun e-mail n'est modifié par ce lot.


## Complément J5Q-C-1 — Paramètres paiements livreurs

Le groupe `Paiements` contient désormais les réglages opérationnels du module de rémunération livreur :

- `courier_payouts_enabled` : active ou suspend les générations de paiements livreurs ;
- `courier_payout_cron_enabled` : autorise la génération cron `--auto-due` ;
- `courier_payout_admin_recap_enabled` : autorise l’envoi du récapitulatif admin après génération réelle ;
- `courier_payout_frequency` : fréquence métier, valeur pilote `semi_monthly` pour la quinzaine.

Ces réglages restent des garde-fous : ils ne valident pas et ne marquent jamais un paiement comme payé automatiquement.
