<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerador de Script</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2>Gerador de Script de Restore</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scriptModal">Ver Script</button>
</div>

<!-- Modal -->
<div class="modal fade" id="scriptModal" tabindex="-1" aria-labelledby="scriptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scriptModalLabel">Script de Restore</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <pre style="background-color: #f8f9fa; padding: 10px; border-radius: 5px;">
<?php
// Caminho para o script shell
$scriptPath = '/path/to/gerador_restore.sh';

// Verifica se o arquivo existe
if (file_exists($scriptPath)) {
    // Lê e exibe o conteúdo do script
    echo htmlspecialchars(file_get_contents($scriptPath));
} else {
    echo "O arquivo do script não foi encontrado.";
}
?>
                </pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
