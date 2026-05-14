#!/bin/bash
cd "$(dirname "$0")"

MYSQL="/Applications/XAMPP/xamppfiles/bin/mysql"
SOCKET="/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock"
SQL_FILE="compat_xampp.sql"

if [ ! -x "$MYSQL" ]; then
    echo "MySQL do XAMPP nao encontrado em $MYSQL"
    exit 1
fi

echo "Aplicando compatibilidade do banco para XAMPP..."
echo "Use o usuario root do MySQL do XAMPP quando a senha for solicitada."

"$MYSQL" --socket="$SOCKET" -u root -p < "$SQL_FILE"

if [ $? -eq 0 ]; then
    echo "Banco ajustado com sucesso."
else
    echo "Nao foi possivel ajustar o banco. Confira se o MySQL do XAMPP esta iniciado."
    exit 1
fi
