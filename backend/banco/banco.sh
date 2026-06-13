#!/usr/bin/env bash
# =============================================================
#  Bazar Online — Automação do banco de dados (Linux/macOS)
#  Localização: backend/banco/banco.sh
#  Uso:         cd backend/banco && bash banco.sh
#
#  O que este script faz:
#    1. Lê DB_USER, DB_PASS, DB_NAME do arquivo backend/.env
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

set -euo pipefail
cd "$(dirname "$0")"

# ── 0. Cores e helpers ────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'

ok()   { echo -e "${GREEN}[OK]${NC}    $*"; }
info() { echo -e "${CYAN}[INFO]${NC}  $*"; }
warn() { echo -e "${YELLOW}[AVISO]${NC} $*"; }
err()  { echo -e "${RED}[ERRO]${NC}   $*" >&2; }
die()  { err "$*"; echo ""; read -rp "Pressione [Enter] para sair..." _; exit 1; }

# ── 1. Localiza o .env (um nível acima: backend/.env) ─────────
ENV_FILE="$(pwd)/../.env"

if [ ! -f "$ENV_FILE" ]; then
    die "Arquivo .env não encontrado em: $ENV_FILE
     Certifique-se de que o arquivo existe em backend/.env"
fi

info "Lendo configurações de: $ENV_FILE"

# Parser seguro: ignora comentários e linhas em branco, remove aspas
parse_env() {
    local key="$1"
    grep -E "^${key}=" "$ENV_FILE" | head -1 \
        | sed -E "s/^${key}=//" \
        | sed -E "s/^['\"]|['\"]$//g" \
        | tr -d '\r'
}

DB_USER=$(parse_env "DB_USER")
DB_PASS=$(parse_env "DB_PASS")
DB_NAME=$(parse_env "DB_NAME")

# Fallback caso DB_NAME não esteja no .env
[ -z "$DB_NAME" ] && DB_NAME="bazar"

[ -z "$DB_USER" ] && die "DB_USER não está definido no .env"
[ -z "$DB_PASS" ] && die "DB_PASS não está definido no .env"

echo ""
echo -e "${BOLD}╔══════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║   Bazar Online — Setup do banco de dados ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════╝${NC}"
echo ""
info "Banco de dados       : $DB_NAME"
info "Usuário da aplicação : $DB_USER"
echo ""

# ── 2. Solicita credenciais do admin MySQL ────────────────────
read -rp "$(echo -e "${YELLOW}Usuário admin do MySQL${NC} [padrão: root]: ")" ROOT_USER
ROOT_USER="${ROOT_USER:-root}"

read -rsp "$(echo -e "${YELLOW}Senha do usuário '${ROOT_USER}'${NC}: ")" ROOT_PASS
echo ""
echo ""

# Helper: executa SQL como root, captura stdout+stderr
mysql_root() {
    mysql -u"$ROOT_USER" -p"$ROOT_PASS" --batch --silent "$@" 2>&1
}

# ── 3. Testa a conexão root ───────────────────────────────────
info "Testando conexão com o MySQL como '$ROOT_USER'..."
if ! OUTPUT=$(mysql_root -e "SELECT 1;" 2>&1); then
    die "Não foi possível conectar ao MySQL.
     Usuário: $ROOT_USER
     Verifique se o serviço está rodando e se a senha está correta.
     Detalhe: $OUTPUT"
fi
ok "Conexão com MySQL estabelecida."
echo ""

# ── 4. Aplica o schema (banco.sql) ───────────────────────────
SQL_FILE="$(pwd)/banco.sql"
[ ! -f "$SQL_FILE" ] && die "Arquivo banco.sql não encontrado em: $SQL_FILE"

info "Aplicando schema do arquivo banco.sql..."
if ! OUTPUT=$(mysql_root < "$SQL_FILE" 2>&1); then
    die "Falha ao aplicar o schema SQL.
     Verifique o arquivo banco.sql e as permissões do usuário '$ROOT_USER'.
     Detalhe: $OUTPUT"
