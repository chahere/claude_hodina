# README mise à jour — J5G-B4 v11 production validée

Date : **19/06/2026**  
Tag final : `j5g-b4-20260618-v11`  
Commit final : `b998b63 fix(admin): avoid collapsing menu items matching section names`

## Résumé

La production Hodina est validée sur la version v11.

Cette version clôture la séquence J5G-B4 étendue : logistique avancée, script de déploiement robuste, assets prod, admin mobile, Ajax panier et e-mails réels.

## Historique des tags

```text
j5g-b4-20260618-v7  : stabilisation MEP, backup DB, binaires, prod OK
j5g-b4-20260618-v8  : compilation AssetMapper automatique
j5g-b4-20260618-v9  : public/assets autorisé comme dossier généré
j5g-b4-20260618-v10 : Ajax ajout panier
j5g-b4-20260618-v11 : correctif menu Utilisateurs, tag final validé
```

## Tests validés

- Recette OK.
- Production OK.
- Panier Ajax catalogue OK.
- Panier Ajax fiche produit OK.
- Pastille panier OK.
- Admin menu repliable OK.
- Menu Utilisateurs corrigé OK.
- Réglages / Préouverture accessibles.
- Miniatures EasyAdmin OK.
- E-mail commande reçu.
- Cron Messenger actif.
- Doctrine schema OK.
- Pas de plantage admin hors MEP.

## Incident admin pendant MEP

Un plantage a été vu pendant la MEP, puis non reproduit après rechargement propre / fenêtre privée.

Les logs PHP visibles ne contenaient pas d'erreur récente liée à l'incident. L'incident est classé transitoire.

## Point MAILER_DSN

`MAILER_DSN=null://null` ne livre aucun mail réel. Après configuration SMTP réelle côté `.env.local`, les e-mails de commande sont reçus.

## Prochaine suite

```text
1. Dette technique courte env/uploads/assets/MAILER_DSN.
2. J5K GPS livraison.
3. J5L admin commande/logistique terrain.
4. J5M portail livreur exploitable.
5. Paiement plus tard.
```
