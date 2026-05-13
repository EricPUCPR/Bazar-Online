$ErrorActionPreference = "Stop"

$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Definition
Set-Location $scriptPath

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "     Iniciando Bazar Online (Windows)     " -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

# ── 1. VERIFICAR E INSTALAR DEPENDENCIAS ──────────────────────
Write-Host "`n-> Passo 1: Verificando dependencias..." -ForegroundColor Yellow

$mysqlOk = Get-Command mysql -ErrorAction SilentlyContinue
$phpOk   = Get-Command php   -ErrorAction SilentlyContinue

if (-not $mysqlOk -or -not $phpOk) {
    Write-Host "   [!] MySQL ou PHP nao encontrados." -ForegroundColor Red
    Write-Host "   Deseja tentar instalar via winget? (S/N)" -ForegroundColor Yellow
    $resp = Read-Host
    if ($resp -match "^[Ss]$") {
        if (-not $mysqlOk) { winget install -e --id Oracle.MySQL }
        if (-not $phpOk)   { winget install -e --id PHP.PHP }
        Write-Host "`n   [AVISO] Apos a instalacao, reinicie o terminal e execute run.ps1 novamente." -ForegroundColor Yellow
        Write-Host "   Se preferir um ambiente ja configurado, instale o XAMPP: https://www.apachefriends.org/" -ForegroundColor Cyan
    } else {
        Write-Host "   Instale as dependencias manualmente (recomendamos o XAMPP) e execute novamente." -ForegroundColor Yellow
    }
    Read-Host "`n   Pressione Enter para sair"
    exit
}

Write-Host "   [OK] PHP e MySQL encontrados." -ForegroundColor Green

# ── 2. INICIAR SERVICO DO MYSQL ───────────────────────────────
Write-Host "`n-> Passo 2: Iniciando servico do MySQL..." -ForegroundColor Yellow

$servico = Get-Service -Name "MySQL*" -ErrorAction SilentlyContinue
if ($servico) {
    if ($servico.Status -ne "Running") {
        Start-Service $servico.Name
        Start-Sleep -Seconds 2
    }
    Write-Host "   [OK] Servico MySQL ativo." -ForegroundColor Green
} else {
    Write-Host "   [AVISO] Servico MySQL nao encontrado pelo Windows." -ForegroundColor Yellow
    Write-Host "   Certifique-se de que o MySQL esta rodando antes de continuar." -ForegroundColor Yellow
    Read-Host "   Pressione Enter quando o MySQL estiver ativo"
}

# ── 3. ATUALIZAR BANCO DE DADOS (PERGUNTA INTERATIVA) ─────────
Write-Host "`n-> Passo 3: Deseja configurar/atualizar o banco de dados? (S/N)" -ForegroundColor Yellow
$atualizar = Read-Host
if ($atualizar -match "^[Ss]$") {
    Write-Host "   Executando script do banco..." -ForegroundColor Cyan
    powershell -ExecutionPolicy Bypass -File .\banco\banco.ps1
} else {
    Write-Host "   Banco ignorado. Usando a versao atual." -ForegroundColor DarkGray
}

# ── 4. INICIAR SERVIDOR PHP ───────────────────────────────────
Write-Host "`n-> Passo 4: Iniciando o servidor PHP..." -ForegroundColor Yellow
Write-Host ""
Write-Host "   Acesse o sistema em:" -ForegroundColor White
Write-Host "   >> http://localhost:8080" -ForegroundColor Green
Write-Host ""
Write-Host "   Pressione CTRL+C para encerrar o servidor." -ForegroundColor DarkGray
Write-Host "==========================================" -ForegroundColor Cyan

& php -S localhost:8080
