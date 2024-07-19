<?php

include('../conexao/conexao.php');

$app_id = $_POST['app_id'];
$json = array();

$sql = mysqli_query($conexao,"SELECT * FROM aplicacao WHERE app_id = $app_id ");
$app = mysqli_fetch_array($sql);

$json['app_nome']      = $app['app_nome'];
$json['app_descricao'] = $app['app_descricao'];

echo json_encode($json);





?>