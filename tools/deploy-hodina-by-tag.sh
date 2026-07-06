#!/usr/bin/env bash
set -Eeuo pipefail

# ==============================================================================
# Hodina - Déploiement robuste par tag Git issu de main
# ==============================================================================
#
# Objectif :
#   - script commun recette / production ;
#   - déploiement uniquement par tag Git ;
#   - le tag doit être contenu dans origin/main ;
#   - contrôles explicites à chaque étape ;
#   - protection des fichiers d'environnement locaux avant checkout Git ;
#   - protection des uploads runtime public/uploads/products avant checkout Git ;
#   - backup DB avant migration ;
#   - migrations Doctrine ;
#   - compilation des assets Symfony AssetMapper en prod ;
#   - cache clear prod sans warmup direct, puis cache warmup séparé ;
#   - validation Doctrine ;
#   - cron Messenger contrôlé / ajouté ;
#   - nettoyage optionnel des anciennes commandes ;
#   - compatible futur CI/CD avec ASSUME_YES=1.
#
# Usage recette :
#   bash tools/deploy-hodina-by-tag.sh \
#     --project-dir /home/vopu3712/recette.hodina.fr \
#     --tag j5g-b4-20260618 \
#     --target recette
#
# Usage production :
#   bash tools/deploy-hodina-by-tag.sh \
#     --project-dir /home/vopu3712/hodina.fr \
#     --tag j5g-b4-20260618 \
#     --target prod
#
# Variables d'environnement :
#   RUN_COMPOSER=1      Lance composer install --no-dev --optimize-autoloader avant le premier bin/console.
#   RESET_COMMANDS=1    Supprime anciennes commandes + logs liés.
#   SKIP_BACKUP=1       Ignore le backup DB. Déconseillé en prod.
#   ASSUME_YES=1        Mode non interactif / CI-CD : confirme les prompts.
#   DRY_RUN=1           Simulation partielle : pas de checkout, pas de SQL, pas de crontab.
#   ENFORCE_SSH=0       Ne force pas origin en SSH. Déconseillé.
#   PHP_BIN=/path/php   Surcharge le binaire PHP.
#   PHP_MEMORY_LIMIT=-1  Limite mémoire PHP appliquée aux commandes Symfony du déploiement. Défaut : -1.
#   MYSQLDUMP_BIN=/path/mariadb-dump  Surcharge le binaire dump DB. Auto-détecté sinon.
#   PUBLIC_URL=https://...  Test HTTP optionnel de l'URL publique dans le résumé final.
#
# Règles Hodina :
#   - recette et production déploient un tag ;
#   - le tag doit provenir de main ;
#   - on ne déploie pas une branche mouvante en production ;
#   - Symfony tourne en APP_ENV=prod, même sur recette ;
#   - public/uploads/products contient des fichiers métier runtime, protégés comme les env locaux ;
#   - public/assets est généré par asset-map:compile et ne doit pas bloquer un déploiement.

PROJECT_DIR=""
TAG=""
TARGET=""
MAIN_BRANCH="${MAIN_BRANCH:-main}"
PHP_BIN="${PHP_BIN:-/usr/local/bin/php}"
PHP_MEMORY_LIMIT="${PHP_MEMORY_LIMIT:--1}"
MYSQLDUMP_BIN="${MYSQLDUMP_BIN:-}"
RUN_COMPOSER="${RUN_COMPOSER:-0}"
RESET_COMMANDS="${RESET_COMMANDS:-0}"
SKIP_BACKUP="${SKIP_BACKUP:-0}"
ASSUME_YES="${ASSUME_YES:-0}"
DRY_RUN="${DRY_RUN:-0}"
ENFORCE_SSH="${ENFORCE_SSH:-1}"
PUBLIC_URL="${PUBLIC_URL:-}"
BACKUP_DIR=""

CURRENT_STEP="initialisation"
PREVIOUS_REF=""
DEPLOYED_REF=""
ENV_BACKUP_DIR=""
RUNTIME_BACKUP_DIR=""
RUNTIME_PARKING_DIR=""
RUNTIME_UPLOAD_PATHS=("public/uploads/products")
SUMMARY_LINES=()
COMPOSER_INSTALL_RAN=0

usage() {
  cat <<'EOF'
Usage:
  bash tools/deploy-hodina-by-tag.sh --project-dir <path> --tag <git-tag> --target <recette|prod>

Arguments obligatoires :
  --project-dir   Dossier projet sur le serveur.
  --tag           Tag Git à déployer.
  --target        recette ou prod.

Options :
  --main-branch   Branche source attendue. Défaut : main.
  --php-bin       Chemin du binaire PHP. Défaut : /usr/local/bin/php.
  -h, --help      Affiche cette aide.

Variables d'environnement :
  RUN_COMPOSER=1      Lance composer install --no-dev --optimize-autoloader avant le premier bin/console.
  RESET_COMMANDS=1    Supprime les anciennes commandes et logs liés.
  SKIP_BACKUP=1       Ignore le backup DB. Déconseillé en prod.
  ASSUME_YES=1        Confirme automatiquement les prompts, utile CI/CD.
  DRY_RUN=1           Simulation partielle : pas de checkout, pas de SQL, pas de crontab.
  ENFORCE_SSH=0       Ne force pas origin en SSH.
  MYSQLDUMP_BIN=/path/mariadb-dump  Surcharge le binaire dump DB. Auto-détecté sinon.
  PHP_MEMORY_LIMIT=-1  Limite mémoire PHP appliquée aux commandes Symfony du déploiement.
  PUBLIC_URL=https://...  Test HTTP optionnel de l'URL publique dans le résumé final.
EOF
}

