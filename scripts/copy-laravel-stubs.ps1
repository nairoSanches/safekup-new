param(
  [Parameter(Mandatory = $false)]
  [string]$Target = "safekup-laravel"
)

if (-not (Test-Path $Target)) {
  Write-Error "Diretório de destino '$Target' não existe. Crie o projeto Laravel primeiro."
  exit 1
}

function Copy-Into($src, $dst) {
  if (-not (Test-Path $dst)) { New-Item -ItemType Directory -Path $dst | Out-Null }
  Get-ChildItem -Path $src -Recurse | ForEach-Object {
    $rel = $_.FullName.Substring($src.Length).TrimStart("/","\\")
    $outPath = Join-Path $dst $rel
    if ($_.PSIsContainer) {
      if (-not (Test-Path $outPath)) { New-Item -ItemType Directory -Path $outPath | Out-Null }
    } else {
      Copy-Item -Force $_.FullName $outPath
    }
  }
}

$repoRoot = Split-Path -Parent $PSScriptRoot
$stubs = Join-Path $repoRoot "laravel-stubs"

if (-not (Test-Path $stubs)) {
  Write-Error "Diretório de stubs não encontrado em '$stubs'"
  exit 1
}

# Copia Models, Controllers, Routes e Views
Copy-Into (Join-Path $stubs "app") (Join-Path $Target "app")
Copy-Into (Join-Path $stubs "routes") (Join-Path $Target "routes")
Copy-Into (Join-Path $stubs "resources") (Join-Path $Target "resources")

Write-Host "Stubs copiados para '$Target'. Abra o projeto e ajuste o .env."

