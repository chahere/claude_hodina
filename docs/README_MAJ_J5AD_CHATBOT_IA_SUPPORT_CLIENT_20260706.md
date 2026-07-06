# J5AD — Chatbot IA + support client

Date : 2026-07-06
Statut : codé en 3 temps distincts, non testé en conditions réelles (voir Tests locaux recommandés).

## Objectif

Donner aux clients connectés de `/mon-compte` un assistant IA capable de répondre sur le statut de commande, les infos de livraison, le catalogue et la FAQ institutionnelle Hodina, avec escalade automatique vers un ticket support humain traçable dès que l'IA ne peut pas répondre ou que le client le demande explicitement. Les visiteurs non connectés n'ont accès qu'à un formulaire de contact simple, jamais à l'IA. Le fournisseur LLM (provider, modèle, clé API) est configurable par l'admin sans redéploiement, sans jamais exposer la clé en clair.

## Décision produit

- Livré en 3 temps, un commit par temps, jamais mélangés :
  1. Modélisation complète (5 entités) + migration + formulaire de contact anonyme + écran EasyAdmin "Tickets support" + écran "FAQ" + notification admin. Zéro appel IA.
  2. Écran EasyAdmin "Réglages IA" + `ChatbotContextBuilderService` + `ChatbotAiClientService` (mock + Anthropic + OpenAI) + interface de chat dans `/mon-compte` + rate limiter.
  3. `ChatbotEscalationService` + câblage du feature flag + documentation.
- Trois fournisseurs LLM supportés dès ce lot : `mock` (simulé, aucun appel réseau — sert à tester tout le pipeline avant tout branchement réel), `anthropic`, `openai`. Le choix du provider actif est un réglage admin, pas un choix de code.
- Le flag d'activation globale (`ai_chatbot_enabled`) vit dans `HodinaSetting` (groupe Technique), pas en `.env` : décision explicite du demandeur, alignée avec la convention déjà utilisée par tous les autres interrupteurs de fonctionnalité du projet (`commerce_cart_locked`, `courier_payouts_enabled`, etc.). Quand il est à `false`, le lien "Assistant" disparaît de la navigation `/mon-compte` et l'endpoint de chat renvoie une erreur 503 ; le formulaire de contact reste actif dans tous les cas car il ne dépend pas de ce flag.

## Décision DB

Trois migrations, une par temps :

- `Version20260706120000` : tables `faq_entry`, `chatbot_conversation`, `chatbot_message`, `support_ticket`, `support_ticket_message`.
- `Version20260706150000` : table `ai_chatbot_setting` (singleton — un seul réglage actif à la fois : `provider`, `model`, `api_key_encrypted`, `updated_at`). La clé API n'est jamais stockée en clair : `AiChatbotCredentialCipher` réutilise le mécanisme AES-256-GCM de `CustomerDeliveryCodeService` (clé dérivée de `kernel.secret`, IV aléatoire par chiffrement, payload versionné).
- `Version20260706180000` : seed idempotent (`INSERT ... WHERE NOT EXISTS`) du réglage `hodina_setting.ai_chatbot_enabled`, désactivé par défaut (`value = '0'`).

`EmailLog` reçoit une nouvelle constante d'événement (`EVENT_SUPPORT_TICKET_CREATED`), sans changement de schéma (table déjà existante).

## Périmètre technique

**Modifié (additif uniquement, diffs vérifiés ligne à ligne)**
- `src/Controller/Admin/DashboardController.php` — 3 nouvelles entrées de menu (Tickets support, FAQ, Réglages IA).
- `src/Entity/EmailLog.php` — 1 nouvelle constante.
- `src/Entity/HodinaSetting.php` — 1 nouvelle constante + inclusion dans `getBooleanSettingKeys()`.
- `src/Repository/ChatbotConversationRepository.php` — 1 nouvelle méthode de recherche (fichier créé par ce même lot en Temps 1).
- `templates/base.html.twig` — 1 lien de footer ("Nous contacter").
- `templates/client/_account_nav.html.twig` — 1 lien conditionnel ("Assistant"), le seul point de contact autorisé avec un fichier du lot J5AC.

