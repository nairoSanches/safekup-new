<?php
require_once("../conexao/conexao_pdo.php");
require_once("../painel/painel.php");

$conexao = conectar();
$sql = $conexao->prepare("SELECT * FROM ssh ORDER BY ssh_id");
$sql->execute();
?>
<div class="container">
<div class="row">
  <div class="col-xs-12">
    <h3 class="header smaller lighter blue">SSH</h3>
    <div class="clearfix">
      <div class="pull-right tableTools-container"> <a href="cadastro_ssh.php"><button type="button" id="cadastrar"  class="btn btn-primary btn-sm">Adicionar</button></a></div>
    </div>
    <div class="table-header"></div>
    <div>
      <table id="table" class="table table-striped table-bordered table-hover">
        <thead>
          <tr>           
            <th class="hidden-480">Host/IP</th>
            <th class="hidden-480">User</th>
            <th class="hidden-480">Pass</th>
            <th class="hidden-480">Porta</th>
            <th class="hidden-480">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php while($dados = $sql->fetch(PDO:: FETCH_ASSOC)) {?>
          <tr>           
            <td><?= $dados['ssh_ip'] ?></td>
            <td><?= $dados['ssh_user'] ?></td>
            <td><?= '**********' ?></td>
            <td><?= 22 ?></td>
            <td>
              <center>
              <a href="alterar_ssh.php?ssh_id=<?php echo $dados['ssh_id']?>"><button class="btn btn-xs btn-success"><i class="ace-icon fa fa-pencil bigger-120"></i></button></i></a>
            </center>
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
