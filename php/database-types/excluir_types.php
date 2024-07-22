<?php

session_start();

// Verificando se o usuário está logado
if (!isset($_SESSION['login'])) {
    die("Usuário não autenticado.");
}

$usuario = $_SESSION['login'];

// Incluindo arquivo de conexão com o banco de dados
include('../conexao/conexao.php');

// Recebendo dados de entrada
$tipo_id = $_POST['tipo_id'] ?? null;

// Verificando se o ID do sistema operacional foi fornecido
if (empty($tipo_id)) {
    die("ID do sistema operacional é obrigatório.");
}

// Função para escapar dados e evitar SQL Injection
function escape($conexao, $dados) {
    return mysqli_real_escape_string($conexao, trim($dados));
}

// Escapando o ID do sistema operacional
$tipo_id = escape($conexao, $tipo_id);

// Consultando no banco de dados
$query = "SELECT tipo_id, bd_tipo 
          FROM sistemas_operacionais A 
          JOIN computadores B ON A.tipo_id = B.bd_tipo 
          WHERE tipo_id = ?";
$stmt = $conexao->prepare($query);
$stmt->bind_param("i", $tipo_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo "Não é possível excluir, sistema operacional associado a um ou mais computadores.";
    exit();
}

// Deletando o sistema operacional
$query = "DELETE FROM sistemas_operacionais WHERE tipo_id = ?";
$stmt = $conexao->prepare($query);
$stmt->bind_param("i", $tipo_id);

if ($stmt->execute()) {
    echo "so_excluido_com_sucesso";
} else {
    echo "Erro ao excluir sistema operacional: " . $stmt->error;
}

$stmt->close();
$conexao->close();
?>
