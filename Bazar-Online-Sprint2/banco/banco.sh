#!/bin/bash
cd "$(dirname "$0")"

# Carregar variáveis do .env da raiz do projeto
ENV_FILE="../.env"
if [ ! -f "$ENV_FILE" ]; then
    echo "ERRO: Arquivo .env não encontrado na raiz do projeto!"
    echo "Copie o .env.example para .env e preencha com suas senhas."
    read -p "Pressione [Enter] para continuar..."
    exit 1
fi

# Carrega apenas linhas que não são vazias ou comentários
export $(grep -v '^#' $ENV_FILE | xargs)

ROOT_USER="root"
SQL_FILE="banco.sql"

echo "--------------------------------------------------------"
echo "Configurando banco de dados Bazar..."
echo "Será solicitada a senha do admin '$ROOT_USER' do MySQL."

# Script dinâmico para criar o usuário com as senhas do .env
SQL_USER="DROP USER IF EXISTS '${DB_USER}'@'localhost'; CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}'; GRANT EXECUTE ON ${DB_NAME_USUARIOS}.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"

# Executa o sql fixo + o sql dinâmico em um comando só
mysql -u $ROOT_USER -p -e "source $SQL_FILE; $SQL_USER"

if [ $? -eq 0 ]; then
    echo "Sucesso! O banco e o usuário '${DB_USER}' foram configurados dinamicamente."
else
    echo "Erro ao configurar o banco. Verifique sua senha admin."
fi

read -p "Pressione [Enter] para continuar..."