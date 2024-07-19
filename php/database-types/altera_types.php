<?php

session_start();

// Verificando se o usuário está logado
if (!isset($_SESSION['login'])) {
    die("Usuário não autenticado.");
}

$usuario = $_SESSION['login'];

// Incluindo arquivo de conexão com o banco de dados
include('../conexao/conexao.php');

// Recebendo os dados do formulário
$tipo_id = $_POST['tipo_id'] ?? null;
$tipo_nome = $_POST['tipo_nome'] ?? null;
$tipo_plataforma = $_POST['tipo_plataforma'] ?? null;

// Verificando se os dados obrigatórios foram fornecidos
if (empty($tipo_id) || empty($tipo_nome) || empty($tipo_plataforma)) {
    die("Todos os campos são obrigatórios.");
}

// Função para escapar dados e evitar SQL Injection
function escape($conexao, $dados) {
    return mysqli_real_escape_string($conexao, trim($dados));
}

// Escapando os dados recebidos
$tipo_id = escape($conexao, $tipo_id);
$tipo_nome = escape($conexao, $tipo_nome);
$tipo_plataforma = escape($conexao, $tipo_plataforma);

// Verificando se já existe o Tipo de Banco de Dados cadastrado
$query = "SELECT tipo_id FROM tipo WHERE tipo_nome = ? AND tipo_id != ?";
$stmt = $conexao->prepare($query);
$stmt->bind_param("si", $tipo_nome, $tipo_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo "ja_existe_so";
    exit();
}

// Alterando o cadastro
$query = "UPDATE tipo SET tipo_nome = UCASE(?),tipo_plataforma = ? WHERE tipo_id = ?";
$stmt = $conexao->prepare($query);
$stmt->bind_param("ssi", $tipo_nome, $tipo_plataforma, $tipo_id);

if ($stmt->execute()) {
    echo "cadastro_alterado_com_sucesso";
} else {
    echo "Erro ao alterar cadastro: " . $stmt->error;
}

$stmt->close();
$conexao->close();
?>
