$envFilePath = Join-Path (Split-Path -Parent $PWD) ".env"
if (-not (Test-Path $envFilePath)) {
    Write-Host "ERRO: Arquivo .env não encontrado na raiz." -ForegroundColor Red
    Write-Host "Copie o .env.example para .env e preencha com suas senhas." -ForegroundColor Yellow
    Read-Host "Pressione Enter"
    exit
}

$envVars = @{}
foreach($line in Get-Content $envFilePath) {
    if ($line -match "^([^#=]+)=(.*)$") {
        $envVars[$matches[1].Trim()] = $matches[2].Trim()
    }
}

$SQL_FILE = "banco.sql"
Write-Host "--------------------------------------------------------" -ForegroundColor Cyan
Write-Host "Configurando banco de dados Bazar..." -ForegroundColor Cyan

$ROOT_USER = Read-Host -Prompt "Usuário do MySQL com permissão admin (padrão: root)"
if ([string]::IsNullOrWhiteSpace($ROOT_USER)) { $ROOT_USER = "root" }

$DB_USER = $envVars["DB_USER"]
$DB_PASS = $envVars["DB_PASS"]
$DB_NAME = $envVars["DB_NAME_USUARIOS"]

$SQL_USER = "DROP USER IF EXISTS '$DB_USER'@'localhost'; CREATE USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS'; GRANT EXECUTE ON ${DB_NAME}.* TO '$DB_USER'@'localhost'; FLUSH PRIVILEGES;"

Write-Host "Será solicitada a senha admin do MySQL." -ForegroundColor Yellow

$Query = (Get-Content $SQL_FILE -Raw) + "`n" + $SQL_USER
$Query | & mysql -u $ROOT_USER -p

if ($LASTEXITCODE -eq 0) {
    Write-Host "Sucesso! O banco e o usuário '$DB_USER' foram configurados dinamicamente." -ForegroundColor Green
} else {
    Write-Host "Erro ao configurar o banco." -ForegroundColor Red
}

Read-Host "Pressione Enter para continuar"