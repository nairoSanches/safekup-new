<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '/opt/lampp/htdocs/safekup/php/conexao/conexao.php';

$draw = intval($_POST['draw'] ?? 0);
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 15);
$search = $_POST['search']['value'] ?? '';
$orderColumnIndex = $_POST['order'][0]['column'] ?? 3;
$orderDir = $_POST['order'][0]['dir'] ?? 'DESC';

$columns = ['H.id', 'H.bd_nome_usuario', 'H.bd_ip', 'H.data_execucao', 'H.status', 'H.tempo_decorrido', 'H.tamanho_arquivo'];
$orderColumn = $columns[$orderColumnIndex] ?? 'H.data_execucao';

$sqlBase = "FROM historico_dumps H 
            JOIN db_management D ON H.bd_id = D.bd_id 
            WHERE 1";

if (!empty($search)) {
    $sqlBase .= " AND (H.bd_nome_usuario LIKE '%$search%' OR H.bd_ip LIKE '%$search%' OR H.status LIKE '%$search%' OR D.bd_tipo LIKE '%$search%')";
}

$totalRecords = $conexao->query("SELECT COUNT(*) as total FROM historico_dumps")->fetch_assoc()['total'];
$totalFiltered = $conexao->query("SELECT COUNT(*) as total $sqlBase")->fetch_assoc()['total'];

$sql = "SELECT H.id, H.bd_nome_usuario, H.bd_ip, H.data_execucao, H.status, H.tempo_decorrido, H.tamanho_arquivo, H.descricao, D.bd_tipo $sqlBase ORDER BY $orderColumn $orderDir LIMIT $start, $length";
$result = $conexao->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $circleStyle = 'style="width:50px;height:50px;border-radius:65%;object-fit:cover;margin-right:8px;float:left;"';
    $estrutura = match ($row['bd_tipo']) {
        '1' => '<img src="../../assets/images/mysql.png" title="MySQL" ' . $circleStyle . '>',
        '2' => '<img src="../../assets/images/pg.png" title="PostgreSQL" ' . $circleStyle . '>',
        '3' => '<img src="../../assets/images/mariadb.jpeg" title="MariaDB" ' . $circleStyle . '>',
        default => '',
    };

    $statusHtml = $row['status'] === 'OK'
        ? '<i class="fa fa-check-circle text-success"></i> OK'
        : '<i class="fa fa-times-circle text-danger"></i> ' . htmlspecialchars($row['status']) .
          (!empty($row['descricao']) ? '<br><span>GLPI: <strong>' . htmlspecialchars($row['descricao']) . '</strong></span>' : '');

    $tamanhoArquivo = $row['tamanho_arquivo'] ? round($row['tamanho_arquivo'] / 1048576, 2) . ' MB' : '-';
    $dataExecucao = date('d/m/Y H:i:s', strtotime($row['data_execucao']));

    $data[] = [
        "estrutura" => $estrutura,
        "bd_nome_usuario" => htmlspecialchars($row['bd_nome_usuario']),
        "bd_ip" => htmlspecialchars($row['bd_ip']),
        "data_execucao" => $dataExecucao,
        "status" => $statusHtml,
        "tempo_decorrido" => htmlspecialchars($row['tempo_decorrido']),
        "tamanho_arquivo" => $tamanhoArquivo
    ];
}

echo json_encode([
    "draw" => $draw,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalFiltered,
    "data" => $data
]);