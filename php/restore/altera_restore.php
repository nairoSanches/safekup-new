<?php

// Iniciando sessão
session_start();

// Recuperando usuario logado
$usuario = $_SESSION['login'];

// Arquivo de conexão com o banco de dados
include('../conexao/conexao.php');

// Modulos
require_once('/opt/lampp/htdocs/safekup/php/include/encryption.inc.php');

// Intancia
$encryption = new Encryption();

// Dados de entrada
$restore_id = mysqli_escape_string($conexao, $_POST['restore_id']);
$restore_nome = mysqli_escape_string($conexao, $_POST['restore_nome']);
$restore_ip = mysqli_escape_string($conexao, $_POST['restore_ip']);
$restore_user = mysqli_escape_string($conexao, $_POST['restore_user']);
$restore_senha_acesso = mysqli_escape_string($conexao, $encryption->encrypt($_POST['restore_senha_acesso']));


$query =  "
UPDATE restores 
SET 
	restore_nome = '$restore_nome',
	restore_ip = '$restore_ip',
	restore_user = '$restore_user',
	restore_senha_acesso = '$restore_senha_acesso'
WHERE restore_id = $restore_id
";

$update = mysqli_query($conexao, $query);

if ($update) {
	echo "true";
	exit();

} else {
	echo mysqli_error($conexao);
}

?>