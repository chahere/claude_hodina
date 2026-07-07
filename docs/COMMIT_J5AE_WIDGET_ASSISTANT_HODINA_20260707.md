# COMMIT — J5AE Widget flottant Assistant Hodina

Date : 2026-07-07

## Commits

```text
fix(admin): collapse the Support menu section like the others
feat(j5ae): backend widget flottant Assistant Hodina (moteur à règles)
feat(j5ae): widget flottant Assistant Hodina (UI)
docs(j5ae): commit et README de mise à jour du lot
```

## Tags

Aucun tag recette/production créé à ce stade : le lot n'a pas encore été validé en environnement réel (voir `README_MAJ_J5AE_WIDGET_ASSISTANT_HODINA_20260707.md` § Validation). À poser après recette : `recette-j5ae-widget-assistant-hodina-20260707`, puis `prod-j5ae-widget-assistant-hodina-20260707`.

## Résumé

Widget conversationnel flottant "Assistant Hodina", moteur à règles (aucun appel IA), disponible sur tout le site public (catalogue, fiche produit, panier, checkout, contact, espace client) pour les visiteurs anonymes **et** les clients connectés. Répond aux questions fréquentes (livraison, suivi commande, disponibilité produit, vendeurs) et escalade vers un ticket support humain traçable quand il ne sait pas répondre ou sur demande explicite.

Le chatbot IA existant (lot J5AD, `/mon-compte/assistant`, réservé aux clients connectés) n'est pas modifié : les deux systèmes coexistent, le widget est la nouvelle couche d'accès rapide sur l'ensemble du site.

## Fichiers principaux

**Correctif indépendant**
- `assets/admin.js` : ajoute `'Support'` à la liste des sections repliables du menu admin (oubli du lot J5AD).

**Backend (moteur de règles + escalade)**
- `src/Dto/SupportWidgetReply.php`
- `src/Service/SupportWidgetAnswerService.php` (réponses par mots-clés + repli `FaqEntry`)
- `src/Service/SupportWidgetEscalationService.php` (crée un `SupportTicket`, réutilise `SupportTicketNotificationService`)
- `src/Controller/SupportWidgetController.php` (routes publiques `/assistant-hodina/message` et `/assistant-hodina/escalade`)
- `migrations/Version20260707040000.php` (seed `HodinaSetting::KEY_SUPPORT_MESSENGER_URL`, valeur vide par défaut)
- Modifiés : `src/Entity/SupportTicket.php` (constante `ORIGIN_CHAT_WIDGET`), `src/Entity/HodinaSetting.php` (constante `KEY_SUPPORT_MESSENGER_URL`), `src/Controller/Admin/SupportTicketCrudController.php` (libellé d'origine), `config/packages/rate_limiter.yaml` (`widget_message_per_ip`, `widget_escalation_per_ip`)

**Frontend (widget)**
- `templates/support/_chat_widget.html.twig` (bouton flottant, panneau, bulles, suggestions rapides, mini-formulaire d'escalade, JS vanilla auto-contenu)
- Modifiés : `templates/base.html.twig` (bloc `chat_widget`, inclus une fois pour tout le site), `templates/client/chatbot/index.html.twig` et `templates/courier/dashboard.html.twig` (bloc `chat_widget` vidé pour masquer le doublon), `public/css/style_mobile.css` (styles du widget + utilitaire `.sr-only`)

**Documentation**
- `docs/COMMIT_J5AE_WIDGET_ASSISTANT_HODINA_20260707.md` (ce fichier)
- `docs/README_MAJ_J5AE_WIDGET_ASSISTANT_HODINA_20260707.md`

## Aucune nouvelle entité, aucun nouveau schéma

Contrairement à la demande initiale qui proposait une entité `SupportRequest`, ce lot réutilise `SupportTicket` / `SupportTicketMessage` (déjà validés en J5AD) avec une nouvelle valeur de `origin` (`CHAT_WIDGET`) — colonne `varchar` déjà générique, aucune migration de structure nécessaire. La seule migration du lot est un **seed de donnée** (`hodina_setting`), idempotent (`WHERE NOT EXISTS`), sans impact sur les tables existantes.

## Décisions importantes

- **Coexistence, pas remplacement.** Le chatbot IA (`/mon-compte/assistant`, J5AD) reste la référence pour les échanges approfondis des clients connectés. Le widget est un point d'accès rapide, à règles, disponible partout — y compris pour les visiteurs anonymes, ce que le chatbot IA ne fait jamais (contrainte d'origine : l'IA n'est jamais exposée à un visiteur non connecté).
- **"Assistant Hodina", jamais "AI Assistant".** Aucun texte du widget ne prétend être une IA : les réponses sont 100 % déterministes (mots-clés + FAQ).
- **Aucun tarif inventé.** La réponse "livraison" ne cite jamais de montant : elle renvoie vers la page Infos livraison et rappelle que le calcul se fait au panier.
- **Messenger : lien configurable, pas d'intégration API.** `HodinaSetting::KEY_SUPPORT_MESSENGER_URL` est vide par défaut (bouton masqué tant qu'il n'est pas renseigné). Aucun jeton Meta en dur ; l'intégration Messenger API réelle (Meta App, webhook, Page Access Token, PSID) est un travail futur distinct, hors périmètre MVP.
- **Persistance de conversation en `sessionStorage` uniquement**, côté navigateur, pour garder le fil en changeant de page pendant la session. Aucune table de conversation dédiée côté serveur pour le widget (contrairement à `ChatbotConversation` pour l'IA) : seule l'escalade crée une trace durable (le `SupportTicket`).