**Non modifié**
- Tout fichier des lots J5Z, J5AB, J5AC, J5AA en dehors du lien ci-dessus.
- Catalogue public, panier, checkout, calcul de livraison/frais, Djama, paiements livreurs.
- `Customer`, `CustomerOrder`, `Address`, `DeliveryCommune`, `Product` : lus en lecture seule par `ChatbotContextBuilderService`, jamais modifiés.

## Hors périmètre volontaire

- Pas de widget IA sur le catalogue public anonyme.
- Pas de base vectorielle ni de pipeline RAG : la FAQ (`FaqEntry`) est injectée telle quelle, en texte, dans le prompt système.
- Pas de suggestion de prix ou de catalogue personnalisée par l'IA.
- Pas de clôture automatique de ticket par l'IA.
- Pas de canal SMS/WhatsApp pour la réponse aux tickets.
- Pas de champ clé API en clair (base, logs, réponses HTTP, EasyAdmin — y compris à l'édition).
- Pas d'endpoint renvoyant le contexte construit (commandes, produits) en JSON brut : seule la réponse textuelle de l'IA transite entre `/mon-compte/assistant/message` et le frontend.

## Anti-régression

- Le formulaire de contact public (`/contact`) reste fonctionnel indépendamment du flag `ai_chatbot_enabled` et sans authentification.
- `ChatbotEscalationService` ne clôture jamais un ticket : seule une action admin explicite (`SupportTicketCrudController::close`) le fait.
- `ChatbotContextBuilderService` n'effectue que des lectures (`createQueryBuilder`/`findBy`) : aucune écriture sur `Customer`, `CustomerOrder`, `Product`, `Address`.
- Le rate limiter (`chatbot_per_customer`, `chatbot_per_ip`) s'applique uniquement à `POST /mon-compte/assistant/message`, jamais au formulaire de contact ni au reste du site.

## Tests locaux recommandés

Cette archive de travail ne contient pas `composer.json`/`vendor/`/`.env` (comme le reste du dépôt) : les commandes ci-dessous sont à exécuter dans l'environnement réel, après :

```bash
composer require symfony/rate-limiter symfony/http-client
```

```bash
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
php bin/console lint:container
php bin/console lint:twig templates/contact templates/admin/support_ticket templates/admin/field templates/emails/admin templates/client/chatbot templates/client/_account_nav.html.twig
php bin/console cache:clear
```

**Contrôles navigateur**
- Visiteur non connecté : `/contact` fonctionne, `/mon-compte/assistant` redirige vers la connexion.
- Client connecté, réglage IA sur `mock` : `/mon-compte/assistant` répond avec le texte simulé, aucune escalade.
- Écrire "je veux parler à un humain" ou "remboursement" (avec un provider réel configuré, pour déclencher le marqueur `[ESCALADE_HUMAIN]`) : un `SupportTicket` (origine `CHATBOT_ESCALATION`) apparaît dans EasyAdmin > Support avec le transcript complet, e-mail admin reçu.
- EasyAdmin > Réglages IA : changer de provider et ressaisir une clé ; recharger la page d'édition et constater que le champ clé reste vide (jamais réaffichée).
- EasyAdmin > Technique / maintenance : basculer `ai_chatbot_enabled` sur `false` ; constater la disparition du lien "Assistant" et une erreur 503 sur l'endpoint de chat, sans effet sur `/contact`.
- Envoyer rapidement plus de 20 messages en 5 minutes : le rate limiter doit bloquer avec un message clair.

**Fait dans ce sandbox (sans vendor/)** : `php -l` sur l'ensemble des fichiers PHP créés/modifiés des 3 temps — tous corrects syntaxiquement.

## Commit conseillé

Ne pas utiliser `git add .`.

```bash
git status --short
git add <fichiers du temps concerné, un par un ou en liste explicite>
git commit -m "feat(j5ad): ..."
```

(Déjà appliqué pour les 3 temps de ce lot — voir `COMMIT_J5AD_CHATBOT_IA_SUPPORT_CLIENT_20260706.md` pour le détail par commit.)

## Validation recette / production

**État final du lot : non fait.** Ce lot a été codé mais n'a pas encore été exécuté dans un environnement disposant de `vendor/` et d'une base MariaDB — la recette (contrôles navigateur, `doctrine:schema:validate`, appel réel à un provider LLM) reste à faire avant tout tag `recette-j5ad-*` / `prod-j5ad-*`.
