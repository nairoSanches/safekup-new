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
    // Verifica se a sessão está iniciada e se o usuário está logado
    if (!isset($_SESSION['login'])) {
        handle_error("Usuário não está logado.");
    }

    $usuario = $_SESSION['login'];

    include('../conexao/conexao.php');
    require_once ('../include/encryption.inc.php');

    // Função para sanitizar entradas
    function sanitize($conexao, $data) {
        return mysqli_real_escape_string($conexao, $data);
    }

    // Recebendo os dados de entrada e sanitizando
    if (!isset($_POST['ssh_ip']) || !isset($_POST['ssh_user']) || !isset($_POST['ssh_pass']) || !isset($_POST['ssh_status'])) {
        handle_error("Dados de entrada incompletos.");
    }

    $ssh_ip = sanitize($conexao, $_POST['ssh_ip']);
    $ssh_user = sanitize($conexao, $_POST['ssh_user']);
    $ssh_senha = sanitize($conexao, $encryption->encrypt($_POST['ssh_pass'] ?? ''));
    $ssh_status = sanitize($conexao, $_POST['ssh_status']);

    

    // Configura relatórios de erros do MySQLi
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Verificando se já existe aquele Ip SSH no banco de dados
    $sql = mysqli_query($conexao, "SELECT ssh_ip FROM ssh WHERE ssh_user = '$ssh_user'");
    if ($sql === false) {
        handle_error("Erro na consulta SQL: " . mysqli_error($conexao));
    }

    $count = mysqli_num_rows($sql);

    if ($count != 0) {
        handle_error("Usuário SSH já existe.");
    } else {
        // Inserindo Ip SSH no banco de dados
        $insert_query = "
            INSERT INTO ssh (
                ssh_ip, ssh_user, ssh_pass, ssh_status
            ) VALUES (
                '$ssh_ip', '$ssh_user', '$ssh_senha', '$ssh_status'
            )
        ";

        $insert = mysqli_query($conexao, $insert_query);
        if ($insert === false) {
            handle_error("Erro ao cadastrar Ip SSH: " . mysqli_error($conexao));
        } else {
            handle_success("Cadastro realizado com sucesso.");
        }
    }
} catch (Exception $e) {
    handle_error("Ocorreu um erro: " . $e->getMessage());
}

?>
