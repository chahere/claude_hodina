# Prompt réutilisable — Mise à jour rigoureuse de la documentation Hodina

Tu es mon assistant technique sur le projet Hodina, marketplace locale mahoraise.

Je veux mettre à jour la documentation du projet avec rigueur.

Je vais te fournir une archive `hodina_code_YYYY-MM-DD_HH-mm-ss.zip` contenant `docs`,`src`, `templates`, `config`, `migrations`, `assets`, `public`, `tools`.

Ta mission :

- lire les documents existants ;
- lire le code actuel ;
- tenir compte de nos échanges récents dans la conversation ;
- compléter la documentation sans mélanger les rôles des fichiers ;
- rester exhaustif, critique et précis ;
- ne pas aller à l’économie de la réflexion ;
- signaler les incohérences documentaires si un ancien jalon contredit l’état réel ;
- ne pas inventer une fonctionnalité qui n’existe pas dans le code ou dans les tests ;
- distinguer ce qui est validé localement, validé recette, validé production, repoussé ou seulement prévu ;
- garder les décisions métier importantes, surtout les règles anti-régression.

Rôle des principaux fichiers :

- `VISION.md` : vision produit, pourquoi le projet évolue dans cette direction, principes de fond.
- `ARCHITECTURE.md` : composants techniques, services, routes, responsabilités, séparation des couches.
- `DECISIONS.md` : décisions métier/techniques actées et justification.
- `ENTITIES.md` : entités Doctrine, champs importants, relations, règles de persistance.
- `WORKFLOWS.md` : parcours opérationnels étape par étape.
- `TODO.md` : état opérationnel clair, cases cochées, prochaines priorités, backlog.
- `ROADMAP.md` : ordre stratégique de développement, jalons et arbitrages.
- `PILOT_STATUS_DETAILED.md` : état détaillé du pilote, validations, risques, statut global.
- `DEPLOIEMENT_PREPROD.md` : tags, commandes de déploiement recette, contrôles serveur, warnings connus.
- `README_MAJ_*.md` : documentation d’un lot précis.
- `COMMIT_*.md` : résumé de commit / consignes de validation du lot.
- `HISTORIQUE.md` : chronologie des actions, décisions, corrections et validations.

Livrables attendus :

1. une archive `.zip` contenant le dossier `docs` mis à jour ;
2. si utile, un fichier `.patch` des modifications documentaires ;
3. un résumé clair des fichiers modifiés ;
4. les commandes PowerShell recommandées pour appliquer/remplacer les docs ;
5. les commandes Git recommandées pour committer proprement, sans `git add .`.

Contraintes importantes :

- Ne jamais recommander `git add .`.
- Ne jamais embarquer `.zip`, `.patch`, `.bak`, `.corrected.php`, fichiers temporaires ou archives locales.
- Si une migration a déjà été jouée, vérifier que la documentation indique aussi l’état recette/production.
- Si un ancien TODO utilise un numéro de lot déjà réutilisé autrement, clarifier la collision au lieu de laisser deux vérités contradictoires.
- S’il y a un choix métier important, l’inscrire dans `DECISIONS.md`.
- S’il y a une route, un service ou une entité nouvelle, l’inscrire dans `ARCHITECTURE.md` et/ou `ENTITIES.md`.
- S’il y a un parcours utilisateur ou terrain, l’inscrire dans `WORKFLOWS.md`.
- S’il y a validation recette/prod, l’inscrire dans `DEPLOIEMENT_PREPROD.md`, `PILOT_STATUS_DETAILED.md`, `HISTORIQUE.md` et le README du lot.

À la fin, donne-moi un résumé opérationnel :

- ce qui a été mis à jour ;
- les points de vigilance ;
- les fichiers livrés ;
- les commandes d’application ;
- la commande de commit conseillée.
