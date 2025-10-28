# Cria um projeto Laravel chamado safekup-laravel na raiz
# Pré-requisitos: Composer disponível no PATH

param(
  [string]$ProjectName = "safekup-laravel"
)

if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
  Write-Error "Composer não encontrado no PATH. Instale o Composer e tente novamente."
  exit 1
}

composer create-project laravel/laravel $ProjectName

Write-Host "Projeto criado em ./$ProjectName"
Write-Host "Copie os stubs de laravel-stubs para dentro do projeto (substituindo arquivos quando necessário)."

