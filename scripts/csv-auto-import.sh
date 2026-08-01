clear

fname="$1"
if [ -z "$fname" ]
then
    echo "Qual arquivo a restaurar?"
    exit 127;
else
    echo "Ok vou restaurar as tabelas para "$fname".";
fi

dir="$PWD"
source $dir/.env

database="$DB_DATABASE"
database_user="$DB_USERNAME"
pass="$DB_PASSWORD"
prefix="adm_"

# Preserve original basename for table name
original_base=$(basename "$fname" .csv)

# Work on a copy to avoid modifying original
cp "$fname" tmp_original.csv
fname="tmp_original.csv"

# Convert Windows line endings (CRLF) to Unix (LF)
sed -i 's/\r$//' "$fname"

# Convert encoding from ISO-8859-3 to UTF-8, ignoring invalid sequences
iconv -f ISO-8859-3 -t UTF-8//IGNORE "$fname" > tmp_converted.csv
fname="tmp_converted.csv"

sed -i '/^$/d' "$fname" #remove linhas vazias

first_line=$(head -1 "$fname")
if [ -z "$first_line" ]; then
    echo "Arquivo vazio após remover linhas em branco."
    exit 1
fi

comma_count=$(echo "$first_line" | tr -cd ',' | wc -c)
semi_count=$(echo "$first_line" | tr -cd ';' | wc -c)

if [ "$comma_count" -gt "$semi_count" ]; then
    delim=','
else
    delim=';'
fi

# Extract and clean header fields into tmp.csv, one per line
awk -v delim="$delim" -F"$delim" 'NR==1 {for(i=1;i<=NF;i++){ gsub(/^"|"$/,"",$i); gsub(/""/, "\"", $i); print $i }}' "$fname" | sed '/^$/d' > tmp.csv

table_name_base="$original_base"
tableName="\`$prefix${table_name_base}\`"

# Build columnsNames by processing each cleaned column name
columnsNames=""
first=true
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

echo 'Importando para:' $tableName
# echo "columnsNames: $columnsNames"
# echo "CREATE TABLE IF NOT EXISTS $tableName($columnsNames);"

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

rm tmp.csv tmp_original.csv tmp_converted.csv