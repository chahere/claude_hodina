# COMMIT — J5AD Chatbot IA + support client

Date : 2026-07-06

## Commits

```text
feat(j5ad): support tickets, FAQ model and public contact form
feat(j5ad): AI settings, chatbot backend and connected chat UI
feat(j5ad): chatbot escalation, feature flag and lot documentation
```

## Tags

Aucun tag recette/production créé à ce stade : le lot n'a pas encore été validé en environnement réel (voir section Validation). À poser après recette : `recette-j5ad-chatbot-ia-support-client-20260706`, puis `prod-j5ad-chatbot-ia-support-client-20260706`.

## Résumé

Chatbot IA pour les clients connectés dans `/mon-compte`, avec escalade automatique vers un ticket support humain traçable, formulaire de contact simple pour les visiteurs non connectés, réglages LLM (provider/modèle/clé API chiffrée) administrables sans redéploiement, et flag d'activation global.

## Fichiers principaux

**Temps 1 — modélisation, formulaire, tickets (zéro IA)**
- `src/Entity/{FaqEntry,SupportTicket,SupportTicketMessage,ChatbotConversation,ChatbotMessage}.php` + repositories
- `migrations/Version20260706120000.php`
- `src/Controller/ContactController.php`, `src/Form/ContactFormType.php`, `templates/contact/form.html.twig`
- `src/Controller/Admin/{SupportTicketCrudController,FaqEntryCrudController}.php` + templates `templates/admin/support_ticket/*`, `templates/admin/field/support_ticket_messages.html.twig`
- `src/Service/SupportTicketNotificationService.php` + `templates/emails/admin/support_ticket_created.html.twig`
- Modifiés (additif) : `src/Controller/Admin/DashboardController.php`, `src/Entity/EmailLog.php`, `templates/base.html.twig`

**Temps 2 — réglages IA, backend chatbot, UI connectée**
- `src/Entity/AiChatbotSetting.php` + `src/Repository/AiChatbotSettingRepository.php` + `migrations/Version20260706150000.php`
- `src/Service/AiChatbotCredentialCipher.php`
- `src/Dto/{ChatbotAiRequest,ChatbotAiReply}.php`
- `src/Service/Ai/{ChatbotAiClientInterface,MockChatbotAiClient,AnthropicChatbotAiClient,OpenAiChatbotAiClient,ChatbotAiClientResolver}.php`
- `src/Service/{ChatbotContextBuilderService,ChatbotConversationService}.php`
- `src/Controller/Admin/AiChatbotSettingCrudController.php`
- `src/Controller/Client/ChatbotController.php` + `templates/client/chatbot/index.html.twig`
- `config/packages/{rate_limiter,http_client}.yaml`
- Modifiés (additif) : `src/Controller/Admin/DashboardController.php`, `src/Repository/ChatbotConversationRepository.php`, `templates/client/_account_nav.html.twig`

**Temps 3 — escalade, feature flag, documentation**
- `src/Service/ChatbotEscalationService.php`
- `src/Service/ChatbotFeatureSettingsService.php` + `src/Twig/ChatbotFeatureExtension.php`
- `migrations/Version20260706180000.php` (seed du réglage `ai_chatbot_enabled`)
- Modifiés : `src/Entity/HodinaSetting.php` (nouvelle clé + `getBooleanSettingKeys()`), `src/Service/ChatbotContextBuilderService.php` (marqueur d'escalade), `src/Service/ChatbotConversationService.php` (détection + déclenchement), `src/Controller/Client/ChatbotController.php` (garde le flag), `templates/client/_account_nav.html.twig` (lien conditionné au flag)
- `docs/COMMIT_J5AD_CHATBOT_IA_SUPPORT_CLIENT_20260706.md`, `docs/README_MAJ_J5AD_CHATBOT_IA_SUPPORT_CLIENT_20260706.md`

## Décisions importantes

- **`AiChatbotSetting` (entité dédiée) plutôt que `HodinaSetting`** pour provider/modèle/clé API : le mécanisme générique `HodinaSetting` (formulaire lié directement à la colonne `value`) ne permet pas proprement le pattern write-only exigé pour la clé API. Une entité dédiée reproduit exactement le pattern `Customer::plainPassword` (champ transitoire, jamais persisté, jamais réaffiché).
- **Flag d'activation dans `HodinaSetting`** (`KEY_AI_CHATBOT_ENABLED`, groupe `technical`), et non en `.env` comme évoqué initialement dans le brief : confirmé explicitement par le demandeur, pour rester cohérent avec la convention déjà utilisée par tous les autres interrupteurs de fonctionnalité du projet (aucun flag `.env` n'existe ailleurs dans ce projet).
- **Écran EasyAdmin FAQ ajouté** en Temps 1 bien que non listé explicitement dans le brief : sans lui, `FaqEntry` n'aurait aucun moyen d'être alimenté. Confirmé par le demandeur.
- **Marqueur d'escalade `[ESCALADE_HUMAIN]`** : le prompt système demande à l'IA de terminer sa réponse par ce marqueur quand la demande sort de son périmètre. `ChatbotEscalationService` le détecte et le retire systématiquement avant tout affichage/transcript. Alternative à une classification NLP dédiée, cohérente avec le volume MVP visé.
- **Historique envoyé à l'IA plafonné à 20 messages** (`ChatbotConversationService::MAX_HISTORY_MESSAGES`) : maîtrise du coût/volume de tokens, indépendant du rate limiting. L'historique complet reste stocké en base pour le transcript d'escalade.

## Hors périmètre

- Aucune base vectorielle / pipeline RAG (FAQ injectée telle quelle dans le prompt système).
- Aucune suggestion de prix ou de catalogue personnalisée par l'IA.
- Aucune clôture automatique de ticket par l'IA (uniquement via EasyAdmin, action admin explicite).
- Aucun canal SMS/WhatsApp pour la réponse aux tickets.
- Aucune modification des fichiers des lots J5Z/J5AB/J5AC/J5AA en dehors du lien d'accès au chat dans `_account_nav.html.twig` (déjà autorisé explicitement).

## Validation

**Statut : implémenté, non testé en conditions réelles.**

Cette archive de travail ne contient ni `composer.json`, ni `vendor/`, ni `.env` (volontairement, comme le reste du dépôt) : aucune commande `php bin/console` n'a pu être exécutée dans cet environnement. Seul `php -l` a été passé sur chacun des ~30 fichiers PHP créés/modifiés (tous corrects syntaxiquement). Voir `README_MAJ_J5AD_CHATBOT_IA_SUPPORT_CLIENT_20260706.md` pour la liste complète des contrôles à exécuter côté environnement réel avant recette.

Dépendances Composer à ajouter avant tout test : `symfony/rate-limiter`, `symfony/http-client`.
