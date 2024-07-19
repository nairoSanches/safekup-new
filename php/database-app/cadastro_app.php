<?php
require_once("../painel/painel.php");
?>

    <div class="page-header">
        <h1>Cadastro de Aplicações</h1>
    </div>
    <div class="container">

        <div class="col-xs-12 col-sm-8" style="margin-left:180px;">
            <div class="widget-box">
                <div class="widget-header">
                    <h4 class="widget-title">Dados da Aplicação</h4>
                    <span style="margin-left:75%" class="help-button" data-rel="popover" data-trigger="hover" data-placement="bottom" title="O sistema irá organizar os backups no Servidor separando por setor">?</span>
                </div>
                <div class="widget-body">
                    <div class="widget-main">
                        <div class="row">
                            <div class="col-sm-6">
                                <label for="form-field-select-3">Nome da Aplicação</label>
                                <br>
                                <input type="text" id="app_nome" class="form-control" placeholder="Não deve conter espaços !!">
                            </div>
                        </div>
                        <hr>
                        <div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <label for="form-field-select-3">Descrição</label>
                                    <br>
                                    <textarea  class="form-control" rows="5" id="descricao_app"></textarea>
                                </div>
                            </div>
                            <hr>
                        </div>
                    </div>
                    <center>
                        <button type="button" class="btn btn-primary" onclick="cadastrar_app()" id="cad_app">CADASTRAR</button>
                        <a type="button" id="cancelar" class="btn btn-default" href="setores.php"> VOLTAR </a>
                    </center>
                    <br>
                </div>
            </div>
        </div>
