<?php
// Iniciando sessão
session_start();

// Verificando se o usuário está logado
if (!isset($_SESSION['login'])) {
    die("Acesso negado.");
}

$usuario = $_SESSION['login'];

// Incluindo a conexão com o banco de dados
include('../conexao/conexao.php');

// Verificando se os dados foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Filtrando e sanitizando os dados de entrada
    $app_id = intval($_POST['app_id']);
    $setor = mysqli_real_escape_string($conexao, $_POST['app_nome']);
    $app_nome = strtoupper(str_replace(" ", "", $setor));
    $app_descricao = mysqli_real_escape_string($conexao, $_POST['descricao_app']);

    // Alterando o cadastro
    $sql = "UPDATE aplicacao SET app_nome = ?, app_descricao = ? WHERE app_id = ?";
    $stmt = $conexao->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param('ssi', $app_nome, $app_descricao, $app_id);
        if ($stmt->execute()) {
            echo "cadastro_alterado_com_sucesso";
        } else {
            echo "Erro ao alterar cadastro: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Erro na preparação da consulta: " . $conexao->error;
    }
} else {
    echo "Método de requisição inválido.";
}

// Fechando a conexão com o banco de dados
$conexao->close();
?>
