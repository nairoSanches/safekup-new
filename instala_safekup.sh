#!/bin/bash 

# Atualizando sistema
echo "Atualizando o Servidor ..."
apt update -y && apt upgrade -y && apt dist-upgrade -y

echo "------------------------------------------------------------"
echo "Servidor atualizado com sucesso"
sleep 3
clear
sleep 3

# Instalando dependências
echo "Instalando dependências ..."
apt install rsync sshpass unzip wakeonlan wget -y

echo "------------------------------------------------------------"
echo "Dependências instaladas com sucesso"
sleep 3
clear
sleep 3

# Instalando Servidor Web
echo "Instalando Servidor Web ..."
apt install lamp-server^ phpmyadmin -y

echo "------------------------------------------------------------"
echo "Servidor web instalado com sucesso"
sleep 3
clear
sleep 3

# Descompactando arquivos
echo "Descompactando arquivos"
unzip master.zip

echo "------------------------------------------------------------"
echo "Código descompactado com sucesso"
sleep 3
clear
sleep 3

# Movendo Arquivos e ajustando permissões
echo "Movendo Arquivos e ajustando permissões"
mv safekup-master safekup && mv safekup /var/www/html/ && sudo chmod -R 775 /var/www/html/safekup && sudo chown -R www-data:www-data /var/www/html/safekup

echo "------------------------------------------------------------"
echo "Processo realizado com sucesso"
sleep 3
clear
sleep 3

# Ajustando arquivos do Banco de Dados
echo "Ajustando arquivos do Banco de Dados"
declare senhaBd

echo "Digite a senha de acesso ao seu Banco de Dados"
read -s senhaBd

echo "Ajustando os arquivos de conexão com o Banco de Dados ..."

find /var/www/html/safekup/php/conexao -type f -exec sed -i 's/ColoqueSuaSenhaAqui/'$senhaBd'/g' '{}' \;
find /var/www/html/safekup/php/database -type f -exec sed -i 's/ColoqueSuaSenha/'$senhaBd'/g' '{}' \;

echo "------------------------------------------------------------"
echo "Arquivos ajustados com sucesso"
sleep 3
clear
sleep 3

# Ajustando script de backup no crontab
echo "Ajustando script de backup no crontab"

echo "0 * * * * sh /var/www/html/safekup/php/backup/executa_backup.sh" >> /var/spool/cron/crontabs/root
chmod +x /var/spool/cron/crontabs/root

echo "------------------------------------------------------------"
echo "Script de backup ajustado com sucesso"
sleep 3
clear
sleep 3

# Criando Diretórios necessários
echo "Criando Diretórios necessários ..."

sudo mkdir -p /home/safekup && sudo chown -R www-data:www-data /home/safekup
sudo mkdir -p /mnt/{cliente,servidor} && sudo chown -R www-data:www-data /mnt/cliente/ /mnt/servidor/ && sudo chmod -R 775 /mnt/cliente/ /mnt/servidor/

echo "--------------------------------------------------------------"
echo "Diretórios criados com sucesso"
sleep 3
clear
sleep 3

# Ajustando o arquivo /etc/sudoers
echo "Ajustando o arquivo /etc/sudoers"
echo "www-data ALL=(ALL:ALL) NOPASSWD:ALL" >> /etc/sudoers

echo "--------------------------------------------------------------"
echo "Arquivo ajustado com sucesso"
sleep 3
clear
sleep 3

# Criando Banco de Dados
echo "Criando Banco de Dados"
mysql -u root -p$senhaBd -e "create database safekup";

echo "----------------------------------------------------------------"
echo "Banco de Dados criado com sucesso"
sleep 3
clear
sleep 3

# Importando arquivo de Banco de Dados
echo "Importando e Populando tabelas do Banco de Dados"
mysql -h localhost -u root -p$senhaBd safekup < dump-safekup.sql

echo "----------------------------------------------------------------"
echo "Banco de Dados configurado com sucesso"
sleep 3
clear
sleep 3

echo "Para acessar seu sistema, abra o navegador e digite: IPSERVIDOR/safekup"
echo ""
echo "Obrigado por instalar nosso Sistema!"
