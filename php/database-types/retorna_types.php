<?php
include('../conexao/conexao.php');

// Recebendo dados de entrada
$tipo_id = $_POST['tipo_id'] ?? null;

// Verificando se o ID do sistema operacional foi fornecido
if (empty($tipo_id)) {
    die(json_encode(["error" => "ID do sistema operacional é obrigatório."]));
}

// Função para escapar dados e evitar SQL Injection
function escape($conexao, $dados) {
    return mysqli_real_escape_string($conexao, trim($dados));
}

// Escapando o ID do sistema operacional
$tipo_id = escape($conexao, $tipo_id);

// Consultando no banco de dados
$query = "SELECT tipo_nome,tipo_plataforma 
          FROM tipo 
          WHERE tipo_id = ?";
$stmt = $conexao->prepare($query);
$stmt->bind_param("i", $tipo_id);
$stmt->execute();
$result = $stmt->get_result();
$sos = $result->fetch_assoc();

if ($sos) {
    echo json_encode($sos);
} else {
    echo json_encode(["error" => "Sistema operacional não encontrado."]);
}

$stmt->close();
$conexao->close();
?>
