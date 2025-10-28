<?php

// Iniciando a sessão
session_start();

// Recuperando o usuário logado
$usuario = $_SESSION['login'] ?? null;

// Incluindo o arquivo de conexão com o banco de dados
require_once '../conexao/conexao.php';

// Validando os campos obrigatórios
if (empty($_POST['restore_nome']) || empty($_POST['restore_ip'])) {
    die("false");
}

// Sanitizando os dados de entrada
$restore_nome = mysqli_real_escape_string($conexao, $_POST['restore_nome']);
$restore_ip = mysqli_real_escape_string($conexao, $_POST['restore_ip']);

// Verificando se o servidor já está cadastrado
$query = "SELECT restore_nome FROM restores WHERE restore_nome = '$restore_nome'";
$resultado = mysqli_query($conexao, $query);

if (mysqli_num_rows($resultado) > 0) {
    echo "ja_existe";
    exit;
}

// Inserindo o novo servidor
$query_insert = "INSERT INTO restores (restore_id, restore_nome, restore_ip) VALUES (DEFAULT, '$restore_nome', '$restore_ip')";
if (mysqli_query($conexao, $query_insert)) {
    echo "true";
} else {
    echo "Erro ao inserir registro: " . mysqli_error($conexao);
}

?>
