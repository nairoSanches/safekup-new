Claro! Aqui está o arquivo `README.md` atualizado conforme solicitado:

```markdown
# Safekup

Safekup é um sistema que realiza backups (dumps) de vários bancos de dados e os armazena em um repositório centralizado. O sistema é capaz de realizar backups de bancos de dados em containers, máquinas virtuais e qualquer máquina no mundo, desde que possua permissões de acesso e credenciais válidas.

## Funcionalidades

- **Servidores de Backup**: Cadastro e gerenciamento de servidores onde os backups serão armazenados.
- **Tipo de Banco de Dados**: Suporte para diferentes tipos de bancos de dados.
- **Aplicações**: Cadastro de aplicações para relacionar com os backups.
- **SSH**: Configuração e gerenciamento de conexões SSH para acesso remoto.
- **Banco de Dados**: Cadastro e gerenciamento de bancos de dados que serão backupados.
- **Backups**: Configuração e gerenciamento dos backups realizados.
- **Usuários**: Cadastro e gerenciamento de usuários do sistema.
- **Relatórios**: Geração de relatórios de backup.
- **Crontab**: Execução automática dos backups utilizando o cron.

## Requisitos

- Servidor Linux
- LAMP (Linux, Apache, MySQL, PHP)
- phpMyAdmin (opcional)

## Instalação

### Passo 1: Atualize o sistema

```sh
echo "Atualizando o Servidor ..."
apt update -y && apt upgrade -y && apt dist-upgrade -y
```

### Passo 2: Instale as dependências

```sh
echo "Instalando dependências ..."
apt install rsync sshpass unzip wakeonlan wget -y
```

### Passo 3: Instale o servidor web

```sh
echo "Instalando Servidor Web ..."
apt install lamp-server^ phpmyadmin -y
```

### Passo 4: Baixe e descompacte o código fonte

Coloque o arquivo `master.zip` na raiz do projeto e descompacte-o:

```sh
echo "Descompactando arquivos"
unzip master.zip
```

### Passo 5: Mova os arquivos e ajuste as permissões

```sh
echo "Movendo Arquivos e ajustando permissões"
mv safekup-master safekup && mv safekup /var/www/html/ && sudo chmod -R 775 /var/www/html/safekup && sudo chown -R www-data:www-data /var/www/html/safekup
```

### Passo 6: Ajuste os arquivos de conexão com o banco de dados

```sh
echo "Ajustando arquivos do Banco de Dados"
declare senhaBd
echo "Digite a senha de acesso ao seu Banco de Dados"
read -s senhaBd

echo "Ajustando os arquivos de conexão com o Banco de Dados ..."
find /var/www/html/safekup/php/conexao -type f -exec sed -i 's/ColoqueSuaSenhaAqui/'$senhaBd'/g' '{}' \;
find /var/www/html/safekup/php/database -type f -exec sed -i 's/ColoqueSuaSenha/'$senhaBd'/g' '{}' \;
```

### Passo 7: Ajuste o script de backup no crontab

```sh
echo "Ajustando script de backup no crontab"
echo "0 * * * * sh /var/www/html/safekup/php/backup/executa_backup.sh" >> /var/spool/cron/crontabs/root
chmod +x /var/spool/cron/crontabs/root
```

### Passo 8: Crie os diretórios necessários

```sh
echo "Criando Diretórios necessários ..."
sudo mkdir -p /home/safekup && sudo chown -R www-data:www-data /home/safekup
sudo mkdir -p /mnt/{cliente,servidor} && sudo chown -R www-data:www-data /mnt/cliente/ /mnt/servidor/ && sudo chmod -R 775 /mnt/cliente/ /mnt/servidor/
```

### Passo 9: Ajuste o arquivo /etc/sudoers

```sh
echo "Ajustando o arquivo /etc/sudoers"
echo "www-data ALL=(ALL:ALL) NOPASSWD:ALL" >> /etc/sudoers
```

### Passo 10: Crie e configure o banco de dados

```sh
echo "Criando Banco de Dados"
mysql -u root -p$senhaBd -e "create database safekup";

echo "Importando e Populando tabelas do Banco de Dados"
mysql -h localhost -u root -p$senhaBd safekup < dump-safekup.sql
```

## Uso

Após a instalação, você pode acessar o sistema no navegador através do endereço:

```
http://IP_DO_SERVIDOR/safekup
```

## Suporte

Para suporte, entre em contato através do email: suporte@safekup.com

## Contribuições

Contribuições são bem-vindas! Para contribuir, siga os passos abaixo:

1. Faça um fork do projeto
2. Crie uma nova branch (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas alterações (`git commit -am 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

## Licença

Este projeto está licenciado sob a licença MIT - veja o arquivo [LICENSE](LICENSE) para mais detalhes.



---

Desenvolvido pela equipe de desenvolvimento do Setor de Tecnologia da Informação e Saúde Digital da HU-USFC, rede EBSERH.
```

### Instruções Adicionais

- **Personalização**: Ajuste o script de instalação conforme necessário para atender a requisitos específicos do seu ambiente.
- **Segurança**: Certifique-se de que todas as senhas e informações sensíveis sejam armazenadas e manipuladas de forma segura.
- **Manutenção**: Periodicamente, verifique e atualize o sistema e as dependências para garantir a segurança e o desempenho.# safekup-new
