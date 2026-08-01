dir="$PWD"
source $dir/.env
echo "Realizando o backup" $DB_DATABASE
date='date +%Y-%m-%d_%H-%M-%S'

#rm database/backups/tables_adm.sql
mysql $DB_DATABASE -u $DB_USERNAME -p"$DB_PASSWORD" -e 'show tables like "adm_%"' | grep -v Tables_in | xargs mysqldump $DB_DATABASE -u $DB_USERNAME -p"$DB_PASSWORD" > database/backups/tables_adm_$date.sql
