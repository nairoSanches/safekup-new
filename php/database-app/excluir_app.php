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

    // Verificando se o aplicativo existe
    $sql = "SELECT app_nome FROM aplicacao WHERE app_id = ?";
    $stmt = $conexao->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('i', $app_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            echo "app_nao_encontrado";
            $stmt->close();
            exit();
        }
        $stmt->close();
    } else {
        echo "Erro na preparação da consulta: " . $conexao->error;
        exit();
    }

    // Deletando o aplicativo
    $delete_sql = "DELETE FROM aplicacao WHERE app_id = ?";
    $delete_stmt = $conexao->prepare($delete_sql);

    if ($delete_stmt) {
        $delete_stmt->bind_param('i', $app_id);
        if ($delete_stmt->execute()) {
            echo "app_excluido_com_sucesso";
        } else {
            echo "erro_ao_excluir_app: " . $delete_stmt->error;
        }
        $delete_stmt->close();
    } else {
        echo "Erro na preparação da consulta de exclusão: " . $conexao->error;
    }
} else {
    echo "Método de requisição inválido.";
}

// Fechando a conexão com o banco de dados
$conexao->close();
?>
