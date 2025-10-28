<?php
require_once("../conexao/conexao_pdo.php"); // Conexão com PDO

if (isset($_GET['bd_id'])) {
    $bd_id = intval($_GET['bd_id']); // Sanitiza o input
    $conexao = conectar(); // Função para estabelecer a conexão PDO

    // Consulta para obter as informações do banco
    $query = "
        SELECT A.bd_nome_usuario, A.bd_login, A.bd_ip, A.bd_tipo, B.restore_ip, B.restore_user, B.restore_senha_acesso, C.tipo_nome
        FROM db_management A
        INNER JOIN restores B ON A.bd_id_restore = B.restore_id
        INNER JOIN tipo C ON A.bd_tipo = C.tipo_id
        WHERE A.bd_id = :bd_id
    ";

    $stmt = $conexao->prepare($query);
    $stmt->bindParam(':bd_id', $bd_id, PDO::PARAM_INT);
    $stmt->execute();

    $db = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$db) {
        die("ERRO: Banco de dados com ID $bd_id não encontrado.");
    }

    $restore_ip = $db['bd_ip'];
    $bd_login = $db['bd_login'];
    $restore_password = 'Consulte a Senha no Teampass'; // Aqui você pode usar decrypt se necessário
    $backup_dir = "/DBbackup/{$db['bd_ip']}/{$db['tipo_nome']}/{$db['bd_nome_usuario']}";
    $restore_db_name = "{$db['bd_nome_usuario']}";

    // Script para descompactar e restaurar
    $script = <<<SCRIPT
#!/bin/bash
echo "Iniciando restore para o banco de dados: {$db['bd_nome_usuario']}"

# Caminho do arquivo de backup mais recente
LATEST_BACKUP=\$(ls -t $backup_dir/*.zip | head -n 1)
if [ -z "\$LATEST_BACKUP" ]; then
    echo "ERRO: Nenhum arquivo de backup encontrado em $backup_dir"
    exit 1
fi

# Descompactar o arquivo
UNZIPPED_FILE="/tmp/restore_\$(basename \$LATEST_BACKUP .zip).sql"
unzip -o "\$LATEST_BACKUP" -d /tmp/

if [ ! -f "\$UNZIPPED_FILE" ]; then
    echo "ERRO: Falha ao descompactar o arquivo \$LATEST_BACKUP"
    exit 1
fi

SCRIPT;

    // Comandos específicos para MySQL ou PostgreSQL
    if ($db['bd_tipo'] == 1) { // MySQL
        $script .= <<<SCRIPT
# Comando de restore MySQL
mysql -h $restore_ip -u $bd_login -p'$restore_password' -e "DROP DATABASE IF EXISTS \`$restore_db_name\`;"
mysql -h $restore_ip -u $bd_login -p'$restore_password' -e "CREATE DATABASE \`$restore_db_name\`;"
mysql -h $restore_ip -u $bd_login -p'$restore_password' $restore_db_name < "\$UNZIPPED_FILE"

if [ \$? -eq 0 ]; then
    echo "Restore MySQL concluído com sucesso!"
else
    echo "ERRO: Falha no restore do banco MySQL!"
fi
SCRIPT;

    } elseif ($db['bd_tipo'] == 2) { // PostgreSQL
        $script .= <<<SCRIPT
# Comando de restore PostgreSQL
export PGPASSWORD='$restore_password'
psql -h $restore_ip -U $bd_login -d postgres -c "DROP DATABASE IF EXISTS \\"$restore_db_name\\";"
psql -h $restore_ip -U $bd_login -d postgres -c "CREATE DATABASE \\"$restore_db_name\\";"
psql -h $restore_ip -U $bd_login -d "$restore_db_name" -f "\$UNZIPPED_FILE"

if [ \$? -eq 0 ]; then
    echo "Restore PostgreSQL concluído com sucesso!"
else
    echo "ERRO: Falha no restore do banco PostgreSQL!"
fi
SCRIPT;

    } else {
        die("ERRO: Tipo de banco de dados não suportado.");
    }

    // Exibe o script gerado
    header('Content-Type: text/plain');
    echo $script;

} else {
    echo "ERRO: ID do banco de dados não fornecido!";
}
?>