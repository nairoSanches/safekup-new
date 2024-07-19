#!/bin/bash

# Definir o caminho completo para o interpretador PHP
PHP_BIN=/opt/lampp/bin/php

# Caminho para o script PHP que queremos executar
PHP_SCRIPT=/opt/lampp/htdocs/safekup/php/backup/backups_cron.php

# Caminho para o arquivo de log
LOG_FILE=/opt/lampp/htdocs/safekup/php/backup/cron.log

# Registrar o início do script no log
echo "Backup iniciado em $(date '+%Y-%m-%d %H:%M:%S')" >> $LOG_FILE

# Executar o script PHP e registrar a saída e os erros no log
$PHP_BIN $PHP_SCRIPT >> $LOG_FILE 2>&1

# Registrar o término do script no log
echo "Backup finalizado em $(date '+%Y-%m-%d %H:%M:%S')" >> $LOG_FILE

