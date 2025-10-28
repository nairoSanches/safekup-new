#!/bin/bash

# === CONFIGURAÇÃO ===

BASE_DIR="/DBbackup"
LOG_FILE="/opt/lampp/htdocs/safekup/cron/log/backup_cleanup.log"

# Regras de retenção (pode alterar os parametros)
RETENTION_RULES=(
  "0 6 daily 4 Diária (0–6 dias, 4 cópias/dia)"
  "7 60 daily 1 Diária (7–60 dias, 1 cópia/dia)"
  "61 1095 monthly 1 Mensal (61–1095 dias, 1 cópia/mês)"
)


log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$LOG_FILE"
}

retain_backups() {
    local path="$1"
    local min_epoch="$2"
    local max_epoch="$3"
    local period_type="$4"
    local keep_limit="$5"
    local rule_name="$6"

    log_message "Regra ${rule_name}: Início. Período de $(date -d @$min_epoch +%F) até $(date -d @$max_epoch +%F)."

    mapfile -t files < <(find "$path" -type f \( -name "*.zip" -o -name "*.zst" \) -printf "%T@ %p\n" | sort -n)

    declare -A grouped_files
    local deleted=0

    for entry in "${files[@]}"; do
        file_epoch=${entry%% *}
        file_path=${entry#* }

        file_epoch_int=${file_epoch%.*}
        [[ "$file_epoch_int" -lt "$min_epoch" || "$file_epoch_int" -gt "$max_epoch" ]] && continue

        case "$period_type" in
            daily) period_key=$(date -d "@$file_epoch_int" +%Y-%m-%d) ;;
            monthly) period_key=$(date -d "@$file_epoch_int" +%Y-%m) ;;
        esac

        grouped_files["$period_key"]+="$file_epoch_int|$file_path"$'\n'
    done

    for period in "${!grouped_files[@]}"; do
        IFS=$'\n' read -r -d '' -a entries < <(echo "${grouped_files[$period]}" | sort -n | tr '\n' '\0')
        for ((i=${#entries[@]}-1; i>=keep_limit; i--)); do
            file_path=$(echo "${entries[$i]}" | cut -d'|' -f2)
            rm -f "$file_path"
            deleted=$((deleted + 1))
            log_message "Regra ${rule_name}: Removido $file_path"
        done
    done

    log_message "Regra ${rule_name}: Fim. $deleted arquivos excluídos."
}


log_message "====== Início da rotina de retenção de backups ======"

now=$(date +%s)

for rule in "${RETENTION_RULES[@]}"; do
    read min_days max_days period_type keep_limit rule_name <<< "$rule"
    min_epoch=$((now - max_days * 86400))
    max_epoch=$((now - min_days * 86400))
    retain_backups "$BASE_DIR" "$min_epoch" "$max_epoch" "$period_type" "$keep_limit" "$rule_name"
done

log_message "====== Fim da rotina de retenção de backups ======"
