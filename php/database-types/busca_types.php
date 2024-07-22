<?php

include('../conexao/conexao.php');

$str = "<option value=''> SELECIONE </option>";

$sql = mysqli_query($conexao,"SELECT * FROM tipo ORDER BY tipo_nome");
  
  while($tipo = mysqli_fetch_array($sql)){

  $str.="<option value='$tipo[tipo_id]'>$tipo[tipo_nome]</option>";	

  }

  echo $str;
  
?>