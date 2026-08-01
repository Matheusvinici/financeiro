# loop
clear

if [ -z "$1" ]
then
    echo "Qual arquivo a restaurar?"
    exit 127;
else
    echo "Ok vou restaurar as tabelas para "$1".";
fi

bash scripts/turbinarMysql.sh

hora_atual=$(date +"%H:%M:%S")
echo "Início: $hora_atual"

file=$(basename "$1")
SQL=$(echo $file | sed "s/.gz//")
database_tmp="tmp_websim"
database_used="ep"
database_user="root"
user_password='root'

    echo "Descompactando Sqls ..."
    diretorio=$(dirname "$1")
    mkdir -p $diretorio/dirSqls
    # gunzip "$1" -d $diretorio/dirSqls
    gunzip -c "$1" > $diretorio/dirSqls/$SQL
    sed -i '1d' $diretorio/dirSqls/$SQL
    sed -i '/[\/*][!M!]*999999.*sandbox/d' $diretorio/dirSqls/$SQL

    cd $diretorio/dirSqls

    echo "Restaurando as tabelas ..."

/opt/lampp/bin/mysql -u $database_user -p$user_password -e "
DROP DATABASE IF EXISTS $database_tmp;
CREATE DATABASE $database_tmp;
"

# Inicia a restauração com otimizações
/opt/lampp/bin/mysql -u $database_user -p$user_password $database_tmp --force <<EOF
-- Desabilitar verificações de chaves estrangeiras
SET foreign_key_checks = 0;

-- Desabilitar logs binários temporariamente
SET sql_log_bin = 0;

-- Desabilitar criação de índices temporariamente (apenas se a tabela for InnoDB)
SET unique_checks = 0;
SET autocommit = 0;

-- Alterar a frequência de flush do InnoDB para reduzir operações de disco
SET GLOBAL innodb_flush_log_at_trx_commit = 2;

-- Restaurar o dump
SOURCE $SQL;

-- Habilitar as verificações e logs novamente após a restauração
SET foreign_key_checks = 1;
SET sql_log_bin = 1;
SET unique_checks = 1;
SET autocommit = 1;
SET GLOBAL innodb_flush_log_at_trx_commit = 1;

-- Recriar índices (caso necessário)
-- ALTER TABLE nome_da_tabela ENABLE KEYS;

COMMIT;
EOF

echo "Restauração concluída!"

tables=$(/opt/lampp/bin/mysql -u $database_user -p$user_password $database_tmp -e "
DROP DATABASE IF EXISTS $database_used;
CREATE DATABASE IF NOT EXISTS $database_used;
SHOW TABLES;" | grep -v Tables_in)

# Iterate over the tables and rename
for table in $tables; do
  echo "Renaming table $database_tmp.$table to $database_used.$table"
  
  # Add quotes around database and table names
  /opt/lampp/bin/mysql -u $database_user -p$user_password -e "
    USE $database_tmp;
    RENAME TABLE $table TO $database_used.$table;
        
  "
done

/opt/lampp/bin/mysql -u $database_user -p$user_password -e "
    USE $database_used;

        DELIMITER $$
        CREATE FUNCTION arredondamento(numero DOUBLE)
        RETURNS DOUBLE
        DETERMINISTIC
        BEGIN
            RETURN ROUND(numero * 2) / 2;
        END$$
        DELIMITER ;

"

pkexec sudo /opt/lampp/lampp restart    

hora_atual=$(date +"%H:%M:%S")
echo "Fim: $hora_atual"

notify-send "Restauração de Banco de Dados" "Banco de dados restaurado com sucesso!"
