#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR=""
TARGET="recette"
PHP_BIN="${PHP_BIN:-/usr/local/bin/php}"
HOUR="${HODINA_COURIER_PAYOUT_CRON_HOUR:-5}"
MINUTE="${HODINA_COURIER_PAYOUT_CRON_MINUTE:-10}"
TIMEZONE="${HODINA_COURIER_PAYOUT_TIMEZONE:-Indian/Mayotte}"

usage() {
  cat <<'USAGE'
Usage:
  bash tools/install-courier-payout-cron.sh --project-dir /home/vopu3712/recette.hodina.fr --target recette
  bash tools/install-courier-payout-cron.sh --project-dir /home/vopu3712/hodina.fr --target prod

Variables optionnelles :
  PHP_BIN=/usr/local/bin/php
  HODINA_COURIER_PAYOUT_CRON_HOUR=5
  HODINA_COURIER_PAYOUT_CRON_MINUTE=10
  HODINA_COURIER_PAYOUT_TIMEZONE=Indian/Mayotte
USAGE
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --project-dir)
      PROJECT_DIR="${2:-}"
      shift 2
      ;;
    --target)
      TARGET="${2:-}"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Option inconnue : $1" >&2
      usage
      exit 1
      ;;
  esac
done

if [[ -z "$PROJECT_DIR" ]]; then
  echo "ERREUR: --project-dir est obligatoire." >&2
  exit 1
fi

if [[ "$TARGET" != "recette" && "$TARGET" != "prod" ]]; then
  echo "ERREUR: --target doit valoir recette ou prod." >&2
  exit 1
fi

if [[ ! -d "$PROJECT_DIR" ]]; then
  echo "ERREUR: dossier projet introuvable : $PROJECT_DIR" >&2
  exit 1
fi

if [[ ! -f "$PROJECT_DIR/bin/console" ]]; then
  echo "ERREUR: bin/console introuvable dans : $PROJECT_DIR" >&2
  exit 1
fi

if [[ ! -x "$PHP_BIN" ]]; then
  echo "ERREUR: PHP_BIN introuvable ou non exécutable : $PHP_BIN" >&2
  exit 1
fi

mkdir -p "$PROJECT_DIR/var/log"

LOCK_FILE="/tmp/hodina_${TARGET}_courier_payout.lock"
LOG_FILE="$PROJECT_DIR/var/log/courier_payout_cron.log"
CRON_CMD="cd $PROJECT_DIR && mkdir -p var/log && flock -n $LOCK_FILE $PHP_BIN bin/console hodina:courier-payouts:generate --auto-due --timezone=$TIMEZONE --notify-admins --env=prod --no-interaction >> $LOG_FILE 2>&1"
CRON_LINE="$MINUTE $HOUR * * * $CRON_CMD"
MARKER="hodina:courier-payouts:generate --auto-due --timezone=$TIMEZONE --notify-admins --env=prod"

TMP_CRON="$(mktemp)"
trap 'rm -f "$TMP_CRON"' EXIT

crontab -l 2>/dev/null | grep -v "hodina:courier-payouts:generate --auto-due" > "$TMP_CRON" || true
printf '%s\n' "$CRON_LINE" >> "$TMP_CRON"
crontab "$TMP_CRON"

echo "Cron paiements livreurs installé / remplacé pour $TARGET."
echo "Ligne : $CRON_LINE"
echo "Lock  : $LOCK_FILE"
echo "Log   : $LOG_FILE"
echo "Marker: $MARKER"
