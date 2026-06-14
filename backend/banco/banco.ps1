# =============================================================
#  Bazar Online — Automação do banco de dados (Windows/PowerShell)
#  Localização: backend\banco\banco.ps1
#  Uso:         cd backend\banco; .\banco.ps1
#
#  O que este script faz:
#    1. Lê DB_USER, DB_PASS, DB_NAME do arquivo backend\.env
#    2. Pede usuário e senha do admin MySQL (ex: root)
#    3. Testa a conexão antes de fazer qualquer alteração
#    4. Aplica o schema completo (banco.sql)
#    5. Verifica se o usuário DB_USER já existe
#       → cria se não existe / atualiza a senha se já existe
#    6. Concede apenas SELECT, INSERT, UPDATE, DELETE no banco
#       (sem CREATE, DROP, ALTER, GRANT — princípio do menor privilégio)
#    7. Valida o acesso da aplicação ao final
#    8. Exibe mensagens de erro claras e detalhadas em cada etapa
# =============================================================

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# ── Helpers de output colorido ────────────────────────────────
function Write-Ok   ($msg) { Write-Host "[OK]    $msg" -ForegroundColor Green }
function Write-Info ($msg) { Write-Host "[INFO]  $msg" -ForegroundColor Cyan }
function Write-Warn ($msg) { Write-Host "[AVISO] $msg" -ForegroundColor Yellow }
function Write-Err  ($msg) { Write-Host "[ERRO]  $msg" -ForegroundColor Red }
function Exit-Err   ($msg) {
    Write-Err $msg
    Write-Host ""
    Read-Host "Pressione Enter para sair"
    exit 1
}

# ── 1. Localiza o .env (um nível acima: backend\.env) ─────────
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$EnvFile   = Join-Path $ScriptDir "..\.env"

if (-not (Test-Path $EnvFile)) {
    Exit-Err @"
Arquivo .env não encontrado em: $EnvFile
Certifique-se de que o arquivo existe em backend\.env
"@
}

Write-Info "Lendo configurações de: $EnvFile"

# Parser seguro: ignora comentários e linhas em branco, remove aspas
$EnvVars = @{}
foreach ($line in (Get-Content $EnvFile)) {
    $line = $line.Trim()
    if ($line -eq "" -or $line.StartsWith("#")) { continue }
    if ($line -match "^([^=]+)=(.*)$") {
        $key   = $matches[1].Trim()
        $value = $matches[2].Trim().Trim('"').Trim("'")
        $EnvVars[$key] = $value
    }
}

$DB_USER = $EnvVars["DB_USER"]
$DB_PASS = $EnvVars["DB_PASS"]
$DB_NAME = if ($EnvVars.ContainsKey("DB_NAME")) { $EnvVars["DB_NAME"] } else { "bazar" }

if ([string]::IsNullOrWhiteSpace($DB_USER)) { Exit-Err "DB_USER não está definido no .env" }
if ([string]::IsNullOrWhiteSpace($DB_PASS)) { Exit-Err "DB_PASS não está definido no .env" }

Write-Host ""
Write-Host "╔══════════════════════════════════════════╗" -ForegroundColor White
Write-Host "║   Bazar Online — Setup do banco de dados ║" -ForegroundColor White
Write-Host "╚══════════════════════════════════════════╝" -ForegroundColor White
Write-Host ""
Write-Info "Banco de dados       : $DB_NAME"
Write-Info "Usuário da aplicação : $DB_USER"
Write-Host ""

# ── 2. Solicita credenciais do admin MySQL ────────────────────
$ROOT_USER = Read-Host "Usuário admin do MySQL [padrão: root]"
if ([string]::IsNullOrWhiteSpace($ROOT_USER)) { $ROOT_USER = "root" }

$ROOT_PASS_SEC = Read-Host "Senha do usuário '$ROOT_USER'" -AsSecureString
$BSTR          = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($ROOT_PASS_SEC)
$ROOT_PASS     = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)
[System.Runtime.InteropServices.Marshal]::ZeroFreeBSTR($BSTR)
Write-Host ""

# Helper: executa SQL como root
function Invoke-MySqlRoot {
    param([string]$Sql)
    $result = $Sql | & mysql -u"$ROOT_USER" -p"$ROOT_PASS" --batch --silent 2>&1
    return $result
}

# ── 3. Testa a conexão root ───────────────────────────────────
Write-Info "Testando conexão com o MySQL como '$ROOT_USER'..."
try {
    $null = Invoke-MySqlRoot "SELECT 1;"
    if ($LASTEXITCODE -ne 0) { throw "Código de saída: $LASTEXITCODE" }
    Write-Ok "Conexão com MySQL estabelecida."
} catch {
    Exit-Err @"
Não foi possível conectar ao MySQL.
Usuário: $ROOT_USER
Verifique se o serviço está rodando e se a senha está correta.
Detalhe: $_
"@
}
Write-Host ""

