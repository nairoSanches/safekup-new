<?php
// Iniciando a sessão
session_start();

// Verificando se houve POST
if (!isset($_POST['smtp_nome'], $_POST['smtp_email_admin'], $_POST['smtp_endereco'], $_POST['smtp_porta'], $_POST['smtp_senha'])) {
  die("false");
}

// Recuperando usuário logado - se necessário
// $usuario = $_SESSION['login'];

// Recebendo os dados digitados pelo usuário
$smtp_nome = $_POST['smtp_nome'];
$smtp_email_admin = $_POST['smtp_email_admin'];
$smtp_endereco = $_POST['smtp_endereco'];
$smtp_porta = $_POST['smtp_porta'];
$smtp_senha = base64_encode($_POST['smtp_senha']); // Usando base64_encode para codificar a senha

// Incluindo arquivo de conexão com o banco de dados
require_once("../conexao/conexao_pdo.php");
$conexao = conectar();

try {
  // Iniciando as transações
  $conexao->beginTransaction();

  // Inserindo na tabela smtp
  $insert_smtp = $conexao->prepare("INSERT INTO smtp (smtp_nome, smtp_email_admin, smtp_endereco, smtp_porta, smtp_senha) VALUES (UCASE(:smtp_nome), :smtp_email_admin, :smtp_endereco, :smtp_porta, :smtp_senha)");
  $insert_smtp->bindValue(":smtp_nome", $smtp_nome);
  $insert_smtp->bindValue(":smtp_email_admin", $smtp_email_admin);
  $insert_smtp->bindValue(":smtp_endereco", $smtp_endereco);
  $insert_smtp->bindValue(":smtp_porta", $smtp_porta);
  $insert_smtp->bindValue(":smtp_senha", $smtp_senha);

  if ($insert_smtp->execute()) {
    $conexao->commit();
    echo "true";
  } else {
    $conexao->rollBack(); // Em caso de falha, reverta as alterações
    die("false");
  }
} catch (PDOException $e) {
  // Em caso de exceção, reverta as alterações e imprima o erro
  $conexao->rollBack();
  echo "Erro ao inserir dados: " . $e->getMessage();
}
?>
