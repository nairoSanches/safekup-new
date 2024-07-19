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
    $setor = mysqli_real_escape_string($conexao, $_POST['nome_app']);
    $nome_app = strtoupper(str_replace(" ", "_", $setor));
    $descricao_app = mysqli_real_escape_string($conexao, $_POST['descricao_app']);

    // Verificando se o aplicativo já existe
    $sql = "SELECT app_nome FROM aplicacao WHERE app_nome = ?";
    $stmt = $conexao->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('s', $nome_app);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo "ja_existe_app";
            $stmt->close();
            exit();
        }
        $stmt->close();
    } else {
        echo "Erro na preparação da consulta: " . $conexao->error;
        exit();
    }

    // Inserindo o novo aplicativo
    $insert_sql = "INSERT INTO aplicacao (app_nome, app_descricao) VALUES (?, ?)";
    $insert_stmt = $conexao->prepare($insert_sql);

    if ($insert_stmt) {
        $insert_stmt->bind_param('ss', $nome_app, $descricao_app);
        if ($insert_stmt->execute()) {
            echo "cadastro_realizado_com_sucesso";
        } else {
            echo "erro_ao_cadastrar: " . $insert_stmt->error;
        }
        $insert_stmt->close();
    } else {
        echo "Erro na preparação da consulta de inserção: " . $conexao->error;
    }
} else {
    echo "Método de requisição inválido.";
}

// Fechando a conexão com o banco de dados
$conexao->close();
?>
