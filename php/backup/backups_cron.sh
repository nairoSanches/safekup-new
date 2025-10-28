#!/bin/bash

# Caminho para o binário PHP do LAMPP
PHP_BIN="/opt/lampp/bin/php"

# Caminho para o script de backup do Safekup
PHP_SCRIPT="/opt/lampp/htdocs/safekup/php/backup/backups_cron.php"

# Caminho para o arquivo de log
LOG_FILE="/opt/lampp/htdocs/safekup/php/backup/cron.log"

# Verificar se o binário PHP existe
if [ ! -x "$PHP_BIN" ]; then
  echo "[ERROR] $(date '+%Y-%m-%d %H:%M:%S') - PHP não encontrado em $PHP_BIN" >> "$LOG_FILE"
  exit 1
fi

# Verificar se o script existe
if [ ! -f "$PHP_SCRIPT" ]; then
  echo "[ERROR] $(date '+%Y-%m-%d %H:%M:%S') - Script não encontrado em $PHP_SCRIPT" >> "$LOG_FILE"
  exit 1
fi

# Registrar início
echo "[INFO] $(date '+%Y-%m-%d %H:%M:%S') - Backup iniciado" | tee -a "$LOG_FILE"

# Executar o script e registrar saída e erro
"$PHP_BIN" "$PHP_SCRIPT" 2>&1 | tee -a "$LOG_FILE"

# Registrar fim
echo "[INFO] $(date '+%Y-%m-%d %H:%M:%S') - Backup finalizado" | tee -a "$LOG_FILE"
