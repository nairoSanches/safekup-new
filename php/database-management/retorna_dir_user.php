<?php

//Incluindo arquivo de conexao com o banco de dados
include('../conexao/conexao.php');

//Recebendo ID do usuario selecionado
$bd_id = $_POST['bd_id'];

//Pegando dados do usuario


$consulta = mysqli_query($conexao,"SELECT * FROM computadores A 
	JOIN sistemas_operacionais B ON A.bd_tipo = B.tipo_id 
	JOIN associar_doc_computador C ON A.bd_id = C.assoc_id_computador
	JOIN documentos D ON D.documento_id = C.assoc_id_documentos
	JOIN diretorio_documentos E ON E.diretorio_id_documentos = D.documento_id
	JOIN servidores F ON A.bd_servidor_backup = F.servidor_id
	JOIN setores G ON A.bd_app = G.app_id
	WHERE A.bd_tipo = E.diretorio_id_sistema_operacional AND bd_id = '$bd_id' ");

$dados = mysqli_fetch_array($consulta);

$plataforma = $dados['tipo_plataforma'];
	$ip = $dados['bd_ip'];
	$usuario = $dados['bd_login'];
	$senha = base64_decode($dados['bd_senha']);
	$documentos[] = $dados['documento_nome'];
	$diretorio_documentos = $dados['diretorio_documentos'];
	$bd_nome_usuario = $dados['bd_nome_usuario'];
	$bd_id = $dados['bd_id'];
	$bd_liga_computador = $dados['bd_liga_computador'];
	$bd_desliga_computador = $dados['bd_desliga_computador'];
	$bd_porta = $dados['bd_porta'];
	$servidor_ip = $dados['servidor_ip'];
	$servidor_nome_compartilhamento = $dados['servidor_nome_compartilhamento'];
	$servidor_plataforma = $dados['servidor_plataforma'];
	$servidor_user_privilegio = $dados['servidor_user_privilegio'];
	$servidor_senha_acesso = base64_decode($dados['servidor_senha_acesso']);
	$bd_usuario_adm = $dados['bd_usuario_adm'];
	$app_nome = $dados['app_nome'];



	// Ajustando diretorio dos documentos

	$usuarioAjustado = $usuario."/";
	$diretorio_documentos = str_replace("C:/", "", $diretorio_documentos);
	$diretorio_documentos = str_replace("c:/", "", $diretorio_documentos);
	$diretorio_documentos = str_replace("usuario/", $usuarioAjustado, $diretorio_documentos);
	$diretorio_documentos = str_replace("Usuario/", $usuarioAjustado, $diretorio_documentos);
	$diretorio_documentos = str_replace("usuario/", $usuarioAjustado, $diretorio_documentos);
	$diretorio_documentos = str_replace("Usuario/", $usuarioAjustado, $diretorio_documentos);
	$diretorio_documentos = str_replace(" ", "\ ", $diretorio_documentos);


//Listar arquivos do usuario no servidor de backup

//Verifiar se o servidor de backup do usuario é o proprio servidor de aplicacao
if($servidor_ip == '127.0.0.1'){

	$dir = "/home/safekup/$app_nome/$bd_nome_usuario/";	
	$abre_dir = ($_GET['dir'] != '' ? $_GET['dir'] : $dir);
	$open_dir = dir($abre_dir);

	echo "<table class='table table-striped table-bordered table-hover'>";
	
	while($arq = $open_dir ->read()){

	echo "<tr>";
	echo "<td>.$arq.</td>";
	echo "</tr>";	
		


	}

	echo "</table>";

	$open_dir -> close();
	

}	





?>