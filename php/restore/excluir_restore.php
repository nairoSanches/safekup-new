<?php

session_start();

// Recuperando usuario logado
$usuario = $_SESSION['login'];

include ('../conexao/conexao.php');

// Função para sanitizar entradas
function sanitize($conexao, $data)
{
	return mysqli_real_escape_string($conexao, $data);
}

$restore_id = sanitize($conexao, $_POST['restore_id']);

// Recuperando nome do servidor
$sql = mysqli_query($conexao, "SELECT restore_nome FROM restores WHERE restore_id = '$restore_id'");
$dados = mysqli_fetch_array($sql);
$restore_nome = $dados['restore_nome'];

// Verificando se o servidor está associado a algum banco de dados
$sql = mysqli_query($conexao, "
    SELECT bd_restore_backup, restore_id 
    FROM restores A 
    JOIN restores B ON A.bd_restore_backup = B.restore_id 
    WHERE restore_id = '$restore_id'
");

$count = mysqli_num_rows($sql);

if ($count != 0) {
	echo "bd_associado";
	exit();
} else {

	if ($delete = mysqli_query($conexao, "DELETE FROM restores WHERE restore_id = '$restore_id'")) {
		echo "true";
	} else {
		echo "false";
		echo "Erro ao deletar servidor: " . mysqli_error($conexao);
	}
}
?>