<?php


session_start();


// Recuperando usuário logado
$usuario = $_SESSION['login'] ?? '';

// Incluindo arquivo de conexão com o banco de dados
require_once ('../conexao/conexao.php');
require_once ('../include/encryption.inc.php');

$encryption = new Encryption();


// Função para inserir cadastrofunction inserirCadastro($conexao, $dados)

function inserirCadastro($conexao, $dados)
{
    $sql = "
        INSERT INTO db_management 
        (bd_tipo, bd_app, bd_nome_usuario, bd_login, bd_senha, bd_ip, bd_porta, 
         bd_dia_0, bd_dia_1, bd_dia_2, bd_dia_3, bd_dia_4, bd_dia_5, bd_dia_6, bd_hora_backup, 
         bd_servidor_backup, bd_data_cadastro, bd_data_alteracao, bd_usuario_adm, bd_backup_ativo, bd_ssh, bd_recorrencia, bd_container)
        VALUES (
            '{$dados['bd_tipo']}', 
            '{$dados['bd_app']}', 
            '{$dados['bd_nome_usuario']}', 
            '{$dados['bd_login']}', 
            '{$dados['bd_senha']}', 
            '{$dados['bd_ip']}', 
            '{$dados['bd_porta']}', 
            '{$dados['dia0']}', 
            '{$dados['dia1']}', 
            '{$dados['dia2']}', 
            '{$dados['dia3']}', 
            '{$dados['dia4']}', 
            '{$dados['dia5']}', 
            '{$dados['dia6']}', 
            '{$dados['bd_hora_backup']}', 
            '{$dados['bd_servidor_backup']}', 
            NOW(), 
            '0000-00-00 00:00:00', 
            '{$dados['bd_usuario_adm']}', 
            '{$dados['bd_backup_ativo']}',
            '{$dados['bd_ssh']}',
            '{$dados['bd_recorrencia']}',
             '{$dados['bd_container']}'
        )
    ";

	
    if (!$conexao->query($sql)) {
        throw new Exception("Erro ao inserir cadastro: (" . $conexao->errno . ") " . $conexao->error);
    }

    // Retornar o ID do último registro inserido
    $last_insert_id = $conexao->insert_id;

    return $last_insert_id;
}





// Função para criar pasta do usuário
function criarPasta($diretorio)
{
	if (!mkdir($diretorio, 0755, true)) {
		throw new Exception("Erro ao criar pasta: $diretorio");
	}
}




try {
	

	// Recebendo os dados enviados via POST
	$dados = [
		'bd_nome_usuario' => $_POST['bd_nome_usuario'] ?? '',
		'bd_login' => $_POST['bd_login'] ?? '',
		'bd_senha' =>  $encryption->encrypt($_POST['bd_senha'] ?? ''),
		'bd_ip'    => $_POST['bd_ip'] ?? '',
		'bd_porta' => $_POST['bd_porta'] ?? '',
		'bd_tipo' => $_POST['tipo_id'] ?? '',
		'dia0' => $_POST['dia0'] ?? 0,
		'dia1' => $_POST['dia1'] ?? 0,
		'dia2' => $_POST['dia2'] ?? 0,
		'dia3' => $_POST['dia3'] ?? 0,
		'dia4' => $_POST['dia4'] ?? 0,
		'dia5' => $_POST['dia5'] ?? 0,
		'dia6' => $_POST['dia6'] ?? 0,
		'bd_hora_backup' => $_POST['bd_hora_backup'] ?? '',
		'bd_servidor_backup' => $_POST['bd_servidor_backup'] ?? '',	
		'bd_app' => $_POST['bd_app'] ?? '',		
		'bd_usuario_adm' => $_POST['bd_usuario_adm'] ?? '',
		'bd_backup_ativo' => $_POST['bd_backup_ativo'] ?? '',
        'bd_ssh' => $_POST['bd_ssh'] ?? '',
        'bd_recorrencia' => $_POST['bd_recorrencia'] ?? '', 	
        'bd_container' => $_POST['bd_container'] ?? '0'		
	];

	// Inserindo cadastro e recuperando o ID do computador
	$bd_id = inserirCadastro($conexao, $dados);
	
	echo json_encode(['sucesso' => 'cadastro_realizado_com_sucesso']);

} catch (Exception $e) {
	echo json_encode(['erro' => $e->getMessage()]);
	exit();
}


