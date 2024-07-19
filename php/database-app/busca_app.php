<?php

include('../conexao/conexao.php');

$str = "<option value=''> SELECIONE </option>";

$sql = mysqli_query($conexao,"SELECT app_id,app_nome FROM aplicacao ORDER BY app_nome");

while($dados = mysqli_fetch_array($sql)){

$str.="<option value='$dados[app_id]'>$dados[app_nome]</option>";

}

echo $str;


?>