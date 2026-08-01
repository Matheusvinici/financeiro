clear

for cmd in wget unzip; do
    if ! command -v $cmd &>/dev/null; then
        echo "Erro: $cmd não encontrado. Instale-o primeiro."
        exit 1
    fi
done

INPUT="$1"

if [ -z "$INPUT" ]; then
    echo -n "Qual a URL do arquivo ZIP? "
    read INPUT
fi

if echo "$INPUT" | grep -qiE '^https?://'; then
    MODE="url"
elif [ -f "$INPUT" ]; then
    MODE="local"
else
    echo "Arquivo não encontrado ou URL inválida: $INPUT"
    exit 127
fi

dir="$PWD"
source $dir/.env

database="$DB_DATABASE"
database_user="$DB_USERNAME"
pass="$DB_PASSWORD"
prefix="adm_censo_"

process_csv() {
    local fname="$1"

    if [ ! -f "$fname" ]; then
        echo "  Arquivo não encontrado: $fname"
        return 1
    fi

    local original_base=$(basename "$fname" .csv)
    echo ""
    echo ">>> Processando: $original_base"

    cp "$fname" tmp_original.csv
    fname="tmp_original.csv"

    sed -i 's/\r$//' "$fname"

    iconv -f ISO-8859-3 -t UTF-8//IGNORE "$fname" > tmp_converted.csv
    fname="tmp_converted.csv"

    sed -i '/^$/d' "$fname"

    first_line=$(head -1 "$fname")
    if [ -z "$first_line" ]; then
        echo "  Arquivo vazio após remover linhas em branco."
        rm -f tmp_original.csv tmp_converted.csv
        return 1
    fi

    comma_count=$(echo "$first_line" | tr -cd ',' | wc -c)
    semi_count=$(echo "$first_line" | tr -cd ';' | wc -c)

    if [ "$comma_count" -gt "$semi_count" ]; then
        delim=','
    else
        delim=';'
    fi

    awk -v delim="$delim" -F"$delim" 'NR==1 {for(i=1;i<=NF;i++){ gsub(/^"|"$/,"",$i); gsub(/""/, "\"", $i); print $i }}' "$fname" | sed '/^$/d' > tmp.csv

    local table_name_base="$original_base"
    local tableName="\`$prefix${table_name_base}\`"

    local columnsNames=""
    local first=true
    while IFS= read -r col || [ -n "$col" ]; do
        col=$(echo "$col" | sed 's/\"//g' | iconv -t ASCII//TRANSLIT//IGNORE 2>/dev/null | sed 's/\[//g' | sed 's/\]//g' | sed 's/(//g' | sed 's/)//g' | sed 's/[^a-zA-Z0-9_]/_/g')
        if [ -n "$col" ] && [ "$col" != "_" ]; then
            if [ "$first" = false ]; then
                columnsNames="${columnsNames}, "
            fi
            columnsNames="${columnsNames}\`${col}\` TEXT"
            first=false
        fi
    done < tmp.csv

    echo "  Importando para: $tableName"

    /opt/lampp/bin/mysql --default-character-set=utf8mb4 $database -u $database_user -p$pass -e "
    DROP TABLE IF EXISTS $tableName;
    CREATE TABLE IF NOT EXISTS $tableName($columnsNames)
    ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    LOAD DATA LOCAL INFILE '$fname' INTO TABLE $tableName
    CHARACTER SET utf8mb4
    FIELDS TERMINATED BY '$delim' OPTIONALLY ENCLOSED BY '\"'
    LINES TERMINATED BY '\n'
    IGNORE 1 LINES;

    ALTER TABLE $tableName ADD COLUMN \`id\` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT FIRST
    "

    rm -f tmp.csv tmp_original.csv tmp_converted.csv
    echo "  Concluído: $original_base"
}

if [ "$MODE" = "url" ]; then
    echo "Baixando arquivo ZIP..."
    echo "  URL: $INPUT"

    TEMP_DIR=$(mktemp -d)
    ZIP_FILE="$TEMP_DIR/arquivo.zip"

    wget -O "$ZIP_FILE" --no-check-certificate "$INPUT" 2>&1 || {
        echo "Erro ao baixar o arquivo."
        rm -rf "$TEMP_DIR"
        exit 1
    }

    echo "Descompactando..."
    unzip -q "$ZIP_FILE" -d "$TEMP_DIR/extracao" || {
        echo "Erro ao descompactar."
        rm -rf "$TEMP_DIR"
        exit 1
    }

    DADOS_DIR=$(find "$TEMP_DIR/extracao" -type d -name "dados" | head -1)

    if [ -z "$DADOS_DIR" ]; then
        echo "Pasta 'dados' não encontrada dentro do arquivo ZIP."
        rm -rf "$TEMP_DIR"
        exit 1
    fi

    echo "Pasta dados encontrada em: $DADOS_DIR"
    echo ""

    CSV_COUNT=$(find "$DADOS_DIR" -maxdepth 1 -name "*.csv" | wc -l)
    echo "Encontrados $CSV_COUNT arquivos CSV para importar."
    echo ""

    for csv_file in "$DADOS_DIR"/*.csv; do
        [ -f "$csv_file" ] || continue
        process_csv "$csv_file"
    done

    rm -rf "$TEMP_DIR"
    echo ""
    echo ">>> Importação concluída! Todos os CSVs foram processados."

elif [ "$MODE" = "local" ]; then
    process_csv "$INPUT"
fi
