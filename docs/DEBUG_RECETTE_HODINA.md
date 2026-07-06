# Debug recette Hodina — guide opérationnel

# DEBUG RECETTE HODINA — Coupures intermittentes, authentification, backoffice et e-mails

Date de création : 2026-06-25  
Contexte : recette `https://recette.hodina.fr`  
Projet : Hodina / Symfony 8 / EasyAdmin / o2switch  
Chemin recette : `/home/vopu3712/recette.hodina.fr`

---

## 1. Objectif du document

Ce document sert de procédure de diagnostic quand le site de recette semble planter de façon intermittente, notamment avec des erreurs navigateur du type :

```text
ERR_CONNECTION_CLOSED
```

Les cas observés concernent surtout :

- l’authentification ;
- les modifications de réglages dans EasyAdmin ;
- certaines actions backoffice ;
- des tests mobiles sur iPhone, Chrome, Brave ou Firefox.

L’objectif est de distinguer rapidement :

1. une erreur Symfony applicative ;
2. une erreur PHP web avant Symfony ;
3. une coupure serveur web / LSAPI / proxy ;
4. un problème réseau mobile ou navigateur ;
5. un problème réel introduit par un patch récent.

---

## 2. État connu au 25/06/2026

Au moment de la création de ce document, les contrôles suivants étaient passés en recette :

- déploiement du tag `j5q-c2-branding-email-recette` OK ;
- commit déployé : `3586560` ;
- migration `Version20260625090000` exécutée ;
- Doctrine schema OK ;
- cache prod clear/warmup OK ;
- Twig e-mails OK ;
- PHP web réel : `8.4.21` ;
- `memory_limit` web : `512M` ;
- `max_execution_time` web : `600` ;
- `session_save_path` web : `/opt/alt/php84/var/lib/php/session` ;
- les tests `curl` serveur sur `/` et `/ouegnewe` répondaient correctement en `200` ou `302`.

Les logs d’accès live montraient des actions normales en `200` ou `302`, y compris :

- connexion `/hodi` ;
- accès `/ouegnewe/dashboard` ;
- modification de réglage EasyAdmin ;
- ajout panier ;
- checkout ;
- actions Djama.

Attention : dans les logs Apache, une ligne du type :

```text
POST /... HTTP/1.1" 302 502
```

ne signifie pas une erreur `502`. Cela signifie :

- statut HTTP : `302` ;
- taille réponse : `502` octets.

De même :

```text
GET /assets/controllers/hello_controller.js HTTP/1.1" 200 500
```

signifie :

- statut HTTP : `200` ;
- taille réponse : `500` octets.

---

## 3. Règle d’or avant toute correction

Ne pas corriger le code à l’aveugle.

Avant rollback ou patch :

1. noter l’heure exacte du plantage ;
2. noter l’URL et l’action réalisée ;
3. noter le navigateur et le réseau utilisé ;
4. récupérer les logs dans les 2 minutes après le plantage ;
5. vérifier si le serveur a réellement retourné une erreur HTTP ou si le navigateur a coupé seul.

---

## 4. Commande de base pour se placer dans le projet

```bash
cd ~/recette.hodina.fr
pwd
git status --short
git log --oneline -3
```

Résultat attendu après déploiement propre :

```text
working tree clean
HEAD sur le tag recette attendu
```

---

## 5. Vérifier l’état Symfony / Doctrine / cache

```bash
php bin/console about --env=prod
php bin/console doctrine:migrations:status --env=prod
php bin/console doctrine:schema:validate --env=prod
```

Si besoin, reconstruire le cache prod :

```bash
php -d memory_limit=-1 bin/console cache:clear --env=prod --no-warmup
php -d memory_limit=-1 bin/console cache:warmup --env=prod
```

---

## 6. Logs disponibles sur recette

### 6.1 Logs Symfony

Symfony est configuré via Monolog pour écrire dans :

```text
var/log/prod.log
```

Créer le fichier si absent :

```bash
mkdir -p var/log
touch var/log/prod.log
chmod 664 var/log/prod.log
```

Lire le log Symfony :

```bash
tail -n 120 var/log/prod.log
```

Rechercher les erreurs :

```bash
grep -nEi "critical|error|exception|fatal|memory|allowed memory|timeout|csrf|session|security" var/log/prod.log | tail -n 120
```

Important : si Monolog utilise `fingers_crossed`, Symfony peut ne rien écrire tant qu’aucune vraie erreur applicative n’est déclenchée.

