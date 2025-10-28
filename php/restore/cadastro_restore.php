<?php
require_once("../painel/painel.php");
?>


<div class="container">
    <h3 class="header smaller lighter blue">Cadastro de Servidor de Restore</h3>

    <div class="col-xs-12 col-sm-8" style="margin-left:180px;">
        <div class="widget-box">
            <div class="widget-header">
                <h4 class="widget-title">Dados do Servidor de Restore</h4>
                <span style="margin-left:90%" class="help-button" data-rel="popover" data-trigger="hover"
                    data-placement="bottom" title="Servidor Restore">?</span>
            </div>
            <div class="widget-body">
                <div class="widget-main">
                    <div class="row">
                        <div class="col-sm-6">
                            <label for="form-field-select-3" data-rel="popover" data-trigger="hover"
                                data-placement="bottom" title="Dê um nome para a identificação deste servidor">Nome do
                                Servidor</label>
                            <br>
                            <input type="text" id="restore_nome" class="form-control">

                        </div>
                        <div class="col-sm-6">
                            <label for="form-field-select-3" data-rel="popover" data-trigger="hover"
                                data-placement="bottom" title="Endereço IP do servidor">Ip do Servidor</label>
                            <br>
                            <input type="text" id="restore_ip" class="form-control" />
                        </div>
                    </div>
                    <div>
                        <hr>
                    </div>
                </div>

            </div>

        </div>
        <br>
        <center>
            <button type="button" class="btn btn-primary" onclick="cadastrar_restore()"
                id="cad_servidor">CADASTRAR</button>
            <a type="button" id="cancelar" class="btn btn-default" href="restore.php"> VOLTAR </a>
        </center>
        <br>
    </div>