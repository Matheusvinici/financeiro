# loop
clear
dir="$PWD"
source $dir/.env

if [ -z "$1" ]
then
    echo "Qual o nome do seu arquivo SQL?"
    exit 127;
else
    echo "Ok vou extrair as tabelas para "$1".";
fi


mkdir -p /tmp/extracted_sqls_table

dirZips="/tmp"
dirSqls="/tmp/extracted_sqls_table"
fileZip=$1
SQL=$(echo $1 | sed "s/.gz//")
table="adm_$table"

read -p "Digite os nomes das tabelas (separados por espaço): " tables_input

IFS=' ' read -ra tables <<< "$tables_input"

    filename_without_path="${fileZip##*/}"  # Remove o caminho
    filename_without_extension="${filename_without_path%.gz}"  # Remove a extensão .zip

for table in "${tables[@]}"; do
    table="adm_$table"

    mkdir -p $dirSqls/$table &&
    cd $dirZips &&

    echo "Descompactando Sqls ..."
    # unzip $fileZip -d $dirSqls &&
    gunzip -c $fileZip  > $dirSqls/$filename_without_extension &&

    echo "Extraindo tabela $table ..." &&
    cd $dirSqls &&


    for f in $filename_without_extension; do
        if grep -q "$table" $f; then
            echo "A tabela $table foi encontrada."
        else
            RED='\033[0;31m'
            NC='\033[0m'
            echo -e "${RED}A tabela $table não foi encontrada, verifique se o nome está correto.${NC}"
            exit 1
        fi

        sed -n -e "/DROP TABLE.*\`$table\`/,/UNLOCK TABLES/p" $f > $table/$f &&
        rm $dirSqls/$f

        echo "tabela extraída para /tmp/extracted_sqls_table/$table/"

    done
done