fi
ok "Schema aplicado com sucesso. Banco '$DB_NAME' está atualizado."
echo ""

# ── 5. Verifica / cria o usuário da aplicação ────────────────
info "Verificando se o usuário '$DB_USER'@'localhost' já existe..."

USER_EXISTS=$(mysql_root \
    -e "SELECT COUNT(*) FROM mysql.user WHERE User='$DB_USER' AND Host='localhost';" \
    2>&1 | grep -E '^[0-9]+$' || echo "0")

if [ "${USER_EXISTS:-0}" -gt 0 ]; then
    warn "Usuário '$DB_USER'@'localhost' já existe — atualizando a senha..."
    if ! OUTPUT=$(mysql_root \
        -e "ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';" 2>&1); then
        die "Não foi possível atualizar a senha do usuário '$DB_USER'.
     Verifique se o usuário root tem permissão de SUPER.
     Detalhe: $OUTPUT"
    fi
    ok "Senha do usuário '$DB_USER' atualizada."
else
    info "Usuário '$DB_USER' não existe — criando..."
    if ! OUTPUT=$(mysql_root \
        -e "CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';" 2>&1); then
        die "Não foi possível criar o usuário '$DB_USER'.
     Detalhe: $OUTPUT"
    fi
    ok "Usuário '$DB_USER'@'localhost' criado."
fi
echo ""

# ── 6. Permissões mínimas (somente DML, sem DDL/GRANT) ───────
info "Configurando permissões de '$DB_USER' no banco '$DB_NAME'..."

# Remove grants anteriores (silencioso se não existiam)
mysql_root -e "REVOKE ALL PRIVILEGES ON \`${DB_NAME}\`.* FROM '${DB_USER}'@'localhost';" \
    2>/dev/null || true

if ! OUTPUT=$(mysql_root \
    -e "GRANT SELECT, INSERT, UPDATE, DELETE ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';" 2>&1); then
    die "Falha ao conceder permissões no banco '$DB_NAME'.
     Detalhe: $OUTPUT"
fi

if ! OUTPUT=$(mysql_root -e "FLUSH PRIVILEGES;" 2>&1); then
    die "Falha ao aplicar privilégios (FLUSH PRIVILEGES).
     Detalhe: $OUTPUT"
fi

ok "Permissões configuradas:"
info "  '$DB_USER'@'localhost' → $DB_NAME : SELECT, INSERT, UPDATE, DELETE"
echo ""

# ── 7. Valida acesso do usuário da aplicação ──────────────────
info "Validando acesso do usuário '$DB_USER' ao banco '$DB_NAME'..."
if ! OUTPUT=$(mysql -u"$DB_USER" -p"$DB_PASS" \
    -e "SELECT 'ok' FROM \`${DB_NAME}\`.\`usuarios\` LIMIT 0;" 2>&1); then
    warn "Não foi possível validar o acesso como '$DB_USER'."
    warn "Detalhe: $OUTPUT"
    warn "O banco foi configurado, mas verifique manualmente o acesso."
else
    ok "Acesso do usuário '$DB_USER' ao banco '$DB_NAME' confirmado."
fi

# ── Resumo final ──────────────────────────────────────────────
echo ""
echo -e "${BOLD}${GREEN}═══════════════════════════════════════════════${NC}"
echo -e "${BOLD}${GREEN}  Setup concluído com sucesso!${NC}"
echo -e "${BOLD}${GREEN}═══════════════════════════════════════════════${NC}"
echo ""
echo -e "  ${BOLD}Próximos passos:${NC}"
echo -e "  1. Confirme que backend/.env contém:"
echo -e "       DB_NAME=${DB_NAME}"
echo -e "       DB_USER=${DB_USER}"
echo -e "  2. Suba o backend:  ${CYAN}cd backend && php -S localhost:8001${NC}"
echo -e "  3. Suba o frontend: ${CYAN}cd frontend && php -S localhost:8080${NC}"
echo ""
read -rp "Pressione [Enter] para fechar..."