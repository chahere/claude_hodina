# J5AE — Widget flottant Assistant Hodina

Date : 2026-07-07
Statut : livré, à valider en local puis en recette (voir § Validation).

## Objectif

Remplacer l'expérience "formulaire plein écran" par un vrai widget conversationnel flottant, mobile-first, disponible sur tout le site public : icône flottante en bas à droite (desktop) / quasi plein écran en bas (mobile), bulles de conversation, suggestions rapides, escalade humaine. `/contact` reste disponible comme page de repli classique.

## Décision produit

- Le widget est un **moteur à règles**, pas une IA : mots-clés (livraison, suivi commande, disponibilité produit, vendeurs, demande d'humain) + repli sur la FAQ (`FaqEntry`), sinon réponse explicite "je ne suis pas sûr, je transmets à l'équipe".
- Il coexiste avec le chatbot IA du lot J5AD (`/mon-compte/assistant`) sans le modifier. Ce dernier reste la seule surface qui appelle réellement un LLM (Mock/Anthropic/OpenAI), réservée aux clients connectés.
- Visible pour **anonymes et connectés** partout sauf sur `/mon-compte/assistant` (évite un double chat sur la même page) et `/djama` (portail livreur, hors périmètre).

## Décision DB

Pas de nouvelle entité. `SupportTicket`/`SupportTicketMessage` (J5AD) sont réutilisés tels quels, avec une nouvelle valeur d'`origin` (`CHAT_WIDGET`) — colonne `varchar` générique existante. La seule migration (`Version20260707040000`) ajoute une ligne de réglage (`support_messenger_url`, vide par défaut) dans `hodina_setting`, de façon idempotente.

## Périmètre technique

- `SupportWidgetAnswerService` : réponses par mots-clés + repli FAQ, aucune donnée de commande exposée sans connexion.
- `SupportWidgetEscalationService` : crée un `SupportTicket` (origine `CHAT_WIDGET`) avec l'échange récent en contexte, notifie les admins via `SupportTicketNotificationService` (déjà en place).
- `SupportWidgetController` : 2 routes publiques (`POST /assistant-hodina/message`, `POST /assistant-hodina/escalade`), CSRF par formulaire, rate limiting par IP (`widget_message_per_ip` 40/5min, `widget_escalation_per_ip` 5/15min), piège à robots sur l'escalade anonyme.
- Widget inclus une seule fois dans `templates/base.html.twig` (bloc `chat_widget`) : couvre automatiquement catalogue, fiche produit, panier, checkout, contact, espace client (tous héritent de `base.html.twig`).
- JS vanilla auto-contenu dans le partial (pas de Stimulus, pas de nouvelle entrée `importmap.php`), conversation persistée en `sessionStorage` (le temps de la session navigateur).
- Bouton "Continuer sur Messenger" affiché uniquement si `HodinaSetting::KEY_SUPPORT_MESSENGER_URL` est renseigné.

## Hors périmètre volontaire

- **Pas d'IA dans le widget.** Uniquement des règles ; brancher une vraie IA sur le widget lui-même serait un lot distinct (le chatbot IA existe déjà, mais ailleurs : `/mon-compte/assistant`).
- **Pas d'intégration Messenger API réelle.** Seulement un lien configurable. L'intégration réelle (Meta App, webhook, Page Access Token, PSID) est un travail futur distinct — aucun jeton Meta n'est codé en dur.
- **Pas de FAQ enrichie par NLP.** Le matching FAQ est un simple score par mots communs (pas d'embeddings, pas de recherche floue avancée).
- **Pas de conversation persistée côté serveur pour le widget** (contrairement à `ChatbotConversation` pour l'IA) : seule l'escalade laisse une trace durable (le ticket). Fermer l'onglet perd l'historique si `sessionStorage` est vidé.
- **Pas de synchronisation multi-appareil** : la conversation vit dans le navigateur, pas dans un compte.

## Anti-régression

- Aucun fichier des lots fermés (J5Z/J5AB/J5AC/J5AA) touché.
- Le chatbot IA J5AD (`ChatbotConversationService`, `AiChatbotSetting`, `/mon-compte/assistant`) n'est pas modifié.
- `assets/styles/app.css` non touché (reste neutre, cf. incident "portail bleu").
- Un seul CSS public modifié : `public/css/style_mobile.css` (ajout en fin de fichier, palette existante réutilisée).
- `importmap.php` non touché : le JS du widget est inline dans le partial, pas de nouvelle entrée AssetMapper.

## Commandes locales

Aucune nouvelle dépendance Composer pour ce lot (pas de nouveau bundle). Après `git pull` :

```powershell
cd D:\hodina\claude_hodina
git pull origin claude/ai-chatbot-customer-account-1i5jbl
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php -d memory_limit=-1 bin/console cache:clear --no-warmup
php -d memory_limit=-1 bin/console cache:warmup
symfony server:start --no-tls
```

## Tests locaux recommandés

**Desktop**
1. Ouvrir le catalogue (`/`) déconnecté : le bouton flottant (logo Hodina) apparaît en bas à droite.
2. Cliquer dessus : le panneau s'ouvre, message d'accueil + 4 suggestions rapides ("Frais de livraison", "Où est ma commande ?", "Produits disponibles", "Parler à l'équipe").
3. Cliquer "Frais de livraison" : réponse cohérente, **sans tarif chiffré**, avec un lien "Infos livraison".
4. Taper "Quels sont les frais de livraison pour Mamoudzou ?" : même type de réponse (mot-clé détecté), aucun tarif inventé.
5. Taper une question hors sujet (ex. "recette de cuisine") : réponse "Je ne suis pas sûr de la réponse..." + formulaire d'escalade qui apparaît.
6. Remplir le formulaire (nom + e-mail, message) et envoyer : confirmation affichée, ticket visible dans EasyAdmin > Support > Tickets support (origine "Widget assistant Hodina"), e-mail de notification dans Logs > E-mails (logs).
7. Naviguer vers `/panier` puis `/checkout` : le widget reste disponible, la conversation en cours est conservée (sessionStorage).
8. Se connecter, réouvrir le widget : cliquer "Où est ma commande ?" renvoie vers "Mes commandes" (pas de formulaire de connexion).
9. Aller sur `/mon-compte/assistant` : le widget flottant est absent (pas de doublon avec le chat IA plein écran).

**Mobile (redimensionner la fenêtre ou vrai mobile)**
10. Le panneau occupe presque toute la largeur, ancré en bas de l'écran.
11. Les champs du formulaire d'escalade restent utilisables au clavier tactile (labels, focus visibles).
12. Vérifier que le panier, les filtres catalogue et la navigation restent utilisables avec le widget fermé **et** ouvert (le widget ne doit rien recouvrir d'essentiel).

**Contrôles transverses**
13. Vérifier qu'aucun tarif de livraison n'est jamais inventé dans les réponses du widget.
14. Vérifier que le bouton "Continuer sur Messenger" n'apparaît **pas** tant que `support_messenger_url` est vide (réglage par défaut).
15. `doctrine:schema:validate` reste vert après migration.

## Limites connues du MVP (non faites, volontairement)

- Pas de vraie IA dans le widget (rule-based uniquement, cf. § Hors périmètre).
- Pas d'intégration Messenger API réelle, seulement un lien.
- Matching FAQ simple (score par mots), pas de recherche sémantique.
- Pas de persistance serveur de la conversation avant escalade (seul le ticket final est durable).
- Pas de traduction/shimaoré : textes en français uniquement, comme le reste du site.

## Points à prévoir plus tard

- Intégration Messenger API réelle : création d'une Meta App, webhook `messages`/`messaging_postbacks`, Page Access Token stocké chiffré (même mécanisme que `AiChatbotSetting`/`AiChatbotCredentialCipher`), résolution du PSID par client.
- Éventuellement, pour les clients connectés, proposer dans le widget un lien direct "Parler à l'assistant IA" vers `/mon-compte/assistant` plutôt que de dupliquer la logique IA dans le widget.
- Si le volume de tickets `CHAT_WIDGET` devient significatif, envisager un tableau de bord EasyAdmin dédié (filtre par origine) plutôt que la liste unique actuelle.

## Commit conseillé

Déjà fait, en 3 commits (+ ce commit de documentation) :
```text
fix(admin): collapse the Support menu section like the others
feat(j5ae): backend widget flottant Assistant Hodina (moteur à règles)
feat(j5ae): widget flottant Assistant Hodina (UI)
docs(j5ae): commit et README de mise à jour du lot
```

## Validation recette / production

À faire après validation locale (cf. § Tests locaux) :
1. Merge de la PR après revue.
2. Tag `recette-j5ae-widget-assistant-hodina-20260707` sur `main`, déploiement via `tools/deploy-hodina-by-tag.sh` (pas de nouvelle dépendance Composer pour ce lot, `RUN_COMPOSER` non nécessaire sauf si le serveur ne l'a pas encore pour J5AD).
3. Rejouer les tests manuels du § Tests locaux en recette.
4. Si un lien Messenger doit être actif, le renseigner dans EasyAdmin > Réglages > Technique / maintenance > "Lien Messenger support" (propre à chaque environnement).
5. Tag `prod-j5ae-widget-assistant-hodina-20260707` après validation recette.