---

### 6.2 Logs PHP web

Sur o2switch, les erreurs PHP web peuvent partir dans :

```text
public/error_log
```

Même si un `.user.ini` tente de forcer `error_log` ailleurs, le test a montré que le log réellement utilisé peut rester `public/error_log`.

Lire les erreurs PHP web :

```bash
tail -n 120 public/error_log 2>/dev/null
```

Rechercher les erreurs récentes :

```bash
grep -nEi "fatal|warning|parse|memory|timeout|uncaught|exception|segmentation|killed" public/error_log 2>/dev/null | tail -n 120
```

Attention : des erreurs anciennes de février 2026 existent dans `public/error_log`. Elles concernent l’ancien contexte `/home/vopu3712/hodina.fr` et PHP 8.3. Elles ne doivent pas être confondues avec un incident recette actuel.

---

### 6.3 Logs Messenger et cron paiements

```bash
tail -n 120 var/log/messenger_cron.log 2>/dev/null
tail -n 120 var/log/courier_payout_cron.log 2>/dev/null
```

Ces logs servent surtout à vérifier les tâches asynchrones. Ils ne prouvent pas un crash web, mais peuvent révéler un problème de worker ou d’e-mail.

---

### 6.4 Logs Apache / o2switch live

Les logs live sont accessibles via :

```bash
~/access-logs
```

Lister les fichiers disponibles :

```bash
ls -lah ~/access-logs
find ~/access-logs -maxdepth 1 -type f -iname "*recette*" -print
```

Lire les logs live HTTPS :

```bash
tail -n 120 ~/access-logs/recette.hodina.fr-ssl_log 2>/dev/null
```

Chercher les vraies erreurs HTTP ou serveur :

```bash
grep -iE " 500 | 502 | 503 | 504 |error|fatal|timeout|mod_security|modsecurity|lsapi|litespeed|killed|closed|reset" ~/access-logs/recette.hodina.fr* 2>/dev/null | tail -n 120
```

Note : éviter une recherche trop large sur `500` seul, car cela remonte aussi les réponses `200 500` où `500` est une taille en octets.

---

### 6.5 Logs compressés mensuels

Les logs historiques compressés sont dans :

```bash
~/logs
```

Lister :

```bash
ls -lah ~/logs
```

Chercher les erreurs dans les logs SSL compressés :

```bash
zgrep -iE " 500 | 502 | 503 | 504 |error|fatal|premature|timeout|mod_security|modsecurity|lsapi|litespeed|killed|segmentation|closed|reset" ~/logs/recette.hodina.fr-ssl_log-Jun-2026.gz | tail -n 120
```

---

## 7. Vérifier le PHP réellement utilisé par le web

La CLI peut utiliser PHP 8.4 alors que le web utiliserait une autre version. Il faut donc tester via HTTP.

Créer un fichier temporaire :

```bash
cat > public/_health_php_tmp.php <<'PHP'
<?php
header('Content-Type: text/plain; charset=UTF-8');
echo 'php_version=' . PHP_VERSION . PHP_EOL;
echo 'memory_limit=' . ini_get('memory_limit') . PHP_EOL;
echo 'max_execution_time=' . ini_get('max_execution_time') . PHP_EOL;
echo 'session_save_path=' . session_save_path() . PHP_EOL;
echo 'date=' . date('c') . PHP_EOL;
PHP
```

Tester :

```bash
curl -sS https://recette.hodina.fr/_health_php_tmp.php
```

Supprimer immédiatement :

```bash
rm -f public/_health_php_tmp.php
```

Résultat attendu au 25/06/2026 :

```text
php_version=8.4.21
memory_limit=512M
max_execution_time=600
session_save_path=/opt/alt/php84/var/lib/php/session
```

---

## 8. Tester si PHP web écrit ses logs

Créer un test temporaire :

```bash
cat > public/_log_test.php <<'PHP'
<?php
header('Content-Type: text/plain; charset=UTF-8');
error_log('HODINA_WEB_LOG_TEST '.date('c'));
trigger_error('HODINA_TRIGGER_WARNING', E_USER_WARNING);
echo "ok\n";
PHP
```

Appeler :

```bash
curl -sS https://recette.hodina.fr/_log_test.php
```

Supprimer :

```bash
rm -f public/_log_test.php
```

Vérifier :

```bash
tail -n 30 public/error_log
```

