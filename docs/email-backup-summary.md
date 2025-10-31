# Envio de resumo diário dos backups

Este projeto agora conta com um script CLI responsável por enviar um relatório consolidado dos backups realizados em `historico_dumps`. O envio considera os destinatários ativos cadastrados na tela **E-mails** (tabela `smtp_destinatarios`) e utiliza a configuração do servidor SMTP vinculada a cada contato.

## Script

Arquivo: `scripts/backups/send_backup_summary.php`

### Dependências

- Entradas da tabela `smtp` com senha armazenada em Base64 (já utilizado nas telas atuais).
- Destinatários ativos na `smtp_destinatarios`.
- PHP com extensões `pdo_mysql` e `openssl`.
- Composer autoload (`vendor/autoload.php`) disponível.

### Executando manualmente

```bash
/opt/lampp/bin/php /opt/lampp/htdocs/safekup/scripts/backups/send_backup_summary.php
```

Por padrão, o script identifica a data mais recente de `historico_dumps`. Também é possível informar explicitamente uma data (formato `YYYY-MM-DD`):

```bash
/opt/lampp/bin/php /opt/lampp/htdocs/safekup/scripts/backups/send_backup_summary.php --date=2024-10-31
```

### Saída

O script registra mensagens no `stdout`/`stderr` indicando:

- Data utilizada para montar o relatório.
- Quantidade de destinatários por servidor SMTP.
- Falhas de envio (quando houver).

### Automação

Para executar automaticamente ao final da rotina de backups, adicione uma entrada no `cron` logo após o processo terminar, por exemplo:

```cron
30 7 * * * /opt/lampp/bin/php /opt/lampp/htdocs/safekup/scripts/backups/send_backup_summary.php >> /var/log/safekup/email-relatorio.log 2>&1
```

Ajuste o horário conforme a conclusão dos backups.

## Conteúdo do e-mail

- Resumo quantitativo (total, sucesso, falhas).
- Tabela com: banco, IP, status, horário da execução, tempo, tamanho e observações.
- Corpo em HTML com fallback em texto simples.

Caso não existam registros ou destinatários ativos, nada é enviado e uma mensagem é escrita no log.