on_error() {
  local exit_code=$?
  echo ""
  echo "[ERREUR] Étape : $CURRENT_STEP"
  echo "         Code retour : $exit_code"
  echo "         Ligne : ${BASH_LINENO[0]:-inconnue}"
  echo ""
  if [ -n "${PREVIOUS_REF:-}" ]; then
    echo "Référence avant déploiement : $PREVIOUS_REF"
  fi
  if [ -n "${DEPLOYED_REF:-}" ]; then
    echo "Référence en cours de déploiement : $DEPLOYED_REF"
  fi
  if [ -n "${ENV_BACKUP_DIR:-}" ] && [ -d "$ENV_BACKUP_DIR" ]; then
    echo "Backup env local : $ENV_BACKUP_DIR"
  fi
  if [ -n "${RUNTIME_BACKUP_DIR:-}" ] && [ -d "$RUNTIME_BACKUP_DIR" ]; then
    echo "Backup uploads runtime : $RUNTIME_BACKUP_DIR"
  fi
  if [ -n "${RUNTIME_PARKING_DIR:-}" ] && [ -d "$RUNTIME_PARKING_DIR" ]; then
    echo "Parking uploads runtime : $RUNTIME_PARKING_DIR"
    if [ -d "${PROJECT_DIR:-}" ]; then
      cd "$PROJECT_DIR" 2>/dev/null || true
      local dir
      for dir in "${RUNTIME_UPLOAD_PATHS[@]}"; do
        if [ -d "$RUNTIME_PARKING_DIR/$dir" ] && [ ! -d "$dir" ]; then
          mkdir -p "${dir%/*}" 2>/dev/null || true
          mv "$RUNTIME_PARKING_DIR/$dir" "$dir" 2>/dev/null || true
          echo "Uploads runtime restaurés depuis le parking après erreur : $dir"
        fi
      done
    fi
  fi
  echo ""
  echo "Actions conseillées :"
  echo "  1. Lire le message exact juste au-dessus de cette erreur."
  echo "  2. Lancer : git status && git log --oneline -5"
  echo "  3. Vérifier les fichiers env locaux : ls -la .env.local prod.env.local .env.prod.local 2>/dev/null"
  echo "  4. Vérifier les uploads runtime : ls -la public/uploads/products 2>/dev/null"
  echo "  5. Vérifier : var/log/prod.log et var/log/messenger_cron.log"
  echo "  6. Ne pas relancer en production sans comprendre l'échec."
  exit "$exit_code"
}
trap on_error ERR

log() {
  CURRENT_STEP="$1"
  echo ""
  echo "================================================================================"
  echo "> $1"
  echo "================================================================================"
}

info() { echo "[INFO] $*"; }
ok() { echo "[OK] $*"; }
warn() { echo "[WARN] $*"; }

record_check() {
  local status="$1"
  local label="$2"
  local detail="${3:-}"

  if [ -n "$detail" ]; then
    SUMMARY_LINES+=("  [$status] $label : $detail")
  else
    SUMMARY_LINES+=("  [$status] $label")
  fi
}

record_ok() { record_check "OK" "$1" "${2:-}"; }
record_warn() { record_check "WARN" "$1" "${2:-}"; }

print_check_summary() {
  echo ""
  echo "Contrôles automatiques confirmés :"

  if [ "${#SUMMARY_LINES[@]}" -eq 0 ]; then
    echo "  [WARN] Aucun contrôle de synthèse enregistré."
    return 0
  fi

  local line
  for line in "${SUMMARY_LINES[@]}"; do
    echo "$line"
  done
}

fail() {
  echo ""
  echo "[ERREUR] $*"
  echo ""
  exit 1
}

run() {
  echo "+ $*"
  "$@"
}

php_console() {
  "$PHP_BIN" -d "memory_limit=$PHP_MEMORY_LIMIT" bin/console "$@"
}

run_console() {
  echo "+ $PHP_BIN -d memory_limit=$PHP_MEMORY_LIMIT bin/console $*"
  php_console "$@"
}

run_composer_install() {
  local reason="${1:-Installation Composer}"

  require_cmd composer
  info "$reason"

  if [ "$DRY_RUN" = "1" ]; then
    warn "DRY_RUN=1 : composer install non exécuté."
    return 0
  fi

  run composer install --no-dev --optimize-autoloader --no-interaction
  COMPOSER_INSTALL_RAN=1
}

run_sql() {
  local sql="$1"
  echo "+ $PHP_BIN -d memory_limit=$PHP_MEMORY_LIMIT bin/console dbal:run-sql \"$sql\""
  if [ "$DRY_RUN" = "1" ]; then
    warn "DRY_RUN=1 : SQL non exécuté."
  else
    php_console dbal:run-sql "$sql"
  fi
}

confirm_or_abort() {
  local message="$1"
  echo ""
  warn "$message"

  if [ "$ASSUME_YES" = "1" ]; then
    warn "ASSUME_YES=1 : confirmation automatique."
    return 0
  fi

  read -r -p "Tape OUI pour confirmer : " answer
  if [ "$answer" != "OUI" ]; then
    fail "Action annulée par sécurité."
  fi
}

require_arg() {
  local value="$1"
  local name="$2"
  if [ -z "$value" ]; then
    usage
    fail "Argument obligatoire manquant : $name"
  fi
}

require_cmd() {
  local cmd="$1"
  command -v "$cmd" >/dev/null 2>&1 || fail "Commande introuvable : $cmd"
}

resolve_command_path() {
  local cmd="$1"
  command -v "$cmd" 2>/dev/null || true
}

resolve_required_command_path() {
  local cmd="$1"
  local resolved
  resolved="$(resolve_command_path "$cmd")"

  if [ -z "$resolved" ]; then
    fail "Commande introuvable : $cmd"
  fi

  printf '%s\n' "$resolved"
}

resolve_configured_or_required_binary() {
  local label="$1"
  local configured="$2"
  shift 2

  local resolved=""

  if [ -n "$configured" ]; then
    if [ -x "$configured" ]; then
      printf '%s\n' "$configured"
      return 0
    fi

    resolved="$(resolve_command_path "$configured")"
    if [ -n "$resolved" ] && [ -x "$resolved" ]; then
      printf '%s\n' "$resolved"
      return 0
    fi

    fail "$label introuvable ou non exécutable : $configured"
  fi

  local candidate
  for candidate in "$@"; do
    resolved="$(resolve_command_path "$candidate")"
    if [ -n "$resolved" ] && [ -x "$resolved" ]; then
      printf '%s\n' "$resolved"
      return 0
    fi
  done

  fail "$label introuvable. Candidats testés : $*"
}

resolve_deploy_binaries() {
  local commands=(
    git
    grep
    sed
    awk
    date
    mkdir
    mktemp
    crontab
    flock
    cmp
    cp
    mv
    find
    tail
  )

  local cmd
  local resolved
  for cmd in "${commands[@]}"; do
    resolved="$(resolve_required_command_path "$cmd")"
    info "$cmd : $resolved"
  done

  PHP_BIN="$(resolve_configured_or_required_binary "PHP" "$PHP_BIN" php)"
  info "PHP utilisé : $PHP_BIN"
  info "Limite mémoire PHP pour bin/console : $PHP_MEMORY_LIMIT"

  if [ "$SKIP_BACKUP" = "1" ]; then
    info "Backup DB ignoré : SKIP_BACKUP=1, résolution du binaire dump DB non nécessaire."
  else
    if MYSQLDUMP_BIN="$(resolve_mysqldump_bin)"; then
      info "Dump DB utilisé : $MYSQLDUMP_BIN"
    else
      warn "Aucun binaire dump DB trouvé automatiquement. Le script proposera le backup manuel si nécessaire."
    fi
  fi
}

