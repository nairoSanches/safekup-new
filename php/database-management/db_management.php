<?php
require_once("../conexao/conexao_pdo.php");
require_once("../painel/painel.php");

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

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Servidor SQL</title>

</head>

<body>

  <div class="container">
    <div class="row">
      <div class="col-xs-12">
        <h3 class="header smaller lighter blue">Servidor SQL</h3>
        <div class="clearfix">
          <div class="pull-right tableTools-container">
            <a href="cadastro_bd.php">
              <button type="button" id="cadastrar" class="btn btn-primary btn-sm">Adicionar</button>
            </a>
          </div>
        </div>
        <div class="table-header"></div>
        <div>
          <table id="table" class="table table-striped table-bordered table-hover">
            <thead>
              <tr>
                <th class="hidden-480">Nome do banco de dados</th>
                <th class="hidden-480">SGBD</th>
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
                    <?php if ($dados["bd_container"]) { ?>
                      <img src="../../assets/images/docker_pen.png"
                        style="float: right; margin-left: 5px; max-width: 30px;">
                    <?php } ?>
                  </td>
                  <td><?php echo $dados["tipo_nome"] ?></td>
                  <td><?php echo $dados["bd_hora_backup"] ?>:00&nbsp;Hs</td>
                  <td><?php echo $dados["app_nome"] ?></td>
                  <td><?php echo $dados["bd_ip"] ?></td>
                  <td><?php echo date('d/m/Y H:i:s', strtotime($dados["bd_data_cadastro"])) ?></td>
                  <td><?php echo $dados["bd_backup_ativo"] ?></td>
                  <td class="checkbox-inline" style="white-space: nowrap;">
                    <!-- Botão de editar -->
                    <a href="altera_bd.php?bd_id=<?php echo $dados['bd_id'] ?>" style="margin-right: 5px;"
                      title="Editar Banco de Dados">
                      <button class="btn btn-sm btn-success">
                        <i class="ace-icon fa fa-pencil bigger-120"></i> Editar
                      </button>
                    </a>

                    <!-- Botão de excluir -->
                    <a id="bd_id" onclick="excluir_computador('<?php echo $dados['bd_id'] ?>')" style="margin-right: 5px;"
                      title="Excluir Banco de Dados">
                      <button class="btn btn-sm btn-danger">
                        <i class="ace-icon fa fa-trash-o bigger-120"></i> Excluir
                      </button>
                    </a>

                    <!-- Botão de restaurar com modal -->
                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal"
                      data-bs-target="#restoreModal" data-bd-id="<?php echo $dados['bd_id'] ?>"
                      title="Restaurar Banco de Dados">
                      <i class="ace-icon fa fa-database bigger-120"></i> Restaurar
                    </button>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="modal" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="restoreModalLabel">Script de Restore</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <pre id="scriptContent" style="background-color: #f8f9fa; padding: 10px; border-radius: 5px;"></pre>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" onclick="copyToClipboard()">Copiar</button>
          <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fechar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget; // Botão que acionou o modal
      const bdId = button.getAttribute('data-bd-id'); // Obtém o bd_id do atributo
      const scriptContent = document.getElementById('scriptContent');

      // Faz a requisição para o arquivo PHP
      fetch('../restore/gerar_script.php?bd_id=' + bdId)
        .then(response => response.text())
        .then(data => {
          scriptContent.textContent = data; // Preenche o conteúdo do modal
        })
        .catch(error => {
          console.error('Erro ao carregar o script:', error);
          scriptContent.textContent = 'Erro ao carregar o script.';
        });
    });

    function copyToClipboard() {
  const scriptContent = document.getElementById('scriptContent').textContent;

  // Verificar se o navegador suporta navigator.clipboard
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(scriptContent)
      .then(() => {
        alert('Script copiado para a área de transferência!');
      })
      .catch(err => {
        console.error('Erro ao copiar o script:', err);
        alert('Falha ao copiar o script. Tente novamente.');
      });
  } else {
    // Método alternativo para navegadores antigos
    const textarea = document.createElement('textarea');
    textarea.value = scriptContent;
    document.body.appendChild(textarea);
    textarea.select();
    try {
      document.execCommand('copy');
      alert('Script copiado para a área de transferência!');
    } catch (err) {
      console.error('Erro ao copiar o script:', err);
      alert('Falha ao copiar o script. Tente novamente.');
    } finally {
      document.body.removeChild(textarea);
    }
  }
}


  </script>

</body>

</html>