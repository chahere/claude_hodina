# README mise à jour — J5H / J5I

## Résumé

Le 13/06/2026, deux évolutions majeures ont été décidées avant la mise en production :

```text
J5I : préouverture commerciale avec compte à rebours et capture e-mail
J5H : e-mails transactionnels avec SMTP o2switch
```

## Ordre recommandé

```text
1. J5I — préouverture
2. validation recette
3. mise en production protégée
4. J5H-A — e-mail automatique de création de commande
5. J5H-B/C — e-mails manuels et statuts
```

## Documents mis à jour

```text
ROADMAP.md
TODO.md
DECISIONS.md
ARCHITECTURE.md
ENTITIES.md
WORKFLOWS.md
VISION.md
DEPLOIEMENT_PREPROD.md
PILOT_STATUS_DETAILED.md
PATCH_GUIDELINES.md
COMMIT_J5H_PREP.md
COMMIT_J5I_PREP.md
```

## Rappel critique

J5I doit bloquer côté serveur. Une bannière ou un bouton désactivé ne suffit pas.

J5H ne doit jamais commiter de secret SMTP et ne doit jamais bloquer une commande si l'e-mail échoue.


---

# Complément 13/06/2026 — J5I livré

J5I n'est plus seulement préparé : le jalon est développé, commité, poussé et déployé en recette.

## Résultat

```text
Préouverture : OK
Compte à rebours : OK
Capture e-mail : OK
Blocage panier / checkout : OK
Réglages EasyAdmin : OK
```

## Attention production

Le déploiement recette a révélé un ordre de migration à corriger avant production.

Ne pas considérer la production comme prête tant que ce point n'est pas traité.

---

# Complément 13/06/2026 — J5J remplace le périmètre J5I pour le pilotage commerce

J5I reste l'historique de la préouverture, mais le mécanisme opérationnel à conserver est désormais J5J.

J5J généralise :

```text
- préouverture ;
- maintenance commerciale ;
- fermeture temporaire ;
- tests production par rôle.
```

Conséquence : les nouveaux développements doivent utiliser `commerce_*` et `ROLE_COMMERCE_TESTER`, pas les anciens paramètres J5I.

---

# Complément 15/06/2026 — J5H-A validé en recette

J5H-A est désormais livré et validé en recette.

## Résultat

```text
E-mail automatique de création de commande : OK
SMTP o2switch contact@hodina.fr : OK
EmailLog EasyAdmin : OK
Messenger async : OK
Cron Messenger : OK
Bouton Envoyer manuellement : OK
Template avec articles / quantités / frais / total : OK
```

## Chronologie courte

```text
1. Ajout EmailLog + OrderEmailService + template HTML.
2. Remplacement de l'ancien envoi no-reply du checkout.
3. Validation locale.
4. Merge dans pilot/j5j-commerce-mode-role-tester.
5. Déploiement recette.
6. Configuration SMTP o2switch.
7. Migration email_log.
8. Diagnostic Messenger : e-mails bloqués tant que worker non consommé.
9. Validation du cron Messenger.
10. Ajout du bouton manuel EasyAdmin.
11. Correction du template pour afficher articles, quantités et frais.
12. Validation finale recette avec git status propre.
```

## Rappel critique

Ne pas confondre :

```text
EmailLog SENT = e-mail accepté par Symfony / Messenger
Réception client = dépend du worker Messenger et du SMTP
```

La recette est validée parce que le cron Messenger consomme la file toutes les minutes.
