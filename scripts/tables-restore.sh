clear
dir="$PWD"
source $dir/.env

file=$(basename "$1")
SQL=$(echo $file | sed "s/.gz//")

database="$DB_DATABASE"
database_user="$DB_USERNAME"
user_password="$DB_PASSWORD"

if [ -z "$1" ]
then
    echo "Qual arquivo a restaurar?"
    exit 127;
else
    echo "Ok vou restaurar as tabelas para "$1".";
fi

bash scripts/turbinarMysql.sh

table="$table"

# Pedir ao usuário para inserir os nomes das tabelas separados por espaço
read -p "Digite os nomes das tabelas (separados por espaço): " tables_input

# Converter a entrada em uma matriz
IFS=' ' read -ra tables <<< "$tables_input"

    echo "Descompactando Sqls ..."
    diretorio=$(dirname "$1")
    mkdir -p $diretorio/dirSqls
    # unzip "$1" -d $diretorio/dirSqls
    gunzip -c "$1" > $diretorio/dirSqls/$SQL

    cd $diretorio/dirSqls

for table in "${tables[@]}"; do
    table="$table"

    mkdir -p $table
    echo "Extraindo tabela $table ..." &&

    # for f in $SQL; do
        if grep -q "$table" $SQL; then
            echo "A tabela $table foi encontrada."
        else
            RED='\033[0;31m'
            NC='\033[0m'
            echo -e "${RED}A tabela $table não foi encontrada, verifique se o nome está correto.${NC}"
            exit 1
        fi

        sed -n -e "/DROP TABLE.*\`$table\`/,/UNLOCK TABLES/p" $SQL > $table/$table.sql &&

        # sed -i '/DROP TABLE IF EXISTS `[^`]*`/i SET FOREIGN_KEY_CHECKS=0;' $table/$table.sql
        # sed -i '/DROP TABLE IF EXISTS `[^`]*`/a SET FOREIGN_KEY_CHECKS=1;' $table/$table.sql
        
        echo "Restaurando a tabela $table ..."

        hora_atual=$(date +"%H:%M:%S")
        echo "Início: $hora_atual"

        $URL_MYSQL -u $database_user -p$user_password $database -e "
        SET FOREIGN_KEY_CHECKS=0;
        SET sql_log_bin = 0;
        SET unique_checks = 0;
        SET autocommit = 0;
        SET GLOBAL innodb_flush_log_at_trx_commit = 2;   

        SOURCE $table/$table.sql;
        
        SET FOREIGN_KEY_CHECKS=1;
        SET sql_log_bin = 1;
        SET unique_checks = 1;
        SET autocommit = 1;
        SET GLOBAL innodb_flush_log_at_trx_commit = 1;
        "
        # $URL_MYSQL -u $database_user -p$user_password $database < $table/$table.sql

        hora_atual=$(date +"%H:%M:%S")
        echo "Fim: $hora_atual"

    # done
done

pkexec sudo /opt/lampp/lampp restart    

notify-send "Restauração de Tabelas" "Tabelas restauradas com sucesso!"

rm $SQL