is_runtime_upload_path() {
  local path="$1"
  case "$path" in
    public/uploads/products|public/uploads/products/|public/uploads/products/*)
      return 0
      ;;
    public/uploads|public/uploads/)
      # Git peut condenser les fichiers non suivis sous "?? public/uploads/".
      # On l'autorise uniquement si tout ce qui est dessous appartient au runtime produits.
      if [ -d "public/uploads" ] && ! find public/uploads -type f ! -path 'public/uploads/products/*' | grep -q .; then
        return 0
      fi
      return 1
      ;;
    *)
      return 1
      ;;
  esac
}

is_generated_asset_path() {
  local path="$1"
  case "$path" in
    public/assets|public/assets/|public/assets/*)
      return 0
      ;;
    *)
      return 1
      ;;
  esac
}

is_allowed_dirty_path() {
  local path="$1"
  case "$path" in
    .env.local|.env.prod.local|prod.env.local)
      return 0
      ;;
    *)
      if is_runtime_upload_path "$path"; then
        return 0
      fi
      if is_generated_asset_path "$path"; then
        return 0
      fi
      return 1
      ;;
  esac
}

blocking_git_status_lines() {
  git status --porcelain | while IFS= read -r line; do
    [ -z "$line" ] && continue
    local path
    path="${line:3}"
    # Gestion simple des renommages porcelain : "R  old -> new".
    case "$path" in
      *" -> "*) path="${path##* -> }" ;;
    esac

    if ! is_allowed_dirty_path "$path"; then
      printf '%s\n' "$line"
    fi
  done
}

backup_local_env_files() {
  log "5. Protection des fichiers d'environnement locaux"

  ENV_BACKUP_DIR="$PROJECT_DIR/var/deploy_env_backup/$TIMESTAMP"
  mkdir -p "$ENV_BACKUP_DIR"

  local files=(".env.local" ".env.prod.local" "prod.env.local")
  local copied=0

  for f in "${files[@]}"; do
    if [ -f "$f" ]; then
      cp -p "$f" "$ENV_BACKUP_DIR/$f"
      ok "Fichier protégé : $f -> $ENV_BACKUP_DIR/$f"
      copied=1
    fi
  done

  if [ "$copied" = "0" ]; then
    warn "Aucun fichier env local trouvé à protéger."
    if [ "$TARGET" = "prod" ]; then
      confirm_or_abort "Aucun .env.local/prod.env.local détecté en production. Continuer quand même ?"
    fi
  fi

  local tracked_env
  tracked_env="$(git ls-files .env.local .env.prod.local prod.env.local || true)"
  if [ -n "$tracked_env" ]; then
    warn "Des fichiers env locaux sont suivis par Git :"
    echo "$tracked_env"
    warn "Ce n'est pas idéal. Le script les restaure après checkout si nécessaire. À corriger plus tard : les sortir du suivi Git."
  else
    ok "Aucun fichier env local sensible n'est suivi par Git."
  fi
}

restore_local_env_files() {
  log "8. Restauration / contrôle des fichiers d'environnement locaux"

  if [ -z "${ENV_BACKUP_DIR:-}" ] || [ ! -d "$ENV_BACKUP_DIR" ]; then
    warn "Aucun backup env disponible à restaurer."
    return 0
  fi

  shopt -s nullglob
  local backups=("$ENV_BACKUP_DIR"/*)
  shopt -u nullglob

  if [ "${#backups[@]}" -eq 0 ]; then
    warn "Backup env vide."
    return 0
  fi

  for src in "${backups[@]}"; do
    local dest
    dest="$(basename "$src")"

    if [ -f "$dest" ]; then
      if cmp -s "$src" "$dest"; then
        ok "$dest déjà présent et identique."
      else
        warn "$dest existe mais diffère du backup. Conservation du fichier actuel, backup disponible : $src"
      fi
    else
      cp -p "$src" "$dest"
      ok "$dest restauré depuis $src"
    fi
  done

  if [ ! -f ".env.local" ] && [ ! -f ".env.prod.local" ] && [ ! -f "prod.env.local" ]; then
    fail "Aucun fichier env local disponible après checkout. Risque de DATABASE_URL manquant."
  fi

}

backup_runtime_uploads() {
  log "5bis. Protection des uploads runtime"

  RUNTIME_BACKUP_DIR="$PROJECT_DIR/var/deploy_runtime_backup/$TIMESTAMP"
  mkdir -p "$RUNTIME_BACKUP_DIR"

  local copied=0
  local dir

  for dir in "${RUNTIME_UPLOAD_PATHS[@]}"; do
    if [ -d "$dir" ]; then
      local backup_parent
      backup_parent="$RUNTIME_BACKUP_DIR/${dir%/*}"
      mkdir -p "$backup_parent"
      cp -a "$dir" "$backup_parent/"
      ok "Uploads protégés : $dir -> $RUNTIME_BACKUP_DIR/$dir"
      copied=1
    else
      warn "Dossier uploads runtime absent : $dir"
    fi
  done

  if [ "$copied" = "0" ]; then
    warn "Aucun upload runtime trouvé à protéger. Le dossier sera recréé si nécessaire."
  fi

  local tracked_uploads
  tracked_uploads="$(git ls-files public/uploads/products 2>/dev/null || true)"
  if [ -n "$tracked_uploads" ]; then
    warn "Des uploads produits sont suivis par Git :"
    echo "$tracked_uploads"
    warn "Ce n'est pas idéal. À corriger plus tard : public/uploads/products doit être une donnée runtime hors Git."
  else
    ok "Aucun upload produit n'est suivi par Git."
  fi
}

park_runtime_uploads_before_checkout() {
  if [ "$DRY_RUN" = "1" ]; then
    warn "DRY_RUN=1 : uploads runtime non déplacés avant checkout."
    return 0
  fi

  RUNTIME_PARKING_DIR="$PROJECT_DIR/var/deploy_runtime_parking/$TIMESTAMP"
  mkdir -p "$RUNTIME_PARKING_DIR"

  local moved=0
  local dir

  for dir in "${RUNTIME_UPLOAD_PATHS[@]}"; do
    if [ -d "$dir" ]; then
      local parking_parent
      parking_parent="$RUNTIME_PARKING_DIR/${dir%/*}"
      mkdir -p "$parking_parent"
      mv "$dir" "$parking_parent/"
      ok "Uploads runtime temporairement mis de côté avant checkout : $dir -> $RUNTIME_PARKING_DIR/$dir"
      moved=1
    fi
  done

  if [ "$moved" = "0" ]; then
    info "Aucun upload runtime à déplacer avant checkout."
  fi
}

restore_runtime_uploads() {
  log "8bis. Restauration / contrôle des uploads runtime"

  local restored=0
  local dir

  if [ -n "${RUNTIME_BACKUP_DIR:-}" ] && [ -d "$RUNTIME_BACKUP_DIR" ]; then
    for dir in "${RUNTIME_UPLOAD_PATHS[@]}"; do
      local src
      src="$RUNTIME_BACKUP_DIR/$dir"

      if [ -d "$src" ]; then
        mkdir -p "$dir"
        cp -a "$src/." "$dir/"
        ok "Uploads runtime restaurés : $src -> $dir"
        restored=1
      fi
    done
  fi

  if [ "$restored" = "0" ]; then
    warn "Aucun backup upload runtime restauré. Création des dossiers runtime si nécessaire."
  fi

  for dir in "${RUNTIME_UPLOAD_PATHS[@]}"; do
    mkdir -p "$dir"
    if [ -d "$dir" ]; then
      ok "Dossier runtime disponible : $dir"
    else
      fail "Impossible de créer le dossier runtime : $dir"
    fi
  done
}


resolve_mysqldump_bin() {
  local resolved=""

  if [ -n "${MYSQLDUMP_BIN:-}" ]; then
    if [ -x "$MYSQLDUMP_BIN" ]; then
      printf '%s\n' "$MYSQLDUMP_BIN"
      return 0
    fi

    resolved="$(resolve_command_path "$MYSQLDUMP_BIN")"
    if [ -n "$resolved" ] && [ -x "$resolved" ]; then
      printf '%s\n' "$resolved"
      return 0
    fi

    warn "MYSQLDUMP_BIN est défini mais introuvable ou non exécutable : $MYSQLDUMP_BIN"
    return 1
  fi

  # On privilégie mariadb-dump, car mysqldump est désormais un alias déprécié sur o2switch/MariaDB.
  resolved="$(resolve_command_path mariadb-dump)"
  if [ -n "$resolved" ] && [ -x "$resolved" ]; then
    printf '%s\n' "$resolved"
    return 0
  fi

  resolved="$(resolve_command_path mysqldump)"
  if [ -n "$resolved" ] && [ -x "$resolved" ]; then
    printf '%s\n' "$resolved"
    return 0
  fi

  return 1
}

backup_database_with_mysqldump() {
  local backup_file="$1"
  local dump_bin="$2"
  local tmp_defaults
  local tmp_php
  local db_name

  tmp_defaults="$(mktemp)"
  tmp_php="$(mktemp)"
  chmod 600 "$tmp_defaults" "$tmp_php"

  cat > "$tmp_php" <<'PHP'
<?php

$defaultsFile = $argv[1] ?? null;
if (!$defaultsFile) {
    fwrite(STDERR, "Fichier temporaire my.cnf manquant.\n");
    exit(1);
}

function normalizeEnvValue(string $value): string
{
    $value = trim($value);

    if (
        (str_starts_with($value, '"') && str_ends_with($value, '"'))
        || (str_starts_with($value, "'") && str_ends_with($value, "'"))
    ) {
        $value = substr($value, 1, -1);
    }

    return $value;
}

function readDatabaseUrlFromFile(string $file): ?string
{
    if (!is_file($file) || !is_readable($file)) {
        return null;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return null;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        if (trim($key) !== 'DATABASE_URL') {
            continue;
        }

        return normalizeEnvValue($value);
    }

    return null;
}

$candidates = [];
$envDatabaseUrl = getenv('DATABASE_URL') ?: '';
if ($envDatabaseUrl !== '') {
    $candidates[] = ['env:DATABASE_URL', normalizeEnvValue($envDatabaseUrl)];
}

// Ordre volontaire : on privilégie les fichiers réellement chargés par Symfony.
// prod.env.local est conservé seulement en fallback legacy, car Symfony ne le charge pas automatiquement.
foreach (['.env.local', '.env.prod.local', '.env.prod', '.env', 'prod.env.local'] as $file) {
    $value = readDatabaseUrlFromFile($file);
    if ($value !== null && $value !== '') {
        $candidates[] = [$file, $value];
    }
}

if ($candidates === []) {
    fwrite(STDERR, "DATABASE_URL introuvable dans l'environnement ou les fichiers .env*.\n");
    exit(2);
}

$selected = null;
foreach ($candidates as [$source, $databaseUrl]) {
    $parts = parse_url($databaseUrl);
    if ($parts === false) {
        continue;
    }

    $scheme = $parts['scheme'] ?? '';
    if (!in_array($scheme, ['mysql', 'mariadb'], true)) {
        continue;
    }

    $user = isset($parts['user']) ? rawurldecode($parts['user']) : '';
    $dbName = isset($parts['path']) ? ltrim(rawurldecode($parts['path']), '/') : '';

    if ($user === '' || $dbName === '') {
        continue;
    }

    $selected = [$source, $databaseUrl, $parts];
    break;
}

if ($selected === null) {
    fwrite(STDERR, "Aucune DATABASE_URL mysql/mariadb exploitable trouvée.\n");
    exit(3);
}

[$source, $databaseUrl, $parts] = $selected;
fwrite(STDERR, "DATABASE_URL retenue pour backup : ".$source."\n");

$user = isset($parts['user']) ? rawurldecode($parts['user']) : '';
$password = isset($parts['pass']) ? rawurldecode($parts['pass']) : '';
$host = isset($parts['host']) ? rawurldecode($parts['host']) : 'localhost';
$port = isset($parts['port']) ? (string) $parts['port'] : '';
$dbName = isset($parts['path']) ? ltrim(rawurldecode($parts['path']), '/') : '';

$query = [];
if (isset($parts['query'])) {
    parse_str($parts['query'], $query);
}
$socket = isset($query['unix_socket']) ? (string) $query['unix_socket'] : '';

$lines = [
    '[client]',
    'user='.$user,
    'password='.$password,
];

if ($socket !== '') {
    $lines[] = 'socket='.$socket;
} else {
    $lines[] = 'host='.$host;
    if ($port !== '') {
        $lines[] = 'port='.$port;
    }
}

if (file_put_contents($defaultsFile, implode(PHP_EOL, $lines).PHP_EOL) === false) {
    fwrite(STDERR, "Impossible d'écrire le fichier temporaire my.cnf.\n");
    exit(6);
}

echo $dbName;
PHP

  set +e
  db_name="$("$PHP_BIN" "$tmp_php" "$tmp_defaults")"
  local php_status=$?
  set -e

  rm -f "$tmp_php"

  if [ "$php_status" -ne 0 ] || [ -z "$db_name" ]; then
    rm -f "$tmp_defaults"
    return 1
  fi

  if php_console doctrine:migrations:status --env=prod 2>/dev/null | grep -Fq "$db_name"; then
    info "Base sauvegardée cohérente avec Doctrine : $db_name"
  else
    warn "La base extraite de DATABASE_URL ($db_name) ne correspond pas à la base vue par Doctrine."
    warn "Backup mysqldump interrompu pour éviter de sauvegarder la mauvaise base."
    rm -f "$tmp_defaults"
    return 1
  fi

  echo "+ $dump_bin --defaults-extra-file=<temp> --single-transaction --quick --routines --triggers \"$db_name\" > $backup_file"
  set +e
  "$dump_bin" --defaults-extra-file="$tmp_defaults" --single-transaction --quick --routines --triggers "$db_name" > "$backup_file"
  local dump_status=$?
  set -e

  rm -f "$tmp_defaults"

  if [ "$dump_status" -ne 0 ]; then
    rm -f "$backup_file"
    return "$dump_status"
  fi

  if [ ! -s "$backup_file" ]; then
    rm -f "$backup_file"
    return 1
  fi

  return 0
}

create_database_backup() {
  local backup_file="$1"

  if php_console list doctrine | grep -q "doctrine:database:export"; then
    echo "+ $PHP_BIN -d memory_limit=$PHP_MEMORY_LIMIT bin/console doctrine:database:export > $backup_file"
    php_console doctrine:database:export > "$backup_file"

    if [ ! -s "$backup_file" ]; then
      fail "Backup créé mais fichier vide : $backup_file"
    fi

    ok "Backup créé avec doctrine:database:export : $backup_file"
    return 0
  fi

  warn "doctrine:database:export indisponible. Tentative de fallback avec mysqldump/mariadb-dump."

  local dump_bin
  if dump_bin="$(resolve_mysqldump_bin)"; then
    info "Binaire dump détecté : $dump_bin"

    if backup_database_with_mysqldump "$backup_file" "$dump_bin"; then
      ok "Backup créé avec mysqldump/mariadb-dump : $backup_file"
      return 0
    fi

    warn "Fallback mysqldump/mariadb-dump échoué."
  else
    warn "Aucun binaire mysqldump ou mariadb-dump trouvé."
  fi

  return 1
}


compile_asset_map_if_available() {
  if php_console list | grep -q "asset-map:compile"; then
    if [ "$DRY_RUN" = "1" ]; then
      warn "DRY_RUN=1 : asset-map:compile non exécuté."
      return 0
    fi

    run_console asset-map:compile --env=prod

    if [ -d "public/assets" ]; then
      ok "Assets Symfony compilés dans public/assets."
    else
      fail "asset-map:compile exécuté mais public/assets est introuvable."
    fi

    if find public/assets -type f | grep -E "admin|product_images|product-images" >/dev/null 2>&1; then
      ok "Assets admin/images produits détectés dans public/assets."
    else
      warn "Aucun asset admin/product_images trouvé dans public/assets. Vérifie si admin.js est bien importé."
    fi
  else
    warn "Commande asset-map:compile indisponible. Compilation assets ignorée."
  fi
}


count_runtime_upload_files() {
  if [ ! -d "public/uploads/products" ]; then
    printf '0\n'
    return 0
  fi

  find public/uploads/products -type f ! -name '.gitkeep' 2>/dev/null | wc -l | awk '{print $1}'
}

count_asset_files() {
  if [ ! -d "public/assets" ]; then
    printf '0\n'
    return 0
  fi

  find public/assets -type f 2>/dev/null | wc -l | awk '{print $1}'
}

check_db_column_exists() {
  local table="$1"
  local column="$2"

  php_console dbal:run-sql --env=prod --force-fetch "SHOW COLUMNS FROM \`$table\` LIKE '$column';" 2>/dev/null | grep -Fq "$column"
}

check_optional_j5k_gps_columns() {
  local missing=0

  local checks=(
    "address:gps_latitude"
    "address:gps_longitude"
    "address:gps_accuracy_meters"
    "customer_order:delivery_address_gps_latitude"
    "customer_order:delivery_address_gps_longitude"
    "customer_order:delivery_address_gps_accuracy_meters"
  )

  local item
  for item in "${checks[@]}"; do
    local table="${item%%:*}"
    local column="${item##*:}"

    if check_db_column_exists "$table" "$column"; then
      ok "Colonne GPS détectée : $table.$column"
    else
      warn "Colonne GPS absente : $table.$column"
      missing=1
    fi
  done

  if [ "$missing" = "0" ]; then
    record_ok "J5K GPS colonnes DB" "address + customer_order OK"
  else
    record_warn "J5K GPS colonnes DB" "colonnes absentes, vérifier le tag ou la migration"
  fi
}


check_optional_j5k_bis_address_columns() {
  local missing=0

  local checks=(
    "address:courier_notes"
    "customer_order:delivery_address_courier_notes"
    "customer:delivery_address_id"
  )

  local item
  for item in "${checks[@]}"; do
    local table="${item%%:*}"
    local column="${item##*:}"

    if check_db_column_exists "$table" "$column"; then
      ok "Colonne J5K-bis détectée : $table.$column"
    else
      warn "Colonne J5K-bis absente : $table.$column"
      missing=1
    fi
  done

  if [ "$missing" = "0" ]; then
    record_ok "J5K-bis adresse enrichie" "commentaire livreur + snapshot commande + adresse livraison par défaut OK"
  else
    record_warn "J5K-bis adresse enrichie" "colonnes absentes, vérifier la migration"
  fi
}

check_hodina_setting_group_columns() {
  local missing=0

  local checks=(
    "hodina_setting:group_key"
    "hodina_setting:group_label"
    "hodina_setting:sort_order"
    "hodina_setting:is_editable"
    "hodina_setting:is_sensitive"
  )

  local item
  for item in "${checks[@]}"; do
    local table="${item%%:*}"
    local column="${item##*:}"

    if check_db_column_exists "$table" "$column"; then
      ok "Colonne réglages groupés détectée : $table.$column"
    else
      warn "Colonne réglages groupés absente : $table.$column"
      missing=1
    fi
  done

  if [ "$missing" = "0" ]; then
    record_ok "J5Q-C-1 réglages groupés" "colonnes hodina_setting OK"
  else
    record_warn "J5Q-C-1 réglages groupés" "colonnes absentes, vérifier migration Version20260624233000"
  fi
}

check_hodina_setting_key_exists() {
  local key="$1"
  php_console dbal:run-sql --env=prod --force-fetch "SELECT setting_key FROM hodina_setting WHERE setting_key = '$key' LIMIT 1;" 2>/dev/null | grep -Fq "$key"
}

check_courier_payout_settings() {
  local missing=0
  local keys=(
    courier_payouts_enabled
    courier_payout_cron_enabled
    courier_payout_admin_recap_enabled
    courier_payout_frequency
  )

  local key
  for key in "${keys[@]}"; do
    if check_hodina_setting_key_exists "$key"; then
      ok "Réglage paiement livreur détecté : $key"
    else
      warn "Réglage paiement livreur absent : $key"
      missing=1
    fi
  done

  if [ "$missing" = "0" ]; then
    record_ok "J5Q-C-1 réglages paiements" "4 réglages livreurs présents"
  else
    record_warn "J5Q-C-1 réglages paiements" "réglages absents, vérifier migration Version20260624234500"
  fi
}

check_hodina_courier_payout_command() {
  if php_console list hodina --env=prod 2>/dev/null | grep -Fq "hodina:courier-payouts:generate"; then
    record_ok "J5Q-C commande paiements livreurs" "hodina:courier-payouts:generate disponible"
  else
    record_warn "J5Q-C commande paiements livreurs" "commande introuvable dans php bin/console list hodina"
  fi
}

check_optional_public_url() {
  if [ -z "${PUBLIC_URL:-}" ]; then
    record_warn "URL publique" "non testée, définir PUBLIC_URL=https://recette.hodina.fr pour automatiser"
    return 0
  fi

  if ! command -v curl >/dev/null 2>&1; then
    record_warn "URL publique" "curl indisponible, test ignoré pour $PUBLIC_URL"
    return 0
  fi

  local http_code
  http_code="$(curl -k -L -s -o /dev/null -w '%{http_code}' --max-time 20 "$PUBLIC_URL" || true)"

  case "$http_code" in
    200|301|302)
      record_ok "URL publique" "$PUBLIC_URL répond HTTP $http_code"
      ;;
    401)
      record_ok "URL publique" "$PUBLIC_URL répond HTTP 401, Basic Auth probablement actif"
      ;;
    *)
      record_warn "URL publique" "$PUBLIC_URL répond HTTP ${http_code:-inconnu}"
      ;;
  esac
}

final_deployment_checks() {
  local final_head
  final_head="$(git rev-parse --short HEAD || true)"

  if [ "$DRY_RUN" = "1" ]; then
    record_warn "Checkout tag" "DRY_RUN actif, aucun checkout réel"
  elif [ "$final_head" = "$(git rev-parse --short "$TAG_COMMIT")" ]; then
    record_ok "Checkout tag" "$TAG @ $final_head"
  else
    fail "HEAD final ($final_head) différent du tag demandé ($(git rev-parse --short "$TAG_COMMIT"))."
  fi

  local final_blocking_status
  final_blocking_status="$(blocking_git_status_lines || true)"
  if [ -z "$final_blocking_status" ]; then
    if [ -n "$(git status --porcelain)" ]; then
      record_ok "Git working tree" "uniquement env/uploads/assets autorisés"
    else
      record_ok "Git working tree" "propre"
    fi
  else
    echo "$final_blocking_status"
    fail "Working tree final non propre hors fichiers autorisés."
  fi

  if [ -f ".env.local" ] || [ -f ".env.prod.local" ] || [ -f "prod.env.local" ]; then
    record_ok "Env local" "présent après checkout"
  else
    fail "Aucun fichier env local présent après déploiement."
  fi

  local upload_count
  upload_count="$(count_runtime_upload_files)"
  if [ -d "public/uploads/products" ]; then
    if [ "$upload_count" -gt 0 ]; then
      record_ok "Uploads produits" "dossier présent, $upload_count fichier(s) runtime"
    else
      record_warn "Uploads produits" "dossier présent mais aucune image runtime détectée"
    fi
  else
    fail "Dossier public/uploads/products absent après déploiement."
  fi

  local asset_count
  asset_count="$(count_asset_files)"
  if [ -d "public/assets" ] && [ "$asset_count" -gt 0 ]; then
    record_ok "Assets compilés" "public/assets contient $asset_count fichier(s)"
  else
    record_warn "Assets compilés" "public/assets absent ou vide"
  fi

  if [ "$SKIP_BACKUP" = "1" ]; then
    record_warn "Backup DB" "SKIP_BACKUP=1"
  elif [ -n "${BACKUP_FILE:-}" ] && [ -s "${BACKUP_FILE:-}" ]; then
    record_ok "Backup DB" "$BACKUP_FILE"
  else
    record_warn "Backup DB" "backup automatique non confirmé, vérifier backup manuel si demandé"
  fi

  if [ -d "var/cache/prod" ]; then
    record_ok "Cache prod" "var/cache/prod présent après warmup"
  else
    fail "Cache prod absent après warmup."
  fi

  if php_console doctrine:schema:validate --env=prod >/tmp/hodina_schema_validate_${TIMESTAMP}.log 2>&1; then
    record_ok "Doctrine schema" "mapping et base synchronisés"
  else
    cat /tmp/hodina_schema_validate_${TIMESTAMP}.log || true
    fail "doctrine:schema:validate échoué en vérification finale."
  fi
  rm -f /tmp/hodina_schema_validate_${TIMESTAMP}.log || true

  if php_console doctrine:migrations:status --env=prod | grep -Fq "Already at latest version"; then
    record_ok "Doctrine migrations" "déjà à la dernière version"
  else
    record_warn "Doctrine migrations" "statut affiché ci-dessus, vérifier la ligne New"
  fi

  check_optional_j5k_gps_columns
  check_optional_j5k_bis_address_columns
  check_hodina_setting_group_columns
  check_courier_payout_settings
  check_hodina_courier_payout_command

  if crontab -l 2>/dev/null | grep -Fq "$CRON_LOCK"; then
    record_ok "Cron Messenger" "$CRON_LOCK présent"
  else
    record_warn "Cron Messenger" "$CRON_LOCK non trouvé dans crontab"
  fi

  if [ -f "var/log/messenger_cron.log" ]; then
    record_ok "Log Messenger" "var/log/messenger_cron.log présent"
  else
    record_warn "Log Messenger" "pas encore présent, attendre 1 minute"
  fi

  check_optional_public_url
}


while [[ $# -gt 0 ]]; do
  case "$1" in
    --project-dir)
      PROJECT_DIR="${2:-}"
      shift 2
      ;;
    --tag)
      TAG="${2:-}"
      shift 2
      ;;
    --target)
      TARGET="${2:-}"
      shift 2
      ;;
    --main-branch)
      MAIN_BRANCH="${2:-}"
      shift 2
      ;;
    --php-bin)
      PHP_BIN="${2:-}"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      usage
      fail "Option inconnue : $1"
      ;;
  esac
done

require_arg "$PROJECT_DIR" "--project-dir"
require_arg "$TAG" "--tag"
require_arg "$TARGET" "--target"

if [ "$TARGET" != "recette" ] && [ "$TARGET" != "prod" ]; then
  fail "--target doit valoir recette ou prod."
fi

if [ "$TARGET" = "prod" ] && [ "$SKIP_BACKUP" = "1" ]; then
  confirm_or_abort "SKIP_BACKUP=1 est actif en production. C'est risqué."
fi

BACKUP_DIR="${BACKUP_DIR:-$PROJECT_DIR/var/backups}"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"

log "1. Précontrôles système et résolution des binaires"
resolve_deploy_binaries

ok "Commandes système disponibles."
info "Cible : $TARGET"
info "Tag demandé : $TAG"
info "Branche source attendue : origin/$MAIN_BRANCH"

log "2. Contrôle du dossier projet"
if [ ! -d "$PROJECT_DIR" ]; then
  fail "Dossier projet introuvable : $PROJECT_DIR"
fi

cd "$PROJECT_DIR"

if [ ! -f "bin/console" ]; then
  fail "bin/console introuvable dans $PROJECT_DIR. Mauvais dossier projet ?"
fi

if [ ! -d ".git" ]; then
  fail "$PROJECT_DIR n'est pas un dépôt Git."
fi

pwd
ok "Dossier projet valide."

log "3. Contrôle Git local"
run git status --short
CURRENT_BRANCH="$(git branch --show-current || true)"
PREVIOUS_REF="$(git rev-parse --short HEAD || true)"

info "Branche locale courante : ${CURRENT_BRANCH:-detached HEAD}"
info "HEAD actuel : ${PREVIOUS_REF:-inconnu}"

BLOCKING_STATUS="$(blocking_git_status_lines || true)"
if [ -n "$BLOCKING_STATUS" ]; then
  run git status
  echo ""
  echo "$BLOCKING_STATUS"
  fail "Working tree non propre hors fichiers env locaux/uploads runtime/assets générés autorisés. Commit, stash ou nettoie avant déploiement."
fi

if [ -n "$(git status --porcelain)" ]; then
  warn "Le working tree contient uniquement des fichiers env locaux, uploads runtime ou assets générés autorisés. Ils seront sauvegardés/recompilés si nécessaire."
fi

if [ -d ".git/rebase-merge" ] || [ -d ".git/rebase-apply" ]; then
  fail "Un rebase Git semble en cours. Termine ou annule le rebase avant déploiement."
fi

if [ -f ".git/MERGE_HEAD" ]; then
  fail "Un merge Git semble en cours. Termine ou annule le merge avant déploiement."
fi

ORIGIN_URL="$(git remote get-url origin 2>/dev/null || true)"
if [ -z "$ORIGIN_URL" ]; then
  fail "Remote Git origin introuvable."
fi

info "origin = $ORIGIN_URL"

if [ "$ENFORCE_SSH" = "1" ]; then
  case "$ORIGIN_URL" in
    git@github.com:*|ssh://git@github.com/*)
      ok "Remote origin en SSH."
      ;;
    *)
      fail "Remote origin n'est pas en SSH. Configure : git remote set-url origin git@github.com:chahere/hodina.git"
      ;;
  esac
else
  warn "ENFORCE_SSH=0 : remote SSH non imposé."
fi

log "4. Contrôle accès GitHub, main et tag"
run git ls-remote --exit-code origin "refs/heads/$MAIN_BRANCH" >/dev/null
ok "origin/$MAIN_BRANCH accessible."

run git fetch --prune origin "$MAIN_BRANCH"
run git fetch origin --tags --force

if ! git rev-parse "refs/remotes/origin/$MAIN_BRANCH" >/dev/null 2>&1; then
  fail "origin/$MAIN_BRANCH introuvable après fetch."
fi

if ! git rev-parse "refs/tags/$TAG" >/dev/null 2>&1; then
  fail "Tag introuvable après fetch : $TAG. Vérifie : git push origin $TAG"
fi

TAG_COMMIT="$(git rev-list -n 1 "$TAG")"
MAIN_COMMIT="$(git rev-parse "origin/$MAIN_BRANCH")"

info "Commit du tag : $TAG_COMMIT"
info "Commit origin/$MAIN_BRANCH : $MAIN_COMMIT"

if ! git merge-base --is-ancestor "$TAG_COMMIT" "origin/$MAIN_BRANCH"; then
  fail "Le tag $TAG n'est pas contenu dans origin/$MAIN_BRANCH. Règle Hodina : déploiement uniquement par tag issu de main."
fi

ok "Le tag $TAG est bien contenu dans origin/$MAIN_BRANCH."

backup_local_env_files
backup_runtime_uploads

log "6. Backup base de données"
mkdir -p "$BACKUP_DIR"

if [ "$SKIP_BACKUP" = "1" ]; then
  warn "Backup ignoré car SKIP_BACKUP=1."
else
  BACKUP_FILE="$BACKUP_DIR/backup_avant_${TARGET}_${TAG}_${TIMESTAMP}.sql"

  if ! create_database_backup "$BACKUP_FILE"; then
    if [ "$ASSUME_YES" = "1" ]; then
      fail "Mode non interactif : backup automatique impossible. Installe mysqldump/mariadb-dump, configure MYSQLDUMP_BIN, ou utilise SKIP_BACKUP=1 seulement si tu assumes."
    fi
    confirm_or_abort "Backup automatique indisponible. Fais un backup manuel DB depuis o2switch/phpMyAdmin avant de continuer."
  fi
fi

log "7. Checkout du tag"
park_runtime_uploads_before_checkout

if [ "$DRY_RUN" = "1" ]; then
  warn "DRY_RUN=1 : checkout non effectué. Tag qui serait déployé : $TAG"
else
  run git checkout -f "tags/$TAG"
fi

DEPLOYED_REF="$(git rev-parse --short HEAD || true)"
EXPECTED_SHORT="$(git rev-parse --short "$TAG_COMMIT")"

if [ "$DRY_RUN" != "1" ] && [ "$DEPLOYED_REF" != "$EXPECTED_SHORT" ]; then
  fail "HEAD déployé ($DEPLOYED_REF) ne correspond pas au tag demandé ($EXPECTED_SHORT)."
fi

run git log --oneline -1
run git status --short
ok "Code positionné sur le tag $TAG."

restore_local_env_files
restore_runtime_uploads

log "8ter. Installation Composer préalable / optionnelle"

if [ "$RUN_COMPOSER" = "1" ] || [ ! -f "vendor/autoload.php" ]; then
  if [ ! -f "vendor/autoload.php" ]; then
    warn "vendor/autoload.php absent après checkout. Installation Composer obligatoire avant bin/console."
  else
    info "RUN_COMPOSER=1 : installation/optimisation Composer exécutée avant le premier bin/console."
  fi

  run_composer_install "Installation Composer avant contrôle Symfony"
else
  ok "vendor/autoload.php présent avant contrôle Symfony. Composer non lancé."
fi

log "9. Contrôle Symfony après checkout"
run_console --version
php_console about --env=prod || warn "bin/console about a renvoyé un avertissement. On continue si les étapes critiques passent."

log "10. Installation Composer optionnelle"
if [ "$RUN_COMPOSER" = "1" ]; then
  if [ "$COMPOSER_INSTALL_RAN" = "1" ]; then
    ok "Composer déjà lancé avant le contrôle Symfony. Deuxième installation évitée."
  else
    run_composer_install "Installation Composer demandée par RUN_COMPOSER=1"
  fi
else
  info "Composer non lancé. Active avec RUN_COMPOSER=1 si nécessaire."
fi

log "11. Migrations Doctrine"
run_console doctrine:migrations:status --env=prod
run_console doctrine:migrations:migrate --no-interaction --env=prod

log "12. Assets, cache prod optimisé et validations Doctrine"
info "Compilation des assets Symfony avant cache prod, nécessaire pour admin.js et les contrôleurs Stimulus en prod."
compile_asset_map_if_available

info "Nettoyage cache prod sécurisé : cache:clear --no-warmup, puis cache:warmup séparé."
info "On évite de supprimer brutalement var/cache/prod pendant que le site tourne."
run_console cache:clear --env=prod --no-warmup
run_console cache:warmup --env=prod

if [ ! -d "var/cache/prod" ]; then
  fail "Le dossier var/cache/prod est introuvable après cache:warmup. Déploiement interrompu."
fi

ok "Cache prod nettoyé et réchauffé."
run_console doctrine:schema:validate --env=prod
run_console doctrine:migrations:status --env=prod

log "13. Nettoyage optionnel anciennes commandes"
if [ "$RESET_COMMANDS" = "1" ]; then
  if [ "$DRY_RUN" = "1" ]; then
    warn "DRY_RUN=1 : nettoyage commandes non exécuté."
  else
    confirm_or_abort "Suppression des commandes et logs liés sur cible '$TARGET'. Tables nettoyées : sms_log liés commandes, email_log liés commandes, order_item, customer_order."

    run_sql "SELECT COUNT(*) AS nb_commandes FROM customer_order;"
    run_sql "SELECT COUNT(*) AS nb_lignes_commande FROM order_item;"
    run_sql "SELECT COUNT(*) AS nb_sms_lies_commandes FROM sms_log WHERE customer_order_id IS NOT NULL;"
    run_sql "SELECT COUNT(*) AS nb_emails_lies_commandes FROM email_log WHERE customer_order_id IS NOT NULL;"

    run_sql "DELETE FROM sms_log WHERE customer_order_id IS NOT NULL;"
    run_sql "DELETE FROM email_log WHERE customer_order_id IS NOT NULL;"
    run_sql "DELETE FROM order_item;"
    run_sql "DELETE FROM customer_order;"

    run_sql "SELECT COUNT(*) AS nb_commandes_restantes FROM customer_order;"
    run_sql "SELECT COUNT(*) AS nb_lignes_restantes FROM order_item;"
    run_sql "SELECT COUNT(*) AS nb_sms_lies_commandes_restants FROM sms_log WHERE customer_order_id IS NOT NULL;"
    run_sql "SELECT COUNT(*) AS nb_emails_lies_commandes_restants FROM email_log WHERE customer_order_id IS NOT NULL;"
  fi
else
  info "Nettoyage non lancé. Active avec RESET_COMMANDS=1 si nécessaire."
fi

log "14. Cron Messenger"
if [ "$TARGET" = "recette" ]; then
  CRON_LOCK="/tmp/hodina_recette_messenger.lock"
else
  CRON_LOCK="/tmp/hodina_prod_messenger.lock"
fi

MESSENGER_LINE="* * * * * cd $PROJECT_DIR && mkdir -p var/log && flock -n $CRON_LOCK $PHP_BIN bin/console messenger:consume async --env=prod --limit=10 --time-limit=50 --memory-limit=128M --no-interaction >> $PROJECT_DIR/var/log/messenger_cron.log 2>&1"

TMP_CRON="$(mktemp)"
crontab -l 2>/dev/null > "$TMP_CRON" || true

if ! grep -Fxq 'MAILTO=""' "$TMP_CRON"; then
  echo 'MAILTO=""' >> "$TMP_CRON"
fi

if grep -Fq "$CRON_LOCK" "$TMP_CRON"; then
  ok "Cron Messenger déjà présent pour $TARGET : $CRON_LOCK"
else
  if [ "$DRY_RUN" = "1" ]; then
    warn "DRY_RUN=1 : cron non ajouté. Ligne prévue :"
    echo "$MESSENGER_LINE"
  else
    echo "$MESSENGER_LINE" >> "$TMP_CRON"
    crontab "$TMP_CRON"
    ok "Cron Messenger ajouté pour $TARGET."
  fi
fi

rm -f "$TMP_CRON"

echo ""
echo "Crontab actuelle :"
crontab -l || true

log "15. Vérification finale automatisée"
run git status --short
run git log --oneline -3
run_console doctrine:migrations:status --env=prod

final_deployment_checks

if [ -f "var/log/messenger_cron.log" ]; then
  info "Dernières lignes messenger_cron.log :"
  tail -n 10 var/log/messenger_cron.log || true
else
  warn "var/log/messenger_cron.log pas encore présent. Il peut apparaître après 1 minute."
fi

log "16. Déploiement terminé"
ok "Déploiement terminé avec succès."
echo ""
echo "Résumé :"
echo "  Cible         : $TARGET"
echo "  Projet        : $PROJECT_DIR"
echo "  Tag           : $TAG"
echo "  Commit tag    : $TAG_COMMIT"
echo "  Main source   : origin/$MAIN_BRANCH @ $MAIN_COMMIT"
echo "  Avant MEP     : ${PREVIOUS_REF:-inconnu}"
echo "  Déployé       : ${DEPLOYED_REF:-inconnu}"
echo "  Backup env    : ${ENV_BACKUP_DIR:-aucun}"
echo "  Backup uploads: ${RUNTIME_BACKUP_DIR:-aucun}"
echo "  Backup DB     : ${BACKUP_FILE:-aucun ou manuel}"
echo "  Dump DB bin   : ${MYSQLDUMP_BIN:-non résolu}"
echo "  URL publique  : ${PUBLIC_URL:-non renseignée}"
print_check_summary
echo ""
echo "Tests navigateur restants :"
echo "  - créer une commande de recette sans GPS ;"
echo "  - créer une commande de recette avec GPS depuis mobile HTTPS ;"
echo "  - ouvrir le lien Google Maps côté admin/livreur ;"
echo "  - confirmer visuellement que les images produits et l'admin sont OK."
echo "  - vérifier EasyAdmin > Réglages > Paiements et les vues groupées HodinaSetting."
