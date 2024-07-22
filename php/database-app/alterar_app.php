<?php
require_once("../painel/painel.php");
require_once("../conexao/conexao.php");


if(isset($_GET['app_id'])){

  $app_id = mysqli_escape_string($conexao, $_GET['app_id']);
  $sql = mysqli_query($conexao,"SELECT * FROM aplicacao WHERE app_id = '$app_id' ");
  $dados = mysqli_fetch_array($sql);

}
?>

    <div class="page-header">
        <h1>Alterar Setor</h1>
    </div>
    <div class="container">
<input type="text" hidden="true" id="app_id" value="<?php echo $dados['app_id']?>">
        <div class="col-xs-12 col-sm-8" style="margin-left:180px;">
            <div class="widget-box">
                <div class="widget-header">
                    <h4 class="widget-title">Dados da Aplicação</h4>
                    <span style="margin-left:75%" class="help-button" data-rel="popover" data-trigger="hover" data-placement="bottom" title="Aplicações dos Bancos">?</span>
                </div>
                <div class="widget-body">
                    <div class="widget-main">
                        <div class="row">
                            <div class="col-sm-6">
                                <label for="form-field-select-3">Nome da Aplicação</label>
                                <br>
                                <input type="text" id="app_nome" class="form-control" placeholder="Não deve conter espaços !!" value="<?php echo $dados['app_nome']?>">
                            </div>
                        </div>
                        <hr>
                        <div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <label for="form-field-select-3">Descrição</label>
                                    <br>
                                    <textarea  class="form-control" rows="5" id="descricao_app" value="<?php echo $dados['app_descricao']?>"><?php echo $dados['app_descricao']?></textarea>
                                </div>
                            </div>
                            <hr>
                        </div>
                    </div>
                    <center>
                        <button type="button" class="btn btn-primary" onclick="alterar_app()" id="cad_app">ALTERAR</button>
                        <a type="button" id="cancelar" class="btn btn-default" href="setores.php"> VOLTAR </a>
                    </center>
                    <br>
                </div>
            </div>
        </div>
