#!/bin/bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
PHP_BIN="${PHP_BIN:-/opt/lampp/bin/php}"
LEGACY_EXECUTOR="$PROJECT_ROOT/php/backup/executa_backup.php"

if [ "$#" -ne 1 ]; then
  echo "Uso: manual_dump.sh <bd_id>" >&2
  exit 1
fi

BD_ID="$1"

if ! [[ "$BD_ID" =~ ^[0-9]+$ ]]; then
  echo "Identificador do banco inválido: $BD_ID" >&2
  exit 1
fi

if [ ! -x "$PHP_BIN" ] && [ ! -x "$PHP_BIN/php" ]; then
  echo "Binário PHP não encontrado: $PHP_BIN" >&2
  exit 1
fi

if [ ! -f "$LEGACY_EXECUTOR" ]; then
  echo "Executor legado não encontrado em $LEGACY_EXECUTOR" >&2
  exit 1
fi

PHP_CMD="$PHP_BIN"
if [ -d "$PHP_BIN" ] && [ -x "$PHP_BIN/php" ]; then
  PHP_CMD="$PHP_BIN/php"
fi

OUTPUT="$("$PHP_CMD" "$LEGACY_EXECUTOR" "$BD_ID" 2>&1 || true)"
EXIT_CODE=$?

if [ $EXIT_CODE -ne 0 ]; then
  echo "$OUTPUT" >&2
  exit $EXIT_CODE
fi

echo "$OUTPUT"
exit 0
