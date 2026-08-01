# loop
clear
dir="$PWD"
source $dir/.env

# read -p "Digite o nome da tabela: " table

dirZips="/tmp"
dirSqls="/tmp"
database="backup"
database_tmp="backup_tmp"
table="adm_$table"



# Pedir ao usuário para inserir os nomes das tabelas separados por espaço
read -p "Digite os nomes das tabelas (separados por espaço): " tables_input

# Converter a entrada em uma matriz
IFS=' ' read -ra tables <<< "$tables_input"

for table in "${tables[@]}"; do
    table="adm_$table"

    mkdir -p $dirSqls/$table &&
    cd $dirZips &&

    echo "Descompactando Sqls ..."

    unzip \*.zip -d $dirSqls &&
    # gunzip -kc \*.gz > $dirSqls &&


    echo "Extraindo tabela $table ..." &&
    cd $dirSqls &&

    for f in *.sql; do
        if grep -q "$table" $f; then
            echo "A tabela $table foi encontrada."
        else
            RED='\033[0;31m'
            NC='\033[0m'
            echo -e "${RED}A tabela $table não foi encontrada, verifique se o nome está correto.${NC}"
            exit 1
        fi

        # Desativar verificações de chave estrangeira
        /opt/lampp/bin/mysql -u root -p'root' $DB_DATABASE -e "SET FOREIGN_KEY_CHECKS=0;"

        sed -n -e "/DROP TABLE.*\`$table\`/,/UNLOCK TABLES/p" $f > $table/$f &&
        rm $dirSqls/$f
        # sed -i '/CONSTRAINT `/d' $dirSqls/$table/$f &&
        # awk -i inplace 'BEGIN{RS="";ORS="\n"} {gsub("\\),\n) ENGINE=", ")\n) ENGINE=")} 1' $dirSqls/$table/$f &&

        sed -i '/DROP TABLE IF EXISTS `[^`]*`/i SET FOREIGN_KEY_CHECKS=0;' $dirSqls/$table/$f
        sed -i '/DROP TABLE IF EXISTS `[^`]*`/a SET FOREIGN_KEY_CHECKS=1;' $dirSqls/$table/$f

        # Criação de um novo arquivo temporário com instruções adicionais
        # echo "SET FOREIGN_KEY_CHECKS=0;" > $dirSqls/$table/tmp_sql_file.sql
        # cat $dirSqls/$table/$f >> $dirSqls/$table/tmp_sql_file.sql
        # echo "SET FOREIGN_KEY_CHECKS=1;" >> $dirSqls/$table/tmp_sql_file.sql
        # mv $dirSqls/$table/tmp_sql_file.sql $dirSqls/$table/$f

        echo "Restaurando a tabela $table ..."

        hora_atual=$(date +"%H:%M:%S")
        echo "Início: $hora_atual"

        /opt/lampp/bin/mysql -u root -p'root' $DB_DATABASE < $dirSqls/$table/$f

        hora_atual=$(date +"%H:%M:%S")
        echo "Fim: $hora_atual"

    done
done






