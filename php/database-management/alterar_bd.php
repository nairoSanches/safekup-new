<?php
 /*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
// Iniciando sessão
session_start();

// Verificando se o usuário está logado
if (!isset($_SESSION['login'])) {
	echo "Usuário não autenticado";
	exit();
}

// Incluindo arquivo de conexão com o banco de dados
require_once ('../conexao/conexao.php');
require_once ('../include/encryption.inc.php');

$encryption = new Encryption();

// Recebendo e validando os dados enviados via POST
$bd_id = isset($_POST['bd_id']) ? $_POST['bd_id'] : null;
$bd_nome_usuario = isset($_POST['bd_nome_usuario']) ? $_POST['bd_nome_usuario'] : null;
$bd_login = isset($_POST['bd_login']) ? $_POST['bd_login'] : null;
$bd_senha = isset($_POST['bd_senha']) ? $encryption->encrypt($_POST['bd_senha']) : null;
$bd_ip = isset($_POST['bd_ip']) ? $_POST['bd_ip'] : null;
$bd_porta = isset($_POST['bd_porta']) ? $_POST['bd_porta'] : null;
$bd_tipo = isset($_POST['tipo_id']) ? $_POST['tipo_id'] : null;
$bd_dia0 = isset($_POST['dia0']) ? $_POST['dia0'] : null;
$bd_dia1 = isset($_POST['dia1']) ? $_POST['dia1'] : null;
$bd_dia2 = isset($_POST['dia2']) ? $_POST['dia2'] : null;
$bd_dia3 = isset($_POST['dia3']) ? $_POST['dia3'] : null;
$bd_dia4 = isset($_POST['dia4']) ? $_POST['dia4'] : null;
$bd_dia5 = isset($_POST['dia5']) ? $_POST['dia5'] : null;
$bd_dia6 = isset($_POST['dia6']) ? $_POST['dia6'] : null;
$bd_hora_backup = isset($_POST['bd_hora_backup']) ? $_POST['bd_hora_backup'] : null;
$bd_servidor_backup = isset($_POST['bd_servidor_backup']) ? $_POST['bd_servidor_backup'] : null;
$bd_app = isset($_POST['bd_app']) ? $_POST['bd_app'] : null;
$bd_usuario_adm = isset($_POST['bd_usuario_adm']) ? $_POST['bd_usuario_adm'] : null;
$bd_backup_ativo = isset($_POST['bd_backup_ativo']) ? $_POST['bd_backup_ativo'] : null;
$bd_ssh = isset($_POST['bd_ssh']) ? $_POST['bd_ssh'] : null;
$bd_recorrencia = isset($_POST['bd_recorrencia']) ? $_POST['bd_recorrencia'] : null;
$bd_container = isset($_POST['bd_container']) ? $_POST['bd_container'] : null;

// Validando se o ID do banco de dados foi fornecido
if (!$bd_id) {
	echo "ID do banco de dados não foi fornecido";
	exit();
}

// Preparando e executando a consulta de atualização
$update = mysqli_query($conexao, "UPDATE db_management SET 
    bd_nome_usuario     = '$bd_nome_usuario',
    bd_login            = '$bd_login',
    bd_senha            = '$bd_senha',
    bd_ip               = '$bd_ip',
    bd_porta            = '$bd_porta',
    bd_tipo             = '$bd_tipo',
    bd_dia_0            = '$bd_dia0',
    bd_dia_1            = '$bd_dia1',
    bd_dia_2            = '$bd_dia2',
    bd_dia_3            = '$bd_dia3',
    bd_dia_4            = '$bd_dia4',
    bd_dia_5            = '$bd_dia5',
    bd_dia_6            = '$bd_dia6',
    bd_hora_backup      = '$bd_hora_backup',
    bd_servidor_backup  = '$bd_servidor_backup',
    bd_app              = '$bd_app',
    bd_data_alteracao   = NOW(),
    bd_usuario_adm      = '$bd_usuario_adm',
    bd_backup_ativo     = '$bd_backup_ativo',
    bd_ssh              = '$bd_ssh',
    bd_recorrencia      = '$bd_recorrencia',
    bd_container        = '$bd_container'
    WHERE bd_id = '$bd_id'");
 
// Verificando se a consulta foi executada com sucesso
if ($update) {
	echo "Cadastro alterado com sucesso";
} else {
	echo "Erro ao atualizar o cadastro: " . mysqli_error($conexao);
}

// Fechando a conexão com o banco de dados
mysqli_close($conexao);
?>