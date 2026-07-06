# Commit J5A — SMS pilote, inscription, checkout et suppression cascade

## Objet du commit

Ce commit stabilise la phase pilote avant le refactoring workflow et le portail livreur.

Il regroupe les actions validées dans la conversation :

- SMS manuel admin ;
- SmsLog enrichi ;
- bouton `Envoyer le SMS` dans SmsLog ;
- ouverture Messages iPhone avec numéro et message ;
- fusion changement de statut + SMS ;
- page intermédiaire avant envoi SMS ;
- inscription client stylée ;
- redirection après inscription vers catalogue ;
- champ nom obligatoire ;
- correction checkout nouvel utilisateur ;
- suppression cascade pilote des utilisateurs de test.

---

# Branche recommandée

Si tu es encore sur la branche J5 :

```bash
git branch --show-current
```

Nom de branche recommandé si tu veux isoler ce lot :

```bash
git checkout -b pilot/j5a-sms-checkout-cleanup
```

Si ta branche existe déjà, reste dessus.

---

# Vérifications avant commit

```bash
php bin/console cache:clear
php bin/console doctrine:migrations:status
php bin/console doctrine:schema:validate
```

Tests manuels recommandés :

```text
1. Créer un nouveau client.
2. Vérifier que le nom est obligatoire.
3. Vérifier la redirection vers /catalogue.
4. Ajouter des produits au panier.
5. Valider une commande.
6. Vérifier la commande dans /ouegnewe.
7. Changer le statut avec Valider + SMS ou Préparer + SMS.
8. Vérifier la page intermédiaire.
9. Cliquer Envoyer SMS depuis l'iPhone.
10. Vérifier numéro et message préremplis.
11. Vérifier le SmsLog.
12. Supprimer un compte de test avec Supprimer pilote.
```

---

# Commandes Git recommandées

## Contrôle

```bash
git status
git diff --stat
```

## Ajout

```bash
git add .
```

## Commit court

```bash
git commit -m "feat(pilot): stabilize manual SMS and customer checkout"
```

## Commit détaillé recommandé

```bash
git commit -m "feat(pilot): stabilize manual SMS and customer checkout" \
  -m "Add manual SMS flow through SmsLog and iPhone sms links." \
  -m "Merge order status transitions with SMS intermediate confirmation page." \
  -m "Improve registration, require customer last name, and redirect to catalogue." \
  -m "Fix checkout validation for new customers." \
  -m "Add pilot cascade deletion for test customers and linked orders/SMS logs."
```

## Push

À adapter selon ta branche :

```bash
git push --set-upstream origin pilot/j5a-sms-checkout-cleanup
```

Si la branche suit déjà `origin` :

```bash
git push
```

---

# Message de PR possible

```text
## Résumé

Ce lot stabilise la phase pilote avant le portail livreur.

### SMS pilote
- Ajout d'une architecture SMS avec SmsService, SmsSenderInterface et LogSmsSender.
- SmsLog enrichi et maintenu en lecture seule.
- Bouton Envoyer le SMS depuis SmsLog.
- Ouverture de Messages iPhone avec numéro et message préremplis.
- Fusion des actions de statut commande avec la génération SMS.
- Page intermédiaire avant l'ouverture de Messages.

### Parcours client
- Page d'inscription remise au propre.
- Redirection après inscription vers le catalogue.
- Nom obligatoire à l'inscription.
- Correction du checkout pour les nouveaux utilisateurs.

### Nettoyage pilote
- Ajout d'une action Supprimer pilote pour supprimer un utilisateur de test avec ses commandes, lignes, SmsLog et adresses.
- Protections contre suppression admin et auto-suppression.

## Tests
- Inscription client OK.
- Validation commande OK.
- Changement statut + SmsLog OK.
- Ouverture Messages iPhone OK.
- Suppression cascade pilote OK.
```

---

# Commit fin de session — Préprod, légal et nettoyage public

## Changements à inclure

Selon l'état réel du dépôt, le commit de fin de session doit inclure :

- reset password par SmsLog si non encore commité ;
- pages CGU / CGV ;
- acceptation CGU/CGV au checkout ;
- sommaire légal compact mobile ;
- suppression du lien Admin dans le footer ;
- correction EasyAdmin JS générique ;
- documentation mise à jour.

Ne pas commiter :

- `.env.local` contenant des secrets ;
- `.htpasswd` ;
- archives `.zip` de déploiement ;
- dump SQL ;
- fichiers spécifiques serveur non souhaités en prod.

## Vérifier le working tree

```bash
git status
```

## Ajouter les fichiers applicatifs et docs

Exemple :

```bash
git add src templates public/js docs DECISIONS.md ENTITIES.md ROADMAP.md TODO.md VISION.md WORKFLOWS.md ARCHITECTURE.md COMMIT_J5A.md
```

À adapter selon l'emplacement réel des docs dans le projet.

## Commit recommandé

```bash
git commit -m "feat(preprod): add legal pages and finalize pilot recipe setup" \
  -m "Add CGU/CGV pages with checkout acceptance." \
  -m "Improve legal pages mobile UX with compact table of contents." \
  -m "Remove public admin footer link and keep EasyAdmin translation generic." \
  -m "Document preprod setup, Basic Auth, DB import and reset password SMS flow."
```

## Push

Si la branche n'a pas d'upstream :

```bash
git push --set-upstream origin pilot/j5-preprod-password-reset
```

Sinon :

```bash
git push
```


---

# Note de continuité

Les étapes suivantes sont désormais documentées séparément :

```text
COMMIT_J5B.md
COMMIT_J5C.md
```

J5A reste le socle SMS / checkout / inscription / préproduction initiale.
J5B couvre le refactoring workflow admin.
J5C couvre les données livraison et la préparation du dashboard livreur.


---

# Note de continuité J5E / J5F / J5G

Les prochains jalons prolongent le socle pilote créé en J5A.

J5A a stabilisé :

- inscription ;
- checkout ;
- SMS manuel ;
- préproduction ;
- pages légales.

J5E, J5F et J5G vont enrichir le même tunnel client avec :

- prix client calculé avec marge Hodina ;
- frais de livraison calculés automatiquement ;
- information logistique dès le panier ;
- valeurs économiques figées dans la commande.

Attention : ces évolutions toucheront le checkout. Il faudra donc retester les acquis J5A après chaque étape.


---

# Note postérieure — J5E validé

J5E prolonge directement le tunnel stabilisé en J5A : inscription, checkout, SMS manuel, préproduction et pages légales.

Après J5E, il faut continuer à retester à chaque évolution du checkout : inscription, connexion, panier, acceptation CGU/CGV, création commande et SmsLog initial.

J5E est validé : les anciennes commandes ne changent pas et les nouvelles figent leurs valeurs économiques.

---

# Note postérieure — J5G avancé prolonge le checkout pilote

J5A avait stabilisé le checkout, les SMS manuels et la préproduction.

Les évolutions J5E / J5F / J5G prolongent ce tunnel client.

Point à retenir pour un développeur débutant :

```text
J5A crée la commande.
J5E fige les prix produit dans OrderItem.
J5G-E devra figer les frais livraison dans CustomerOrder.
```

La logique panier J5G-A reste une estimation. Le checkout devra toujours recalculer avant de créer la commande.
