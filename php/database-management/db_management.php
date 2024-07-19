<?php
require_once ("../conexao/conexao_pdo.php");
require_once ("../painel/painel.php");

$conexao = conectar();

$query = "
    SELECT * 
    FROM db_management A
    JOIN tipo B ON A.bd_tipo = B.tipo_id
    JOIN aplicacao C ON A.bd_app = C.app_id
    ORDER BY bd_nome_usuario
";

$stmt = $conexao->prepare($query);
$stmt->execute();

?>
<div class="container">
  <div class="row">
    <div class="col-xs-12">
      <h3 class="header smaller lighter blue">Servidor SQL</h3>
      <div class="clearfix">
        <div class="pull-right tableTools-container"><a href="cadastro_bd.php"><button type="button" id="cadastrar"
              class="btn btn-primary btn-sm">Adicionar</button></a></div>
      </div>
      <div class="table-header"></div>
      <div>
        <table id="table" class="table table-striped table-bordered table-hover">
          <thead>
            <tr>
              <th class="hidden-480">Nome do banco de dados</th>
              <th class="hidden-480">Tipo de Banco de Dados</th>
              <th class="hidden-480">Horário</th>
              <th class="hidden-480">Aplicação</th>
              <th class="hidden-480">IP</th>
              <th class="hidden-480">Data de Cadastro</th>
              <th class="hidden-480">Ativo</th>
              <th class="hidden-480">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($dados = $stmt->fetch(PDO::FETCH_ASSOC)) { ?>
              <tr>
                <td>
                  <span><?php echo $dados["bd_nome_usuario"] ?></span>
                  <?php if($dados["bd_container"]){ ?> 
                  <img src="../../assets/images/docker_pen.png"
                    style="float: right; margin-left: 5px; max-width: 30px;">
                  <?php } ?>  
                </td>
                <td><?php echo $dados["tipo_nome"] ?></td>
                <td><?php echo $dados["bd_hora_backup"] ?>:00 Hs</td>
                <td><?php echo $dados["app_nome"] ?></td>
                <td><?php echo $dados["bd_ip"] ?></td>
                <td><?php echo date('d/m/Y H:i:s', strtotime($dados["bd_data_cadastro"])) ?></td>
                <td><?php echo $dados["bd_backup_ativo"] ?></td>
                <td>
                  <a href="altera_bd.php?bd_id=<?php echo $dados['bd_id'] ?>"><button class="btn btn-xs btn-success"><i
                        class="ace-icon fa fa-pencil bigger-120"></i></button></i></a>
                  <a id="bd_id" onclick="excluir_computador('<?php echo $dados['bd_id'] ?>')"><button
                      class="btn btn-xs btn-danger"><i class="ace-icon fa fa-trash-o bigger-120"></i></button></a>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript">
  $('#table').DataTable({
    "lengthMenu": [[5], [5]],
    "language": {
      "paginate": {
        "previous": "Anterior",
        "next": "Próximo"
      },
      search: "",
      searchPlaceholder: "Pesquisar",
      "lengthMenu": "Mostrando _MENU_ registros por página",
      "zeroRecords": "Nada encontrado",
      "info": "Mostrando página _PAGE_ de _PAGES_",
      "infoEmpty": "Nenhum registro disponível",
      "infoFiltered": "(filtrado de _MAX_ registros no total)"
    }
  });

  function abrirModalSo() {
    $('#modal').modal('show');
  }
</script>
</body>

</html>