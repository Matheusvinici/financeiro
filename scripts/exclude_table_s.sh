# loop
clear
dir="$PWD"
source $dir/.env

if [ -z "$1" ]
then
    echo "Qual o nome do seu arquivo SQL?"
    exit 127;
else
    echo "Ok vou gerar novo sql excluindo tabelas.";
fi

# read -p "Digite o nome da tabela: " table

mkdir -p /tmp/excluded_sqls_table

dirZips="/tmp"
dirSqls="/tmp/excluded_sqls_table"
fileZip=$1
table="adm_$table"

# Pedir ao usuário para inserir os nomes das tabelas separados por espaço
read -p "Digite os nomes das tabelas a serem excluídas (separados por espaço): " tables_input

# Converter a entrada em uma matriz
IFS=' ' read -ra tables <<< "$tables_input"

for table in "${tables[@]}"; do
    table="adm_$table"

    mkdir -p $dirSqls/$table &&
    cd $dirZips &&

    echo "Descompactando Sqls ..."
    unzip $fileZip -d $dirSqls &&

    echo "Excluindo a(s) tabela(s) $table ..." &&
    cd $dirSqls &&

    filename_without_path="${fileZip##*/}"  # Remove o caminho
    filename_without_extension="${filename_without_path%.zip}"  # Remove a extensão .zip

    for f in $filename_without_extension; do
        if grep -q "$table" $f; then
            echo "A tabela $table foi encontrada."
        else
            RED='\033[0;31m'
            NC='\033[0m'
            echo -e "${RED}A tabela $table não foi encontrada, verifique se o nome está correto.${NC}"
            exit 1
        fi

        sed -e "/DROP TABLE.*\`$table\`/,/UNLOCK TABLES/d" $f > $table/$f
        rm $dirSqls/$f

        echo "tabela(s) excluida(s) com sucesso. Novo arquivo gerado em /tmp/excluded_sqls_table/$table/"

    done
done






