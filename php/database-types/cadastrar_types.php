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
$nome_so = $_POST['nome_so'] ?? null;
$plataforma = $_POST['plataforma'] ?? null;

// Verificando se os dados obrigatórios foram fornecidos
if (empty($nome_so) || empty($plataforma)) {
    die("Todos os campos são obrigatórios.");
}

// Função para escapar dados e evitar SQL Injection
function escape($conexao, $dados) {
    return mysqli_real_escape_string($conexao, trim($dados));
}

// Escapando os dados recebidos
$nome_so = escape($conexao, $nome_so);
$plataforma = escape($conexao, $plataforma);

// Verificando se já existe o Tipo de Banco de Dados cadastrado
$query = "SELECT tipo_id FROM tipo WHERE tipo_nome = ?";
$stmt = $conexao->prepare($query);
$stmt->bind_param("s", $nome_so);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo "ja_existe_so";
    exit();
}

// Inserindo Tipo de Banco de Dados no banco de dados
$query = "INSERT INTO tipo (tipo_nome,tipo_plataforma) VALUES (UCASE(?), UCASE(?))";
$stmt = $conexao->prepare($query);
$stmt->bind_param("ss", $nome_so, $plataforma);

if ($stmt->execute()) {
    echo "cadastro_realizado_com_sucesso";
} else {
    echo "erro_ao_cadastrar: " . $stmt->error;
}

$stmt->close();
$conexao->close();
?>