Si les lignes `HODINA_WEB_LOG_TEST` apparaissent, le log PHP web fonctionne.

---

## 9. Tester la prise en compte de `.user.ini`

Créer un fichier temporaire :

```bash
cat > public/_ini_check.php <<'PHP'
<?php
header('Content-Type: text/plain; charset=UTF-8');
echo 'php_version=' . PHP_VERSION . PHP_EOL;
echo 'log_errors=' . ini_get('log_errors') . PHP_EOL;
echo 'error_log=' . ini_get('error_log') . PHP_EOL;
echo 'display_errors=' . ini_get('display_errors') . PHP_EOL;
echo 'error_reporting=' . ini_get('error_reporting') . PHP_EOL;
echo 'user_ini.filename=' . ini_get('user_ini.filename') . PHP_EOL;
echo 'user_ini.cache_ttl=' . ini_get('user_ini.cache_ttl') . PHP_EOL;
PHP
```

Tester :

```bash
curl -sS https://recette.hodina.fr/_ini_check.php
```

Supprimer :

```bash
rm -f public/_ini_check.php
```

Observation du 25/06/2026 :

```text
user_ini.filename=.user.ini
user_ini.cache_ttl=300
error_log=error_log
```

Cela signifie que PHP reconnaît `.user.ini`, mais le chemin `error_log` personnalisé peut ne pas être appliqué. Dans ce cas, surveiller `public/error_log`.

---

## 10. Tester le site depuis le serveur

Accueil :

```bash
for i in 1 2 3 4 5; do
  echo "=== accueil $i ==="
  curl -I -L --max-time 20 https://recette.hodina.fr/
done
```

Admin :

```bash
for i in 1 2 3 4 5; do
  echo "=== admin $i ==="
  curl -I -L --max-time 20 https://recette.hodina.fr/ouegnewe
done
```

Login :

```bash
for i in $(seq 1 20); do
  echo "=== hodi $i ==="
  curl -sS -I -L --max-time 15 https://recette.hodina.fr/hodi | head -n 12
done
```

Si ces tests passent tous mais que le navigateur mobile affiche `ERR_CONNECTION_CLOSED`, la piste réseau mobile / navigateur / proxy devient plus probable.

---

## 11. Procédure juste après un plantage

Dès qu’un plantage arrive, lancer immédiatement :

```bash
cd ~/recette.hodina.fr

echo "=== date ==="
date

echo "=== git ==="
git log --oneline -1
git status --short

echo "=== Symfony prod.log ==="
tail -n 120 var/log/prod.log 2>/dev/null

echo "=== PHP public/error_log ==="
tail -n 120 public/error_log 2>/dev/null

echo "=== messenger ==="
tail -n 80 var/log/messenger_cron.log 2>/dev/null

echo "=== access log live ==="
tail -n 120 ~/access-logs/recette.hodina.fr-ssl_log 2>/dev/null
```

Puis noter dans le retour :

```text
Heure du plantage :
URL :
Action :
Navigateur :
Réseau : Wi-Fi / 4G / 5G / VPN / relais privé iCloud
Message navigateur :
```

---

## 12. Interprétation rapide

### Cas A — `prod.log` contient une exception Symfony

Alors c’est probablement une erreur applicative.

Actions :

1. lire la stack trace ;
2. identifier le contrôleur/service/template ;
3. vérifier si c’est lié au dernier patch ;
4. corriger en patch ciblé ;
5. tester local + recette.

---

### Cas B — `public/error_log` contient une fatal error PHP

Alors le crash arrive au niveau PHP web.

Actions :

1. lire la ligne exacte ;
2. vérifier la version PHP web ;
3. vérifier mémoire et timeout ;
4. vérifier autoload/vendor/cache ;
5. corriger ou redéployer.

---

### Cas C — access-log montre un vrai `500`, `502`, `503` ou `504`

Alors il faut regarder :

- `public/error_log` ;
- `var/log/prod.log` ;
- logs o2switch ;
- ressources serveur ;
- ModSecurity / LSAPI.

---

### Cas D — access-log montre `200` ou `302`, mais navigateur affiche `ERR_CONNECTION_CLOSED`

Alors le serveur a probablement répondu correctement, et la coupure peut venir de :

- réseau mobile instable ;
- navigateur mobile ;
- proxy opérateur ;
- HTTP/2/TLS ;
- relais privé iCloud / VPN ;
- extension navigateur ;
- session expirée / double navigation.

