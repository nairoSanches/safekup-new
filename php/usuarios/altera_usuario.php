<?php
// Iniciando sessão
session_start();

// Recuperando usuario logado
$usuario = $_SESSION['login'] ?? null;

// Verificando se o usuário está logado
if (!$usuario) {
    die("Usuário não autenticado.");
}

// Incluindo arquivo de conexao com o banco de dados
include('../conexao/conexao.php');

// Recebendo os dados do formulário
$usuario_id = $_POST['usuario_id'] ?? null;
$usuario_nome = $_POST['usuario_nome'] ?? null;
$usuario_status = $_POST['usuario_status'] ?? null;
$usuario_id_app = $_POST['usuario_id_app'] ?? null;
$usuario_email = $_POST['usuario_email'] ?? null;

// Verificando se todos os campos obrigatórios foram fornecidos
if (!$usuario_id || !$usuario_nome || !$usuario_status || !$usuario_id_app || !$usuario_email) {
    die("Todos os campos são obrigatórios.");
}

// Função para escapar dados e evitar SQL Injection
function escape($conexao, $dados) {
    return mysqli_real_escape_string($conexao, trim($dados));
}

// Escapando os dados recebidos
$usuario_id = escape($conexao, $usuario_id);
$usuario_nome = escape($conexao, $usuario_nome);
$usuario_status = escape($conexao, $usuario_status);
$usuario_id_app = escape($conexao, $usuario_id_app);
$usuario_email = escape($conexao, $usuario_email);

// Alterando o cadastro
$query = "
    UPDATE usuario 
    SET 
        usuario_nome = UCASE(?), 
        usuario_status = ?, 
        usuario_id_app = ?, 
        usuario_email = ?, 
        usuario_tentativas_invalidas = '0', 
        usuario_data_bloqueio = '0000-00-00 00:00:00' 
    WHERE 
        usuario_id = ?
";

$stmt = $conexao->prepare($query);
$stmt->bind_param("ssisi", $usuario_nome, $usuario_status, $usuario_id_app, $usuario_email, $usuario_id);

if ($stmt->execute()) {
        echo "cadastro_alterado_com_sucesso";
    
} else {
    echo "Erro ao alterar cadastro: " . $stmt->error;
}

$stmt->close();

?>
