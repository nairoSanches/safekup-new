<?php
session_start();

function handle_error($message) {
    echo json_encode(["error" => $message]);
    exit();
}

function handle_success($message) {
    echo json_encode(["success" => $message]);
    exit();
}

try {
    // Recuperando usuario logado
    $usuario = $_SESSION['login'] ?? null;

    // Verificando se o usuário está logado
    if (!$usuario) {
        handle_error("Usuário não autenticado.");
    }

    // Incluindo arquivo de conexao com o banco de dados
    include('../conexao/conexao.php');

    // Recebendo os dados do formulário
    $ssh_id = $_POST['ssh_id'] ?? null;
    $ssh_ip = $_POST['ssh_ip'] ?? null;
    $ssh_status = $_POST['ssh_status'] ?? null;
    $ssh_senha =  $encryption->encrypt($_POST['ssh_pass']);

    // Verificando se todos os campos obrigatórios foram fornecidos
    if (!$ssh_id || !$ssh_ip || !$ssh_status) {
        handle_error("Todos os campos são obrigatórios.");
    }

    // Função para escapar dados e evitar SQL Injection
    function escape($conexao, $dados) {
        return mysqli_real_escape_string($conexao, trim($dados));
    }

    // Escapando os dados recebidos
    $ssh_id = escape($conexao, $ssh_id);
    $ssh_ip = escape($conexao, $ssh_ip);
    $ssh_status = escape($conexao, $ssh_status);

    // Configura relatórios de erros do MySQLi
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Alterando o cadastro
    $query = "
        UPDATE ssh 
        SET 
            ssh_ip = ?, 
            ssh_status = ? 
        WHERE 
            ssh_id = ?
    ";

    $stmt = $conexao->prepare($query);
    if ($stmt === false) {
        handle_error("Erro na preparação da consulta SQL: " . $conexao->error);
    }

    $stmt->bind_param("ssi", $ssh_ip, $ssh_status, $ssh_id);

    if ($stmt->execute()) {
        handle_success("Cadastro alterado com sucesso.");
    } else {
        handle_error("Erro ao alterar cadastro: " . $stmt->error);
    }

    $stmt->close();
} catch (Exception $e) {
    handle_error("Ocorreu um erro: " . $e->getMessage());
}

?>