Tester :

1. Wi-Fi ;
2. 5G ;
3. Chrome ;
4. Safari ;
5. Brave ;
6. Firefox ;
7. navigation privée ;
8. sans VPN / sans relais privé iCloud.

---

### Cas E — aucune trace dans les logs

Si aucun log ne bouge au moment du plantage, il est probable que la requête n’arrive pas jusqu’à PHP/Symfony, ou qu’elle soit coupée avant traitement.

Actions :

1. comparer avec les access logs ;
2. tester depuis une autre connexion ;
3. tester depuis PC ;
4. demander à o2switch s’il y a des erreurs LSAPI/ModSecurity autour de l’heure exacte.

---

## 13. Nettoyage des fichiers temporaires de diagnostic

Toujours supprimer les fichiers publics de test :

```bash
rm -f public/_health_php_tmp.php
rm -f public/_ini_check.php
rm -f public/_log_test.php
```

Vérifier :

```bash
find public -maxdepth 1 -type f -name "_*php" -print
```

Le résultat doit être vide.

---

## 14. Fichiers à ne pas committer

Ne jamais committer :

```text
public/error_log
public/.user.ini
var/log/*.log
var/cache/
var/backups/
.env.local
prod.env.local
```

`public/.user.ini` peut être utile temporairement en recette, mais il doit rester runtime, sauf décision explicite de versionner une configuration web portable.

---

## 15. Dette technique observabilité à prévoir

Créer un lot dédié si les coupures continuent :

```text
J5Q-C-2-bis — Observabilité recette
```

Objectifs possibles :

- documenter les logs o2switch ;
- ajouter une commande `hodina:diagnostic:runtime` ;
- ajouter un contrôle de logs dans le script de déploiement ;
- vérifier la présence de `var/log/prod.log` ;
- vérifier que `public/error_log` n’est pas trop gros ;
- ajouter une page admin interne de diagnostic minimal ;
- ajouter une commande pour tester mailer, DB, sessions et cache.

---

## 16. Commande mémo complète

Commande rapide à lancer après incident :

```bash
cd ~/recette.hodina.fr && \
echo "=== DATE ===" && date && \
echo "=== GIT ===" && git log --oneline -1 && git status --short && \
echo "=== SYMFONY PROD ===" && tail -n 120 var/log/prod.log 2>/dev/null && \
echo "=== PHP WEB ===" && tail -n 120 public/error_log 2>/dev/null && \
echo "=== ACCESS LIVE ===" && tail -n 120 ~/access-logs/recette.hodina.fr-ssl_log 2>/dev/null
```

---

## 17. Décision actuelle

Au 25/06/2026, rien ne justifie un rollback de `J5Q-C-2 — Branding e-mail paramétrable`.

Les éléments connus indiquent :

- déploiement propre ;
- pas d’erreur Doctrine ;
- pas d’erreur Twig ;
- PHP web correct ;
- logs d’accès majoritairement normaux ;
- coupure observée surtout côté navigateur mobile.

La priorité est de capturer le prochain incident avec :

```text
public/error_log
var/log/prod.log
~/access-logs/recette.hodina.fr-ssl_log
```


---

## Annexe 25/06/2026 — constats réels recette

Constats issus du diagnostic après le déploiement `j5q-c2-branding-email-recette` :

```text
PHP web réel        : 8.4.21
memory_limit        : 512M
max_execution_time  : 600
session_save_path   : /opt/alt/php84/var/lib/php/session
```

`public/.user.ini` est détecté (`user_ini.filename=.user.ini`, `user_ini.cache_ttl=300`), mais la directive de redirection `error_log=/home/.../var/log/php_web_error.log` n'a pas été prise en compte : `ini_get('error_log')` retourne `error_log`.

Le test `error_log()` écrit donc dans :

```text
public/error_log
```

La configuration Monolog prod actuelle utilise `php://stderr` pour le handler principal en prod. Sur o2switch, cela peut aussi aboutir dans `public/error_log` plutôt que dans `var/log/prod.log`.

Conclusion opérationnelle :

- surveiller `public/error_log` pour les erreurs PHP web et stderr ;
- surveiller `~/access-logs/recette.hodina.fr-ssl_log` pour les statuts HTTP ;
- ne pas interpréter `200 500` comme une erreur HTTP 500 ;
- ne pas supposer que `var/log/prod.log` est alimenté tant que Monolog n'est pas modifié.
