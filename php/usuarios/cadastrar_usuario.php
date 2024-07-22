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

// Recebendo os dados de entrada e sanitizando
$nome_usuario = sanitize($conexao, $_POST['nome_usuario']);
$login = sanitize($conexao, $_POST['login']);
$setor = sanitize($conexao, $_POST['setor']);
$senha = md5(sanitize($conexao, $_POST['senha']));
$status = sanitize($conexao, $_POST['status']);
$usuario_email = sanitize($conexao, $_POST['usuario_email']);

// Verificando se já existe aquele usuário no banco de dados
$sql = mysqli_query($conexao, "SELECT usuario_id FROM usuario WHERE usuario_login = '$login'");
$count = mysqli_num_rows($sql);

if ($count != 0) {
	echo "ja_existe_login";
	exit();
} else {
	// Inserindo usuário no banco de dados
	$insert_query = "
        INSERT INTO usuario (
            usuario_nome, usuario_login, usuario_senha, usuario_status, 
            usuario_id_app, usuario_email, usuario_tentativas_invalidas, usuario_data_bloqueio
        ) VALUES (
            UCASE('$nome_usuario'), '$login', '$senha', '$status', 
            '$setor', '$usuario_email', '0', '1900-01-01 00:00:00'
        )
    ";

	if ($insert = mysqli_query($conexao, $insert_query)) {
		echo "cadastro_realizado_com_sucesso";
	} else {

		echo "Erro ao cadastrar usuário: " . mysqli_error($conexao);
	}
}

?>