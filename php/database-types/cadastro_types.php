<?php
require_once("../conexao/conexao_pdo.php");
require_once("../painel/painel.php");

$conexao = conectar();
$sql = $conexao->prepare("SELECT * FROM tipo ORDER BY tipo_nome");
$sql->execute();
?>
<div class="container">
<div class="row">
  <div class="col-xs-12">
    <h3 class="header smaller lighter blue">Tipo de Banco de Dados</h3>
    <div class="clearfix">
      <div class="pull-right tableTools-container"> <a href="cad_types.php"><button type="button" id="cadastrar"  class="btn btn-primary btn-sm" onclick="abrirModalSo()">Adicionar</button></a></div>
    </div>
    <div class="table-header"></div>
    <div>
      <table id="table" class="table table-striped table-bordered table-hover">
        <thead>
          <tr>
            <th class="hidden-480">ID</th>
            <th class="hidden-480">Tipo</th>
            <th class="hidden-480">Sistema Operacional</th>
            <th class="hidden-480">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php while($dados = $sql->fetch(PDO:: FETCH_ASSOC)) {?>
          <tr>
            <td><?php echo $dados["tipo_id"]?></td>
            <td><?php echo $dados["tipo_nome"]?></td>
            <td><?php echo $dados["tipo_plataforma"]?></td>
            <td class="checkbox-inline">
              <a href="alterar_types.php?tipo_id=<?php echo $dados['tipo_id']?>"><button class="btn btn-xs btn-success"><i class="ace-icon fa fa-pencil bigger-120"></i></button></i></a>
            <a id="tipo_id" onclick="excluir_so('<?php echo $dados['tipo_id']?>')"><button class="btn btn-xs btn-danger"><i class="ace-icon fa fa-trash-o bigger-120"></i></button></a>            </td>
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
    search:"",
    searchPlaceholder: "Pesquisar",
    "lengthMenu": "Mostrando _MENU_ registros por página",
    "zeroRecords": "Nada encontrado",
    "info": "Mostrando página _PAGE_ de _PAGES_",
    "infoEmpty": "Nenhum registro disponível",
    "infoFiltered": "(filtrado de _MAX_ registros no total)"
  }
});

function abrirModalSo(){
  $('#modal').modal('show');
}
</script>
</body>
</html>
