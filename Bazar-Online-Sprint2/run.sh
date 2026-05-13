#!/bin/bash

cd "$(dirname "$0")"

echo "=========================================="
echo "    Iniciando Bazar Online (Linux/Mac)    "
echo "=========================================="

# ── 1. VERIFICAR E INSTALAR DEPENDÊNCIAS ──────────────────────
echo ""
echo "-> Passo 1: Verificando dependencias..."

instalar_dependencias() {
    echo "   Dependencias ausentes. Tentando instalar automaticamente..."
    if command -v apt >/dev/null 2>&1; then
        echo "   [Debian/Ubuntu] Usando apt..."
        sudo brew update -q
        sudo brew install -y mysql-server php php-mysql php-mbstring
    elif command -v brew >/dev/null 2>&1; then
        echo "   [macOS] Usando brew..."
        sudo  brew install mysql php
    elif command -v dnf >/dev/null 2>&1; then
        echo "   [Fedora] Usando dnf..."
        sudo dnf install -y mysql-server php php-mysqlnd php-mbstring
    else
        echo "   [ERRO] Gerenciador de pacotes nao reconhecido."
        echo "   Instale o MySQL e o PHP manualmente e execute novamente."
        read -p "Pressione [Enter] para sair..."
        exit 1
    fi
}

DEPENDENCIAS_OK=true

if ! command -v mysql >/dev/null 2>&1; then
    echo "   [!] MySQL nao encontrado."
    DEPENDENCIAS_OK=false
fi
if ! command -v php >/dev/null 2>&1; then
    echo "   [!] PHP nao encontrado."
    DEPENDENCIAS_OK=false
fi
if command -v php >/dev/null 2>&1 && ! php -m 2>/dev/null | grep -qi mysqli; then
    echo "   [!] Extensao mysqli do PHP ausente."
    DEPENDENCIAS_OK=false
fi

if [ "$DEPENDENCIAS_OK" = false ]; then
    instalar_dependencias
else
    echo "   [OK] PHP e MySQL encontrados."
fi

# ── 2. INICIAR SERVIÇO DO MYSQL ───────────────────────────────
echo ""
echo "-> Passo 2: Iniciando servico do MySQL..."

if command -v systemctl >/dev/null 2>&1; then
    if ! systemctl is-active --quiet mysql 2>/dev/null && \
       ! systemctl is-active --quiet mysqld 2>/dev/null; then
        sudo systemctl start mysql 2>/dev/null || sudo systemctl start mysqld 2>/dev/null
        sleep 2
    fi
    echo "   [OK] Servico MySQL ativo."
elif command -v brew >/dev/null 2>&1; then
    brew services start mysql >/dev/null 2>&1
    echo "   [OK] Servico MySQL ativo (brew)."
else
    echo "   [AVISO] Nao foi possivel iniciar o servico automaticamente."
    echo "   Certifique-se de que o MySQL esta rodando antes de continuar."
    read -p "   Pressione [Enter] quando o MySQL estiver ativo..."
fi

# ── 3. ATUALIZAR BANCO DE DADOS (PERGUNTA INTERATIVA) ─────────
echo ""
read -p "-> Passo 3: Deseja configurar/atualizar o banco de dados? (s/N): " ATUALIZAR
ATUALIZAR="${ATUALIZAR:-n}"

if [[ "$ATUALIZAR" =~ ^[Ss]$ ]]; then
    echo "   Executando script do banco..."
    bash banco/banco.sh
else
    echo "   Banco ignorado. Usando a versao atual."
fi

# ── 4. INICIAR SERVIDOR PHP ───────────────────────────────────
echo ""
echo "-> Passo 4: Iniciando o servidor PHP..."
echo ""
echo "   Acesse o sistema em:"
echo "   👉  http://localhost:8080"
echo ""
echo "   Pressione CTRL+C para encerrar o servidor."
echo "=========================================="

php -S localhost:8080