# ── 4. Aplica o schema (banco.sql) ───────────────────────────
$SqlFile = Join-Path $ScriptDir "banco.sql"
if (-not (Test-Path $SqlFile)) {
    Exit-Err "Arquivo banco.sql não encontrado em: $SqlFile"
}

Write-Info "Aplicando schema do arquivo banco.sql..."
try {
    $sqlContent = Get-Content $SqlFile -Raw -Encoding UTF8
    $result     = $sqlContent | & mysql -u"$ROOT_USER" -p"$ROOT_PASS" --batch --silent 2>&1
    if ($LASTEXITCODE -ne 0) { throw $result }
    Write-Ok "Schema aplicado com sucesso. Banco '$DB_NAME' está atualizado."
} catch {
    Exit-Err "Falha ao aplicar o schema SQL. Verifique banco.sql e as permissões de '$ROOT_USER'. Detalhe: $_"
}
Write-Host ""

# ── 5. Verifica / cria o usuário da aplicação ────────────────
Write-Info "Verificando se o usuário '$DB_USER'@'localhost' já existe..."
try {
    $checkResult = Invoke-MySqlRoot "SELECT COUNT(*) FROM mysql.user WHERE User='$DB_USER' AND Host='localhost';"
    $userExists  = [int]($checkResult -replace '\D', '')
} catch {
    $userExists = 0
}

if ($userExists -gt 0) {
    Write-Warn "Usuário '$DB_USER'@'localhost' já existe — atualizando a senha..."
    try {
        $result = Invoke-MySqlRoot "ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
        if ($LASTEXITCODE -ne 0) { throw $result }
        Write-Ok "Senha do usuário '$DB_USER' atualizada."
    } catch {
        Exit-Err "Não foi possível atualizar a senha do usuário '$DB_USER'. Detalhe: $_"
    }
} else {
    Write-Info "Usuário '$DB_USER' não existe — criando..."
    try {
        $result = Invoke-MySqlRoot "CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
        if ($LASTEXITCODE -ne 0) { throw $result }
        Write-Ok "Usuário '$DB_USER'@'localhost' criado."
    } catch {
        Exit-Err "Não foi possível criar o usuário '$DB_USER'. Detalhe: $_"
    }
}
Write-Host ""

# ── 6. Permissões mínimas (somente EXECUTE de stored procedures) ───────
Write-Info "Configurando permissões de '$DB_USER' no banco '$DB_NAME'..."

# Remove grants anteriores (silencioso se não existiam)
Invoke-MySqlRoot "REVOKE ALL PRIVILEGES ON ``${DB_NAME}``.* FROM '${DB_USER}'@'localhost';" 2>$null | Out-Null

try {
    $result = Invoke-MySqlRoot "GRANT EXECUTE ON ``${DB_NAME}``.* TO '${DB_USER}'@'localhost';"
    if ($LASTEXITCODE -ne 0) { throw $result }
} catch {
    Exit-Err "Falha ao conceder permissões no banco '$DB_NAME'. Detalhe: $_"
}

try {
    $result = Invoke-MySqlRoot "FLUSH PRIVILEGES;"
    if ($LASTEXITCODE -ne 0) { throw $result }
} catch {
    Exit-Err "Falha ao aplicar privilégios (FLUSH PRIVILEGES). Detalhe: $_"
}

Write-Ok "Permissões configuradas:"
Write-Info "  '$DB_USER'@'localhost' -> $DB_NAME : EXECUTE (apenas execução de procedures)"
Write-Host ""

# ── 7. Valida acesso do usuário da aplicação ──────────────────
Write-Info "Validando acesso do usuário '$DB_USER' ao banco '$DB_NAME'..."
try {
    $validSql = "CALL ``${DB_NAME}``.sp_buscar_usuario_por_email('teste@teste.com');"
    $result   = $validSql | & mysql -u"$DB_USER" -p"$DB_PASS" --batch --silent 2>&1
    if ($LASTEXITCODE -ne 0) { throw $result }
    Write-Ok "Acesso do usuário '$DB_USER' ao banco '$DB_NAME' (EXECUTE) confirmado."
} catch {
    Write-Warn "Não foi possível validar o acesso como '$DB_USER'."
    Write-Warn "Detalhe: $_"
    Write-Warn "O banco foi configurado, mas verifique manualmente o acesso."
}

# ── Resumo final ──────────────────────────────────────────────
Write-Host ""
Write-Host "═══════════════════════════════════════════════" -ForegroundColor Green
Write-Host "  Setup concluído com sucesso!" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════" -ForegroundColor Green
Write-Host ""
Write-Host "  Próximos passos:" -ForegroundColor White
Write-Host "  1. Confirme que backend\.env contém:"
Write-Host "       DB_NAME=$DB_NAME"
Write-Host "       DB_USER=$DB_USER"
Write-Host "  2. Suba o backend:  cd backend; php -S localhost:8001" -ForegroundColor Cyan
Write-Host "  3. Suba o frontend: cd frontend; php -S localhost:8080" -ForegroundColor Cyan
Write-Host ""
Read-Host "Pressione Enter para fechar"