<?php
require_once("../painel/painel.php");
require_once("../conexao/conexao.php");

if(isset($_GET['ssh_id'])){

  $ssh_id = mysqli_escape_string($conexao, $_GET['ssh_id']);
  $sql = mysqli_query($conexao,"SELECT * FROM ssh A  WHERE ssh_id = '$ssh_id' ");
  $dados = mysqli_fetch_array($sql);

}
?>

    <div class="page-header">
        <h1>Alterar SSH</h1>
    </div>
    <div class="container">
      <input type="text" hidden="true" id="ssh_id" value="<?php echo $dados['ssh_id']?>">
        <div class="col-xs-12 col-sm-8" style="margin-left:180px;">
            <div class="widget-box">
                <div class="widget-header">
                    <h4 class="widget-title">Dados do SSH</h4>
                    <span style="margin-left:73%" class="help-button" data-rel="popover" data-trigger="hover" data-placement="bottom" title="Cadastro SSH">?</span>
                </div>
                <div class="widget-body">
                    <div class="widget-main">
                      <div class="row">
                        <div class="col-sm-12">
                            <label for="form-field-select-3">Nome</label>
                            <br>
                            <input type="text" id="ssh_ip" class="form-control" value="<?php echo $dados['ssh_ip']?>"/>
                        </div>
                      </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <label for="form-field-select-3">Login</label>
                                <br>
                                <input type="text" id="ssh_user" disabled="true" class="form-control" value="<?php echo $dados['ssh_user']?>" />
                            </div>
                           
                        </div>
                        <hr>
                        <div>
                          <div class="row">
                                <div>                                    
                                    <div class="col-sm-6">
                                        <label for="form-field-select-3">Status</label>
                                        <br>
                                        <select id="ssh_status" class="chosen-select form-control" data-placeholder="Selecione">
                                          <option  value="<?php echo $dados['ssh_status']?>"><?php echo $dados['ssh_status']?></option>
                                          <?php
                                          if($dados['ssh_status'] == "ATIVO"){
                                            echo "<option value='BLOQUEADO'>BLOQUEADO</option>";
                                          } else{
                                            echo "<option value='ATIVO'>ATIVO</option>";
                                          }
                                          ?>
                                      </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br><hr>
                    <center>
                        <button type="button" class="btn btn-primary" onclick="alterar_ssh()" id="alterar_ssh">ALTERAR</button>
                        <a type="button" id="cancelar" class="btn btn-default" href="ssh.php"> VOLTAR </a>
                    </center>
                    <br>
                </div>
            </div>
        </div>
