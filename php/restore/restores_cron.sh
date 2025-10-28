#!/bin/bash

# URL do script PHP
PHP_SCRIPT_URL="http://10.94.61.126/safekup/php/restore/restore_cron.php"

# Caminho para o arquivo de log
LOG_FILE="/opt/lampp/htdocs/safekup/php/restore/cron.log"

# Registrar o início do script no log
echo "Restore iniciado em $(date '+%Y-%m-%d %H:%M:%S')" >> $LOG_FILE

# Executar o script PHP via curl e registrar a saída e os erros no log
if curl -s $PHP_SCRIPT_URL >> $LOG_FILE 2>&1; then
    echo "Restore executado com sucesso em $(date '+%Y-%m-%d %H:%M:%S')" >> $LOG_FILE
else
    echo "Erro durante a execução do restore em $(date '+%Y-%m-%d %H:%M:%S')" >> $LOG_FILE
fi

# Registrar o término do script no log
echo "Restore finalizado em $(date '+%Y-%m-%d %H:%M:%S')" >> $LOG_FILE
