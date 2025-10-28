<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('/opt/lampp/htdocs/safekup/php/include/sendticketglpiSingle.inc.php');


$glpiClient = new GlpiClient();


$sql = "
     WITH base_com_rank AS (
    SELECT 
        A.knowbaseitems_id AS ID_da_Base,
        B.completename AS Caminho_da_Base, 
        B.name AS Nome_Categoria,
        C.name AS Nome_da_Base,
        ROW_NUMBER() OVER (
            PARTITION BY C.name 
            ORDER BY LENGTH(B.completename) DESC
        ) AS rn
    FROM glpi_knowbaseitems_knowbaseitemcategories A
    JOIN glpi_knowbaseitemcategories B ON A.knowbaseitemcategories_id = B.id
    JOIN glpi_knowbaseitems C ON A.knowbaseitems_id = C.id
    WHERE B.completename LIKE '%Estrutura Antiga%'
      AND B.sons_cache IS NOT NULL
      AND A.knowbaseitems_id NOT IN (
        273, 183, 221, 285, 134, 198, 178, 10, 176, 276, 208, 179, 225, 255, 279, 197, 267, 268, 282, 
        56, 59, 61, 63, 65, 66, 67, 68, 69, 133, 172, 173, 51, 73, 78, 118, 123, 130, 129,
        48, 70, 77, 90, 102, 117, 146, 161, 132, 74, 21, 34, 92, 40, 52, 71, 41, 112, 55, 46,
        175, 186, 188, 174, 143, 170, 177, 180, 181, 190,
        205, 209, 210, 214, 206, 207, 211, 212, 215,
        228, 220, 224, 227, 230, 226, 233, 229, 222, 223,
        236, 238, 240, 241, 242, 246, 250, 251, 237,
        254, 252, 261, 271, 257, 259, 264, 266, 270,
        272, 193, 204, 191, 288, 194, 278, 199, 218, 277,
        284, 195, 200, 86, 152, 217, 275, 189, 219, 31,  
        216, 202, 274, 281, 158, 136, 283, 203, 127, 160,
        185, 125, 114, 126, 81, 43, 79, 75, 145, 142
      )
)
SELECT 
    ID_da_Base,
    Caminho_da_Base,
    Nome_Categoria,
    Nome_da_Base
FROM base_com_rank
WHERE rn = 1
LIMIT 10;
";

$result = mysqli_query($conexao, $sql);

if (!$result) {
    die("Erro ao executar a consulta: " . mysqli_error($conexao));
}

if (mysqli_num_rows($result) == 0) {
    echo "Nenhum artigo encontrado para revisão.\n";
    exit;
}

while ($row = mysqli_fetch_assoc($result)) {
    $ticketTitle = "Revisão do artigo: " . $row['Nome_da_Base']. " - Id Base:". $row['ID_da_Base'];

    $ticketDescription = "
    Dados do formulário  
    Novo chamado  

    1) Tipo de solicitação: Requisição
    2) Unidade ou Setor: EBSERH > HU-UFSC > SUP > SETISD > UISTI  
    3) Localização: UISTI  
    4) Ramal para contato: 8152  
    5) Título: {$ticketTitle}  
    6) Descrição:  
    A/C: N3  

    Revisão necessária para o artigo da base de conhecimento.  

    - ID da Base: {$row['ID_da_Base']}  
    - Nome da Base: {$row['Nome_da_Base']}  
    - Caminho da Base: {$row['Caminho_da_Base']}  

    Tarefas a serem realizadas:  
    * Analisar vigência – Confirmação da relevância do artigo com base na data de última atualização.  
    * Verificar se o conteúdo está atualizado – Validação da coerência com os processos atuais.  
    * Verificar se o artigo está estruturado – Ajuste da formatação para melhor entendimento.  
    * Inserir tabela de versionamento ao final dos documentos – Implementação do controle de versões.  
    * Validar acessos e FAQ – Revisão de permissões e atualização da FAQ associada.  
    * Remover categorias antigas – Eliminação de categorias obsoletas.  
    * Associar às novas categorias – Inclusão do artigo em categorias atualizadas.  
    * Relatar as mudanças realizadas ou emitir parecer técnico em caso de exclusão – Registro detalhado das ações tomadas.  

    7) Urgência: Baixa  
    8) Anexar arquivos (Opcional): Nenhum documento anexado  
    9) Observadores (Opcional):  Sem 
    10) IP de origem do chamado: 10.94.51.13  
    ";

    try {
        $ticketId = $glpiClient->openTicket($ticketTitle, $ticketDescription);
        echo "Ticket criado com sucesso. ID: $ticketId <br>";
    } catch (Exception $e) {
        echo "Erro ao criar ticket: " . $e->getMessage() . "<br>";
    }
}

mysqli_close($conexao);
?>