# COMMIT J5H PREP — E-mails transactionnels Hodina

## Statut

Préparation fonctionnelle et technique décidée le 13/06/2026.

Ce fichier documente le futur jalon J5H. Il ne signifie pas que le code est déjà livré.

## Objectif général

Mettre en place un socle e-mail propre pour Hodina, basé sur Symfony Mailer et le SMTP o2switch.

## Première priorité J5H-A

Envoyer automatiquement au client un e-mail HTML de récapitulatif dès la création de commande.

Important : la commande est reçue, mais pas encore validée admin.

Message métier attendu :

```text
Nous avons bien reçu votre commande.
Elle est en attente de validation par l'équipe Hodina.
Le paiement se fera à la livraison pendant le pilote.
```

## Architecture prévue

```text
CustomerOrder
→ OrderEmailService
→ Symfony Mailer
→ SMTP o2switch
→ EmailLog
```

## Règle critique

L'e-mail ne doit jamais bloquer la création de commande.

Si SMTP échoue :

```text
commande créée
EmailLog FAILED
admin pourra agir
```

## Suites prévues

```text
J5H-B : renvoi manuel depuis EasyAdmin
J5H-C : e-mails automatiques sur changements d'état
```

---

# Mise à jour 15/06/2026

Ce fichier reste l'historique de préparation. Le jalon J5H-A est maintenant livré et validé en recette.

Le fichier de référence livré est désormais :

```text
COMMIT_J5H.md
```
