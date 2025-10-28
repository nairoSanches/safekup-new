<?php

require_once("../conexao/conexao_pdo.php");

$conexao = conectar();

// Consulta SQL para obter os dados do histórico de dumps ordenados pela data de execução mais recente
$sql = "SELECT 
        H.id, 
        H.bd_nome_usuario, 
        H.bd_ip,   
        H.data_execucao, 
        H.status, 
        H.arquivo_backup, 
        D.bd_tipo,
        D.bd_container,
        H.descricao,
        H.tamanho_arquivo,
        H.tempo_decorrido
    FROM 
        historico_dumps H
    JOIN 
        db_management D ON H.bd_id = D.bd_id
    ORDER BY 
        H.id DESC";

$resultado = $conexao->query($sql);

if (!$resultado) {
  die("Erro na consulta SQL: " . $conexao->errorInfo()[2]);
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Relatório de Histórico de Dumps</title>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap.min.css">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
</head>

<body>
  <?php require_once("../painel/painel.php"); ?>
  <div class="container">
    <div class="row">
      <div class="col-xs-12">
        <h3 class="header smaller lighter blue">Relatório de Histórico de Dumps</h3>
        <div class="table-header"></div>
        <div>
          <table id="table" class="table table-striped table-bordered table-hover">
            <thead>
              <tr>
                <th>Estrutura</th>
                <th>Nome do Banco de Dados</th>
                <th>IP</th>
                <th>Data de Execução</th>
                <th>Status</th>
                <th>Tempo</th>
                <th>Tamanho do arquivo</th>
              </tr>
            </thead>
          </table>

        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap.min.js"></script>
  <script>

    $.fn.dataTable.ext.type.order['date-dd-mmm-yyyy-pre'] = function (d) {
      var parts = d.split('/');
      return new Date(parts[2], parts[1] - 1, parts[0]).getTime();
    };

    $(document).ready(function () {
    $('#table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "../../get-data/getDumps.php", 
            "type": "POST"
        },
        "lengthMenu": [[15], [15]],
        "language": {
            "paginate": { "previous": "Anterior", "next": "Próximo" },
            "search": "Pesquisar:",
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "Nenhum registro encontrado",
            "info": "Mostrando página _PAGE_ de _PAGES_",
            "infoEmpty": "Nenhum registro disponível",
            "infoFiltered": "(filtrado de _MAX_ registros no total)"
        },
        "columns": [
            { "data": "estrutura" },         
            { "data": "bd_nome_usuario" },   
            { "data": "bd_ip" },             
            { "data": "data_execucao" },     
            { "data": "status" },            
            { "data": "tempo_decorrido" },   
            { "data": "tamanho_arquivo" }    
        ],
        "order": [[3, "desc"]]
    });
});


  </script>
</body>

</html>