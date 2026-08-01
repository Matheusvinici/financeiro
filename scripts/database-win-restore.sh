#!/bin/bash

# Carrega variáveis do arquivo .env, ignorando comentários e linhas inválidas
if [ -f ".env" ]; then
    while IFS='=' read -r key value; do
        # Ignora linhas vazias, comentários e linhas sem '='
        if [[ -n "$key" && ! "$key" =~ ^\s*# && -n "$value" ]]; then
            export "$key=$value"
        fi
    done < <(grep -v '^\s*#' .env | grep '=')
else
    echo "Erro: Arquivo .env não encontrado!"
    exit 127
fi

# Verifica se o argumento do arquivo foi fornecido e é um arquivo .gz
if [ -z "$1" ]; then
    echo "Qual arquivo a restaurar?"
    exit 127
elif [[ ! "$1" =~ \.gz$ ]]; then
    echo "Erro: O arquivo deve ter a extensão .gz"
    exit 127
else
    # Converte o caminho do Windows para formato compatível com Git Bash
    file_path=$(echo "$1" | sed 's/\\/\\\\/g')
    if [ ! -f "$file_path" ]; then
        echo "Erro: O arquivo $file_path não existe"
        exit 127
    fi
    echo "Ok vou restaurar as tabelas para $file_path."
fi

# Define variáveis
file=$(basename "$file_path")
SQL=$(echo "$file" | sed 's/.gz//')
database_tmp="tmp_websim"
database_used="$DB_DATABASE" # Usa o nome do banco do .env (juazeiroba)
database_user="$DB_USERNAME" # Usa o usuário do .env (root)
user_password="" # Sem senha, conforme confirmado
mysql_path="/c/xampp/mysql/bin/mysql.exe"
gunzip_path="/usr/bin/gunzip"
dir_sqls="$(dirname "$file_path")/dirSqls"

# Converte caminho do Windows para estilo Unix
dir_sqls=$(echo "$dir_sqls" | sed 's/\\/\\\\/g')

# Log do horário de início
hora_atual=$(date +"%H:%M:%S")
echo "Início: $hora_atual"

# Cria diretório para arquivos SQL
mkdir -p "$dir_sqls"

# Descompacta o arquivo SQL
echo "Descompactando Sqls ..."
"$gunzip_path" -c "$file_path" > "$dir_sqls/$SQL"
if [ $? -ne 0 ]; then
    echo "Erro ao descompactar o arquivo. Verifique o caminho e tente novamente."
    exit 1
fi
sed -i '1d' "$dir_sqls/$SQL"

# Muda para o diretório SQL
cd "$dir_sqls" || exit 1

# Restaura o banco de dados
echo "Restaurando as tabelas ..."

# Drop e cria banco temporário (sem senha)
"$mysql_path" -u "$database_user" -e "DROP DATABASE IF EXISTS $database_tmp; CREATE DATABASE $database_tmp;"

# Restaura o dump com otimizações (sem senha)
"$mysql_path" -u "$database_user" "$database_tmp" <<EOF
SET foreign_key_checks = 0;
SET sql_log_bin = 0;
SET unique_checks = 0;
SET autocommit = 0;
SET GLOBAL innodb_flush_log_at_trx_commit = 2;
SOURCE $SQL;
SET foreign_key_checks = 1;
SET sql_log_bin = 1;
SET unique_checks = 1;
SET autocommit = 1;
SET GLOBAL innodb_flush_log_at_trx_commit = 1;
COMMIT;
EOF

echo "Restauração concluída!"

# Obtém lista de tabelas e renomeia (sem senha)
tables=$("$mysql_path" -u "$database_user" "$database_tmp" -e "SHOW TABLES;" | grep -v Tables_in)

for table in $tables; do
    echo "Renaming table $database_tmp.$table to $database_used.$table"
    "$mysql_path" -u "$database_user" -e "USE $database_tmp; RENAME TABLE $table TO $database_used.$table;"
done

# Cria função armazenada (sem senha)
"$mysql_path" -u "$database_user" "$database_used" -e "
DELIMITER \$\$
CREATE FUNCTION arredondamento(numero DOUBLE)
RETURNS DOUBLE
DETERMINISTIC
BEGIN
    RETURN ROUND(numero * 2) / 2;
END\$\$
DELIMITER ;
"

# Reinicia serviços do XAMPP (comente até confirmar os nomes corretos)
# echo "Restarting XAMPP..."
# net stop Apache2.4
# net stop mysql
# net start Apache2.4
# net start mysql

# Log do horário de término
hora_atual=$(date +"%H:%M:%S")
echo "Fim: $hora_atual"

# Notifica o usuário (opcional, requer BurntToast)
# Descomente após instalar o módulo BurntToast
# powershell -command "New-BurntToastNotification -Text 'Restauração de Banco de Dados', 'Banco de dados restaurado com sucesso!'"