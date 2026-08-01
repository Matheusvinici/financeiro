# loop
clear

# Variaveis necessária para encontrar o arquvo .env
script_name=$(basename "$0")
echo "Nome do arquivo: $script_name"
script_path=$(readlink -f "$0")
dir=$(echo "$script_path" | sed "s;$script_name;;")
echo $dir

# incluidno o arquivo .env
source $dir../.env

hora_atual=$(date +"%H:%M:%S")
echo "Início: $hora_atual"

if [ "$APP_ENV" == "homo" ] || [ "$APP_ENV" == "production" ]; then
    echo "🌐 Ambiente de produção detectado. Usando variáveis de ambiente do VPS."
DB_PASSWORD=$DB_PASSWORD_rt
DB_USERNAME=root
DB_SERVER=VPS-Hostinger
else
echo "💻 Ambiente local detectado. Carregando variáveis do .env."
fi

database_used=$DB_DATABASE
database_user=$DB_USERNAME
user_password=$DB_PASSWORD

tables=$($URL_MYSQL -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" -D "$DB_DATABASE" -e "

SHOW TABLES;" | grep -v Tables_in)

# Iterate over the tables and rename
for table in $tables; do
  
echo "Otimizando a tabela $table"
  
# Add quotes around database and table names
$URL_MYSQL -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" -D "$DB_DATABASE" -e "
USE $database_used;
# OPTIMIZE TABLE $table;
ANALYZE TABLE $table;
"

done
