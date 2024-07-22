<?php

include('../conexao/conexao.php');

$usuario_id = $_POST['usuario_id'];
$json = array();

$sql = mysqli_query($conexao,"SELECT usuario_nome,usuario_login,usuario_status,usuario_id_app,usuario_email FROM ssh WHERE usuario_id = $usuario_id");
$ssh = mysqli_fetch_array($sql);




$json['usuario_nome'] = $ssh['usuario_nome'];
$json['usuario_login']        = $ssh['usuario_login'];
$json['usuario_status']       = $ssh['usuario_status'];
$json['usuario_id_app'] = $ssh['usuario_id_app'];
$json['usuario_email']    = $ssh['usuario_email'];
echo json_encode($json);



?>