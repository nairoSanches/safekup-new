<?php
// Iniciando sessão
session_start();

// Verificando se o usuário está logado
if (!isset($_SESSION['login'])) {
    echo "Sessão expirada ou usuário não autenticado.";
    exit();
}

// Conexão com o banco de dados
include('../conexao/conexao.php');

// Recuperando e sanitizando o ID do banco de dados a ser deletado
$bd_id = isset($_POST['bd_id']) ? mysqli_real_escape_string($conexao, $_POST['bd_id']) : '';

// Verificando se o ID do banco de dados foi enviado
if (empty($bd_id)) {
    echo "ID do banco de dados não foi fornecido.";
    exit();
}

// Consultando o nome do usuário do banco de dados antes de deletar
$sql = "SELECT bd_nome_usuario FROM db_management WHERE bd_id = '$bd_id'";
$result = mysqli_query($conexao, $sql);

if (!$result) {
    echo "Erro ao consultar o banco de dados: " . mysqli_error($conexao);
    exit();
}

$dados = mysqli_fetch_array($result);
$bd_nome_usuario = $dados['bd_nome_usuario'];

// Deletando o banco de dados do banco de dados
$delete_sql = "DELETE FROM db_management WHERE bd_id = '$bd_id'";

if (mysqli_query($conexao, $delete_sql)) {
    echo "Banco de dados de ID $bd_id (Base:  $bd_nome_usuario) foi deletado com sucesso.";
} else {
    echo "Erro ao deletar o banco de dados: " . mysqli_error($conexao);
}

// Fechando a conexão com o banco de dados
mysqli_close($conexao);
?>
