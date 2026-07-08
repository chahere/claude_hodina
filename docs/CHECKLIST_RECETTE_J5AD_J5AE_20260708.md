# Checklist recette fonctionnelle — J5AD + J5AE (Assistant Hodina)

Date : 2026-07-08
Contexte : déploiement technique recette validé (tag `recette-j5ad-j5ae-assistant-hodina-20260708`, commit `cf8fa51`, `develop` et `main` réalignés). Migrations, `doctrine:schema:validate`, cache et assets déjà verts côté serveur — cette checklist couvre la validation **fonctionnelle** restante, avant tout tag `prod-j5ad-j5ae-*`.

## A. Visiteur anonyme (desktop + mobile)

- [ ] `https://recette.hodina.fr/` charge sans erreur ; widget « Assistant Hodina » (mascotte) visible.
- [ ] Clic sur le widget : panneau s'ouvre, message d'accueil + 4 suggestions rapides.
- [ ] Suggestion « Frais de livraison » : réponse cohérente, **aucun tarif chiffré inventé**, lien vers les infos livraison.
- [ ] Question hors sujet (ex. « recette de cuisine ») : réponse « je ne suis pas sûr... » + formulaire d'escalade apparaît.
- [ ] Envoi du formulaire d'escalade (nom + e-mail + message) : confirmation affichée.
- [ ] EasyAdmin > Support > Tickets support : le ticket apparaît, origine « Widget assistant Hodina », e-mail admin reçu (ou visible dans Logs > E-mails).
- [ ] Navigation catalogue → produit → panier → checkout : widget toujours disponible, conversation conservée (sessionStorage).
- [ ] `/contact` : formulaire fonctionne sans connexion, crée un `SupportTicket`.

## B. Exclusions du widget (ne doit apparaître nulle part ici)

- [ ] `/mon-compte/assistant` : widget flottant absent (pas de doublon avec le chat IA plein écran).
- [ ] `/djama` (portail livreur) : widget absent.

## C. Client connecté — chatbot IA (J5AD)

- [ ] Connexion avec un compte client recette.
- [ ] `ai_chatbot_enabled = false` (valeur par défaut post-migration) : lien « Assistant » absent du menu `/mon-compte`, endpoint de chat en 503.
- [ ] EasyAdmin > Réglages (Technique / maintenance) : activer `ai_chatbot_enabled`.
- [ ] Lien « Assistant » apparaît, `/mon-compte/assistant` accessible.
- [ ] Provider `mock` configuré (EasyAdmin > Réglages IA) : le chat répond avec le texte simulé.
- [ ] Widget flottant connecté : « Où est ma commande ? » renvoie vers « Mes commandes » (pas de formulaire de connexion).
- [ ] EasyAdmin > Réglages IA : changer provider/clé, recharger la page → la clé API n'est jamais réaffichée.
- [ ] Déclencher une escalade (« je veux parler à un humain », ou marqueur `[ESCALADE_HUMAIN]` avec un provider réel) : `SupportTicket` origine `CHATBOT_ESCALATION` créé, transcript complet, e-mail admin reçu.
- [ ] Rate limiter : plus de 20 messages/5 min bloque avec un message clair.

## D. EasyAdmin

- [ ] Menu « Support » présent, se replie/déplie correctement.
- [ ] Tickets support : liste, détail, réponse, clôture fonctionnent (CSRF).
- [ ] FAQ : création/édition d'une entrée fonctionne.
- [ ] Réglages IA : provider/modèle/clé éditables.
- [ ] `support_messenger_url` vide par défaut → bouton « Continuer sur Messenger » absent du widget ; si renseigné, le bouton apparaît.

## E. Non-régression transverse

Le lot touche `base.html.twig`, le CSS global et EasyAdmin — vérifier qu'il n'y a pas d'effet de bord :

- [ ] Catalogue mobile : navigation, filtres, recherche OK, widget ne recouvre rien d'essentiel.
- [ ] Ajout panier (desktop + mobile) : toast de confirmation OK, badge panier à jour.
- [ ] Panier avec sélection commune de livraison : frais cohérents.
- [ ] Checkout complet (sans GPS, puis avec GPS mobile HTTPS) jusqu'à confirmation commande.
- [ ] `/djama` : connexion livreur, tableau de bord, sans effet de bord visuel.
- [ ] EasyAdmin > Commandes : ouverture, édition normales.

## F. Vérifications serveur (SSH recette)

```bash
cd ~/recette.hodina.fr
php bin/console doctrine:migrations:status --env=prod
php bin/console doctrine:schema:validate --env=prod
tail -n 100 var/log/prod.log
grep -iE "assistant|chatbot|support|ticket|rate.?limit|http-client|error|critical|exception" var/log/prod.log | tail -n 100
```
- [ ] Aucune exception liée à J5AD/J5AE depuis le déploiement.
- [ ] `doctrine:schema:validate --env=prod` reste vert après les tests ci-dessus.

## G. Critère de sortie avant tag prod

Ne pas taguer `prod-j5ad-j5ae-*` tant que :
- Tous les points A à E sont cochés sans anomalie bloquante.
- F ne montre aucune exception liée au lot.
- Au moins un cycle complet catalogue → panier → checkout → commande admin a été rejoué sans régression.
