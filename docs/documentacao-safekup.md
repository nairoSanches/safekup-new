# Documentacao do Safekup

Este documento consolida informacoes essenciais sobre o funcionamento do Safekup, suas dependencias, fluxos principais e boas praticas de operacao.

## Visao Geral do Sistema
- Plataforma web focada em orquestrar e acompanhar rotinas de backup de bancos de dados heterogeneos.
- Permite cadastrar servidores, bancos, credenciais e rotinas, executando dumps remotos via SSH ou acessos diretos.
- Centraliza a armazenagem dos artefatos de backup e gera relatorios de acompanhamento.

## Arquitetura de Alto Nivel
- **Interface Web (`app/`, `php/`)**: implements CRUDs, autenticacao e configuracoes via PHP/HTML.
- **Scripts de automacao (`scripts/backups/`, `cron/`)**: orquestram execucoes, consolidam resultados e disparam relatorios.
- **Banco de dados MySQL**: armazena configuracoes, historico das execucoes e dados operacionais.
- **Infraestrutura externa**: servidores de banco, storage para backups e agente de e-mail via SMTP.
- **Dependencias chave**: PHP 8+, Composer (vendor/), PHPMailer para envio de e-mails e utilitarios do sistema operacional (rsync, sshpass, unzip, etc.).

## Fluxo de Backup
1. Rotinas ativas sao agendadas via cron chamando o script legado `php/backup/backups_cron.php`.
2. Esse script consolida parametros de conexao cadastrados na aplicacao e executa os dumps conforme o tipo de banco.
3. Os artefatos sao gravados no storage definido (por exemplo, `/home/safekup` ou montagens em `/mnt/cliente` e `/mnt/servidor`).
4. O script `scripts/backups/run_backups.php` carrega a rotina legada e, apos a execucao, dispara o resumo diario via `send_backup_summary.php`.
5. O resumo e enviado por e-mail utilizando PHPMailer, destacando total de execucoes, sucessos, falhas e metricas de cada banco.

## Agendamentos e Automacao
- Utilize o arquivo `cron/cleanup_backups.sh` para politicas de limpeza de artefatos antigos.
- Configure o crontab conforme exemplo descrito no `README.md` (entrada recomendada: `0 * * * * sh /var/www/html/safekup/php/backup/executa_backup.sh`).
- Garanta que os scripts tenham permissao de execucao e que o usuario `www-data` (ou equivalente) possua privilegios adequados em `/etc/sudoers`.

## Estrutura de Diretorios Relevantes
- `app/` e `php/`: aplicacao web (controllers, views, conexoes e scripts auxiliares).
- `scripts/backups/`: scripts modernos para integracao com cron e envio de resumos.
- `cron/`: scripts shell utilizados em agendamentos complementares.
- `data/`: modelos e dados auxiliares utilizados pela aplicacao.
- `docker/` e `docker-compose.yml`: infraestrutura opcional para containerizacao do ambiente.
- `docs/`: documentacao tecnica e operacoes (incluindo este arquivo).
- `log/`: utilitarios e possiveis artefatos de log/compactacao (ex.: `log/compacta.sh`).

## Configuracao e Implantacao
- Siga os passos detalhados no `README.md` para preparar o ambiente (atualizacao do sistema, instalacao de dependencias, configuracao do Apache/PHP e importacao do banco).
- Ajuste os arquivos de conexao em `php/conexao/` e `php/database/` substituindo senhas padrao.
- Certifique-se de que os diretorios de armazenamento (`/home/safekup`, `/mnt/cliente`, `/mnt/servidor`) existam e possuam permissoes corretas.
- Configure variaveis de ambiente para credenciais SMTP utilizadas pelo `send_backup_summary.php` (via `.env` ou arquivo de configuracao PHP correspondente).

## Monitoramento e Logs
- Registre saidas dos scripts cron via redirecionamento (`>>`) para arquivos em `/var/log` ou similar.
- Utilize as funcoes de log de `send_backup_summary.php` para registrar mensagens de status quando executado via CLI.
- Acompanhe o historico de execucoes e falhas pela interface web (relatorios) ou consultando diretamente as tabelas do banco.

## Recuperacao e Testes
- Realize testes periodicos restaurando dumps em ambientes de homologacao para validar integridade.
- Verifique se os resumos diarios estao sendo enviados e recebidos pelos grupos responsaveis.
- Automatize verificacoes de espaco em disco nos storages utilizados e gere alertas quando limites forem atingidos.

## Praticas de Seguranca
- Restrinja acesso SSH apenas a hosts autorizados e utilize chaves com senha quando possivel.
- Armazene credenciais em local seguro; evite comitar senhas em arquivos versionados.
- Mantenha o sistema operacional e dependencias atualizados para reduzir exposicao a vulnerabilidades.
- Garanta rotinas de limpeza para remover backups fora da politica de retencao definida.

## Extensao e Customizacao
- Para adicionar novo tipo de banco, estenda os scripts em `php/backup/` e ajuste a interface para coletar os parametros necessarios.
- Integre sistemas de monitoramento externos (ex.: Zabbix, Prometheus) lendo as metricas geradas pelos scripts.
- Documente alteracoes relevantes neste repositorio e atualize este arquivo para manter a equipe alinhada.

## Contato e Suporte
- Canal primario: suporte@safekup.com.
- Em manutencoes, comunique o time e os stakeholders com antecedencia, destacando janelas de indisponibilidade e risco.
