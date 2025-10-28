<?php
require_once("../painel/painel.php");
require_once("../conexao/conexao.php");


if (isset($_GET['restore_id'])) {

    $restore_id = mysqli_escape_string($conexao, $_GET['restore_id']);

    $sql = mysqli_query($conexao, "SELECT * FROM restores WHERE restore_id = '$restore_id' ");
    $dados = mysqli_fetch_array($sql);

}


?>

<div class="container">
    <h3 class="header smaller lighter blue">Alterar de Servidor de RESTORE</h3>
    <input type="text" hidden="true" id="restore_id" value="<?php echo $dados['restore_id'] ?>">
    <div class="col-xs-12 col-sm-8" style="margin-left:180px;">
        <div class="widget-box">
            <div class="widget-header">
                <h4 class="widget-title">Dados do Servidor</h4>
                <span style="margin-left:72%" class="help-button" data-rel="popover" data-trigger="hover"
                    data-placement="bottom" title="Servidor onde serão salvos os backups">?</span>
            </div>
            <div class="widget-body">
                <div class="widget-main">
                    <div class="row">
                        <div class="col-sm-6">
                            <label for="form-field-select-3" data-rel="popover" data-trigger="hover"
                                data-placement="bottom" title="Dê um nome para a identificação deste servidor">Nome do
                                Servidor</label>
                            <br>
                            <input type="text" id="restore_nome" value="<?php echo $dados['restore_nome'] ?>"
                                class="form-control" />
                        </div>
                        <div class="col-sm-6">
                            <label for="form-field-select-3" data-rel="popover" data-trigger="hover"
                                data-placement="bottom" title="Endereço IP do servidor">Ip do Servidor</label>
                            <br>
                            <input type="text" id="restore_ip" value="<?php echo $dados['restore_ip'] ?>"
                                class="form-control" />
                        </div>
                    </div>

                    <hr>
                    <div class="row">
                        <div>
                            
                            <div class="col-sm-6">
                                <label for="form-field-select-3" data-rel="popover" data-trigger="hover"
                                    data-placement="bottom" title="Compartilhamento onde será salvo os backups">Usuário Banco</label>
                                <br>
                                <input type="text" id="restore_user"
                                    value="<?php echo $dados['restore_user'] ?>"
                                    class="form-control" />
                            </div>
                            <div class="col-sm-6">
                                <label for="form-field-select-3" data-rel="popover" data-trigger="hover"
                                    data-placement="bottom"
                                    title="Senha do usuário">Senha</label>
                                <br>
                                <input type="password" id="restore_senha_acesso"
                                    value="<?php echo base64_decode($dados['restore_senha_acesso']) ?>"
                                    class="form-control" />
                            </div>
                        </div>
                    </div>
                    <hr>
                </div>
                <center>
                    <button type="button" class="btn btn-primary" onclick="alterar_restore()"
                        id="alt_servidor">ALTERAR</button>
                    <a type="button" id="cancelar" class="btn btn-default" href="restore.php"> VOLTAR </a>
                </center>
                <br>
            </div>
        </div>
    </div>
    <script src="../../assets/js/funcoes_alteracoes.js"></script>
    <script src="../../assets/js/funcoes_retorno.js"></script>

    </body>

    </html>