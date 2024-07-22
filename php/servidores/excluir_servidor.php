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

$servidor_id = sanitize($conexao, $_POST['servidor_id']);

// Recuperando nome do servidor
$sql = mysqli_query($conexao, "SELECT servidor_nome FROM servidores WHERE servidor_id = '$servidor_id'");
$dados = mysqli_fetch_array($sql);
$servidor_nome = $dados['servidor_nome'];

// Verificando se o servidor está associado a algum banco de dados
$sql = mysqli_query($conexao, "
    SELECT bd_servidor_backup, servidor_id 
    FROM db_management A 
    JOIN servidores B ON A.bd_servidor_backup = B.servidor_id 
    WHERE servidor_id = '$servidor_id'
");

$count = mysqli_num_rows($sql);

if ($count != 0) {
	echo "bd_associado";
	exit();
} else {

	if ($delete = mysqli_query($conexao, "DELETE FROM servidores WHERE servidor_id = '$servidor_id'")) {
		echo "true";
	} else {
		echo "false";
		echo "Erro ao deletar servidor: " . mysqli_error($conexao);
	}
}
?>