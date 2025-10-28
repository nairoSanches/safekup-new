<?php

// Incluindo arquivo de conexao com o banco de dados

include('../conexao/conexao.php');
require_once ('../include/encryption.inc.php');

$encryption = new Encryption();

// Recebendo os dados e variavel array

$bd_id = $_POST['bd_id'];
$json = array();

// Consultando no banco de dados

$sql = mysqli_query($conexao,"SELECT * FROM db_management A WHERE A.bd_id = '$bd_id' ORDER BY bd_nome_usuario");
$retorna_bd = mysqli_fetch_array($sql);

// Retornando os dados

$json['bd_nome_usuario']        = $retorna_bd['bd_nome_usuario'];
$json['bd_login']               = $retorna_bd['bd_login'];
$json['bd_senha'] 			    =  $encryption->decrypt($retorna_bd['bd_senha']);
$json['bd_ip']				    =  $retorna_bd['bd_ip'];
$json['bd_porta']				= $retorna_bd['bd_porta'];
$json['bd_tipo']                = $retorna_bd['bd_tipo'];
$json['dia0']                   = $retorna_bd['bd_dia_0'];
$json['dia1']                   = $retorna_bd['bd_dia_1'];
$json['dia2']                   = $retorna_bd['bd_dia_2'];
$json['dia3']                   = $retorna_bd['bd_dia_3'];
$json['dia4']                   = $retorna_bd['bd_dia_4'];
$json['dia5']                   = $retorna_bd['bd_dia_5'];
$json['dia6']                   = $retorna_bd['bd_dia_6'];
$json['bd_hora_backup']         = $retorna_bd['bd_hora_backup'];
$json['bd_servidor_backup']     = $retorna_bd['bd_servidor_backup'];
$json['bd_app']	                = $retorna_bd['bd_app'];
$json['bd_usuario_adm']	        = $retorna_bd['bd_usuario_adm'];
$json['bd_backup_ativo']        = $retorna_bd['bd_backup_ativo'];
$json['bd_ssh']                 = $retorna_bd['bd_ssh'];
$json['bd_recorrencia']         = $retorna_bd['bd_recorrencia'];
$json['bd_container']           = $retorna_bd['bd_container'];
$json['bd_id_restore']          = $retorna_bd['bd_id_restore'];

echo json_encode($json);

?>
