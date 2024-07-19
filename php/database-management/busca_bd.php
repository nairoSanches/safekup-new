<?php

include('../conexao/conexao.php');

$str = "<option value=''> SELECIONE </option>";

$sql = mysqli_query($conexao,"SELECT bd_id,bd_nome_usuario FROM computadores ORDER BY bd_nome_usuario");

while($dados = mysqli_fetch_array($sql)){

$str.="<option value='$dados[bd_id]'>$dados[bd_nome_usuario]</option>";


}

echo $str;

?>