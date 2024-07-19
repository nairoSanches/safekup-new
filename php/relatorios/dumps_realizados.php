<?php

require_once ("../conexao/conexao_pdo.php");

$conexao = conectar();

// Consulta SQL para obter os dados do histórico de dumps ordenados pela data de execução mais recente
$sql = "SELECT 
        H.id, 
        H.bd_nome_usuario, 
        H.bd_ip, 
        H.bd_id, 
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
        H.data_execucao DESC";

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
  <?php
  require_once ("../painel/painel.php");
  ?>
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
            <tbody>
              <?php while ($row = $resultado->fetch(PDO::FETCH_ASSOC)) { ?>
                <tr>
                  <td>
                    <?php if ($row["bd_container"]) { ?>
                      <img src="../../assets/images/docker_pen.png" title="Docker Container"
                        style="float: left; margin-right: 5px; max-width: 30px;">
                    <?php } else { ?>
                      <img src="../../assets/images/vm.svg" title="Virtual Machine"
                        style="float: left; margin-right: 5px; max-width: 30px;">
                    <?php } ?>
                    <?php if ($row["bd_tipo"] == 1) { ?>
                      <img src="../../assets/images/mysql.png" title="MySQL Database"
                        style="float: left; margin-right: 5px; max-width: 30px;">
                    <?php } ?>
                    <?php if ($row["bd_tipo"] == 2) { ?>
                      <img src="../../assets/images/pg.png" title="PostgreSQL Database"
                        style="float: left; margin-right: 5px; max-width: 30px;">
                    <?php } ?>
                  </td>

                  <td><?php echo htmlspecialchars($row['bd_nome_usuario']) ?></td>
                  <td><?php echo $row['bd_ip'] ?></td>
                  <td><?php echo htmlspecialchars(date('d/m/Y H:i:s', strtotime($row['data_execucao']))) ?></td>
                  <td><?php
                  $status = htmlspecialchars($row['status']);
                  if ($status === 'OK') {
                    echo '<i class="fa fa-check-circle text-success"></i> ';
                    echo $status;
                  } else {
                    echo '<i class="fa fa-times-circle text-danger"></i> ';
                    echo $status;
                    if ($row['descricao']) {
                      echo '<br><br><span>Chamado GLPI: <strong>' . $row['descricao'] . '</strong> </span>';
                    }
                  }
                  ?>

                  </td>
                  <td><?php echo htmlspecialchars($row['tempo_decorrido']) ?></td>
                  <td><?php if (isset($row['tamanho_arquivo'])) {

                    $tamanho_arquivo_mb = round($row['tamanho_arquivo'] / 1024 / 1024, 2);

                    echo htmlspecialchars(" {$tamanho_arquivo_mb} MB");
                  } ?></td>

                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap.min.js"></script>
  <script>
    $(document).ready(function () {
      $('#table').DataTable({
        "lengthMenu": [[15], [15]], // Define o menu de comprimento para mostrar 15 registros por página
        "order": [[3, "desc"]], // Define a ordenação inicial pela quarta coluna (índice 3) em ordem decrescente
        "language": { // Configurações de idioma para o DataTable
          "paginate": {
            "previous": "Anterior",
            "next": "Próximo"
          },
          "search": "Pesquisar:",
          "lengthMenu": "Mostrar _MENU_ registros por página",
          "zeroRecords": "Nenhum registro encontrado",
          "info": "Mostrando página _PAGE_ de _PAGES_",
          "infoEmpty": "Nenhum registro disponível",
          "infoFiltered": "(filtrado de _MAX_ registros no total)"
        },
        "columnDefs": [
          { "className": "dt-center", "targets": "_all" } // Centraliza a sexta coluna (índice 5)
        ]
      });
    });

  </script>
</body>

</html>