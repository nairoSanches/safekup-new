<?php

// Iniciando sessão
session_start();

// Recuperando usuario logado
$usuario = $_SESSION['login'];

// Arquivo de conexão com o banco de dados
include('../conexao/conexao.php');

// Dados de entrada
$servidor_id = mysqli_escape_string($conexao,$_POST['servidor_id']);
$servidor_nome = mysqli_escape_string($conexao,$_POST['servidor_nome']);
$servidor_ip = mysqli_escape_string($conexao,$_POST['servidor_ip']);
$servidor_user_privilegio = mysqli_escape_string($conexao,$_POST['servidor_user_privilegio']);
$servidor_senha_acesso = mysqli_escape_string($conexao,base64_encode($_POST['servidor_senha_acesso']));
$servidor_nome_compartilhamento = mysqli_escape_string($conexao,($_POST['servidor_nome_compartilhamento']));
$servidor_plataforma = mysqli_escape_string($conexao,$_POST['servidor_plataforma']);


$update = mysqli_query($conexao,"UPDATE servidores SET servidor_nome = '$servidor_nome',servidor_ip = '$servidor_ip',servidor_user_privilegio = '$servidor_user_privilegio',servidor_senha_acesso = '$servidor_senha_acesso', servidor_nome_compartilhamento = '$servidor_nome_compartilhamento', servidor_plataforma = '$servidor_plataforma' WHERE servidor_id = '$servidor_id'  ");

if($update){

	echo "true";
	exit();

} else {

	echo mysqli_error($conexao);
} 

?>