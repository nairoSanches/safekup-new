<?php
require_once("../painel/painel.php");
?>

    <div class="page-header">
        <h1>Cadastro de Tipo de Banco de Dados</h1>
    </div>
    <div class="container">

        <div class="col-xs-12 col-sm-8" style="margin-left:180px;">
            <div class="widget-box">
                <div class="widget-header">
                    <h4 class="widget-title">Dados do Tipo de Banco de Dados</h4>
                    <span style="margin-left:57%" class="help-button" data-rel="popover" data-trigger="hover" data-placement="bottom" title="Cadastre os Tipo de Banco de Dados existentes em sua rede de computadores">?</span>
                </div>
                <div class="widget-body">
                    <div class="widget-main">
                        <div class="row">
                            <div class="col-sm-6">
                                <label for="form-field-select-3">Nome do Tipo de Banco de Dados</label>
                                <br>
                                <input type="text" id="nome_so" class="form-control" />
                            </div>
                        </div>
                        <hr>
                        <div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <label for="form-field-select-3">Plataforma</label>
                                    <br>
                                    <select class="chosen-select form-control" id="plataforma">
                                        <option value=""> Selecione </option>
                                        <option value="LINUX"> LINUX </option>
                                        <option value="WINDOWS"> WINDOWS </option>
                                    </select>
                                </div>
                            </div>
                            <hr>
                        </div>
                    </div>
                    <center>
                        <button type="button" class="btn btn-primary" onclick="cadastrar_so()" id="cad_so">CADASTRAR</button>
                        <a type="button" id="cancelar" class="btn btn-default" href="cadastro_types.php"> VOLTAR </a>
                    </center>
                    <br>
                </div>
            </div>
        </div>
