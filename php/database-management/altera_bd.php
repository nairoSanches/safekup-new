<?php
require_once("../painel/painel.php");
require_once("../conexao/conexao.php");


if (isset($_GET['bd_id'])) {

  $bd_id = mysqli_escape_string($conexao, $_GET['bd_id']);

  ?>
  <script>
    retorna_computador(<?php echo $bd_id ?>);

  </script>
<?php }

?>

<div class="container">
  <div class="page-header">
    <h1>Alterar Cadastro de Banco de Dados</h1>
  </div>
  <div class="col-xs-12 col-sm-8" style="margin-left:210px;">
    <div class="widget-box">
      <div class="widget-header">
        <h4 class="widget-title">Dados</h4>
        <span style="margin-left:72%" class="help-button" data-rel="popover" data-trigger="hover"
          data-placement="bottom" title="OBS: Cadastro do banco de  dados onde será realizado o Dump.">?</span>
      </div>
      <input type="text" hidden="true" id="bd_id" value="<?php echo $bd_id ?>">

      <div class="widget-body">
        <div class="widget-main">
          <div class="row">
            <div class="col-sm-12">
              <label for="form-field-select-3" data-rel="popover" data-trigger="hover" data-placement="bottom"
                title="Banco de Dados">Banco de Dados</label>
              <br>
              <input type="text" id="bd_nome_usuario" class="form-control" />
            </div>
          </div>
          <hr>
          <div class="row">
            <div class="col-sm-4">
              <label for="form-field-select-3" data-rel="popover" data-trigger="hover" data-placement="bottom"
                title="User do banco">Usuário</label>
              <br>
              <input type="text" id="bd_login" class="form-control" />
            </div>
            <div class="col-sm-4">
              <label for="form-field-select-3" data-rel="popover" data-trigger="hover" data-placement="bottom"
                title="Senha do banco">Senha</label>
              <br>
              <input type="password" id="bd_senha" class="form-control" />
            </div>
            <div class="col-sm-4">
              <label for="inputState" data-rel="popover" data-trigger="hover" data-placement="bottom"
                title="Tipo de Banco de Dados">SSH</label>
              <select id="bd_ssh" class="chosen-select form-control" data-placeholder="Selecione">
                <option value='0'>Não</option>
                <?php
                $sql = mysqli_query($conexao, "SELECT  ssh_id, ssh_ip FROM ssh A ");
                while ($ssh = mysqli_fetch_array($sql)) {
                  echo "<option value='$ssh[ssh_id]'>$ssh[ssh_ip]</option>";
                }
                ?>
              </select>
            </div>
          </div>
          <hr>
          <div class="row">
            <div class="col-sm-4">
              <label for="form-field-select-3" data-rel="popover" data-trigger="hover" data-placement="bottom"
                title="Host">Endereço IP</label>
              <br>
              <input type="text" id="bd_ip" class="form-control input-mask-ip" />
            </div>
            <div class="col-sm-4">
              <label for="form-field-select-3" data-rel="popover" data-trigger="hover" data-placement="bottom"
                title="Porta do Banco">Porta</label>
              <br>
              <input type="text" id="bd_porta" class="form-control" />
            </div>
            <div class="col-sm-4">
              <label for="inputState" data-rel="popover" data-trigger="hover" data-placement="bottom"
                title="Tipo de Banco de Dados">Tipo de Banco de Dados</label>
              <select id="tipo_id" class="chosen-select form-control" data-placeholder="Selecione">

                <?php
                $sql = mysqli_query($conexao, "SELECT  tipo_id, tipo_nome FROM tipo A ");
                while ($tipo = mysqli_fetch_array($sql)) {
                  echo "<option value='$tipo[tipo_id]'>$tipo[tipo_nome]</option>";
                }
                ?>
              </select>
            </div>
          </div>
          <hr>
          <center>
            <h4 data-rel="popover" data-trigger="hover" data-placement="bottom" title="Escolha o(s) dia(s) de backup">
              Dia do Backup</h4>
          </center>
          <br>
          <div class="col-sm-12" style="margin-left:10px;">
            <label onclick="marca_dias_semana();" class="checkbox-inline"> <input type="checkbox" id="todos_os_dias"
                class="ace checkbox"><span class="lbl"><span class="lbl"><span class="lbl"><span class="lbl"><span
                        class="lbl"> Todos os
                        dias</span></label>
            <label class="checkbox-inline"><input type="checkbox" id="dia0" class="ace checkbox"><span
                class="lbl">Domingo</span></label>
            <label class="checkbox-inline"><input type="checkbox" id="dia1" class="ace checkbox"><span
                class="lbl">Segunda</span></label>
            <label class="checkbox-inline"><input type="checkbox" id="dia2" class="ace checkbox"><span
                class="lbl">Terça</span></label>
            <label class="checkbox-inline"><input type="checkbox" id="dia3" class="ace checkbox"><span
                class="lbl">Quarta</span></label>
            <label class="checkbox-inline"><input type="checkbox" id="dia4" class="ace checkbox"><span
                class="lbl">Quinta</span></label>
            <label class="checkbox-inline"><input type="checkbox" id="dia5" class="ace checkbox"><span
                class="lbl">Sexta</span></label>
            <label class="checkbox-inline"><input type="checkbox" id="dia6" class="ace checkbox"><span
                class="lbl">Sábado</span> </label>
          </div>
          <hr>
          <hr>
          <div class="row">
            <div class="col-sm-4">
              <label for="form-field-select-3" data-rel="popover" data-trigger="hover" data-placement="bottom"
                title="Escolha o horário do backup automático">Horário Inicial</label>
              <br>
              <select class="chosen-select form-control" id="bd_hora_backup">
                <option value="">Selecione</option>
                <option value="0">00:00</option>
                <option value="01">01:00</option>
                <option value="02">02:00</option>
                <option value="03">03:00</option>
                <option value="04">04:00</option>
                <option value="05">05:00</option>
                <option value="06">06:00</option>
                <option value="07">07:00</option>
                <option value="08">08:00</option>
                <option value="09">09:00</option>
                <option value="10">10:00</option>
                <option value="11">11:00</option>
                <option value="12">12:00</option>
                <option value="13">13:00</option>
                <option value="14">14:00</option>
                <option value="15">15:00</option>
                <option value="16">16:00</option>
                <option value="17">17:00</option>
                <option value="18">18:00</option>
                <option value="19">19:00</option>
                <option value="20">20:00</option>
                <option value="21">21:00</option>
                <option value="22">22:00</option>
                <option value="23">23:00</option>
              </select>

            </div>

            <div class="col-sm-4">
              <label for="form-field-select-3" data-rel="popover" data-trigger="hover" data-placement="bottom"
                title="Horários de Recorrêcia">Recorrêcia diária (6 horas)</label>
              <br>
              <select class="chosen-select form-control" id="bd_recorrencia">
                <option value="1">1x</option>
                <option value="2">2x</option>
                <option value="3">3x</option>
                <option value="4">4x</option>
              </select>

            </div>
            <div class="col-sm-4">
              <label for="form-field-select-3" data-rel="popover" data-trigger="hover" data-placement="bottom"
                title="Senha do banco">ID Contêiner do Docker</label>
              <br>
              <input type="bd_container" id="bd_container" class="form-control" />
            </div>

          </div>
          <hr>
          <div class="row">
            <div class="col-sm-4">
              <label for="form-field-select-3">Dump Ativo ?</label>
              <br>
              <select class="chosen-select form-control" id="bd_backup_ativo">
                <option value="">Selecione</option>
                <option value="SIM">SIM</option>
                <option value="NÃO">NÃO</option>
              </select>
            </div>
            <div class="form-group col-md-4">
              <label data-rel="popover" data-trigger="hover" data-placement="bottom"
                title="Escolha para qual servidor o backup será enviado">Servidor de Backup</label>
              <select id="servidor_id" class="chosen-select form-control">
                <option value="">Selecione</option>
                <?php
                $sql = mysqli_query($conexao, "SELECT servidor_id,servidor_nome FROM servidores ORDER BY servidor_id");
                while ($servidor = mysqli_fetch_array($sql)) {
                  echo "<option value='$servidor[servidor_id]'>$servidor[servidor_nome]</option>";
                }
                ?>
              </select>
            </div>
            <div class="form-group col-md-4">
              <label for="inputState" data-rel="popover" data-trigger="hover" data-placement="bottom"
                title="Aplicação de requisição do Banco">Aplicação</label>
              <select id="bd_app" class="chosen-select form-control">
                <option value="">Selecione</option>
                <?php
                $sql = mysqli_query($conexao, "SELECT app_id,app_nome FROM aplicacao WHERE app_id != 1 ORDER BY app_nome");
                while ($setor = mysqli_fetch_array($sql)) {
                  echo "<option value='$setor[app_id]'>$setor[app_nome]</option>";
                }
                ?>
              </select>
            </div>
          </div>
          <hr>
        </div>
        <div class="row">
        <div class="form-group col-md-4">
              <label data-rel="popover" data-trigger="hover" data-placement="bottom"
                title="Escolha para qual servidor o backup será enviado">Servidor de Restore</label>
              <select id="restore_id" class="chosen-select form-control">
                <option value="">Selecione</option>
                <?php
                $sql = mysqli_query($conexao, "SELECT restore_id,restore_nome FROM restores ORDER BY restore_id");
                while ($servidor = mysqli_fetch_array($sql)) {
                  echo "<option value='$servidor[restore_id]'>$servidor[restore_nome]</option>";
                }
                ?>
              </select>
            </div>

          </div>
          <hr>
        <center>
          <button type="button" class="btn btn-primary" onclick="altera_computador()" id="alterar_comp">ALTERAR</button>
          <a type="button" id="cancelar" class="btn btn-default" href="db_management.php"> VOLTAR </a>
        </center>
        <br>
      </div>
    </div>
  </div>
</div>
</div>