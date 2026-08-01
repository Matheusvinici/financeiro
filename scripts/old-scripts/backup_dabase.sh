dir="$PWD"
source $dir/.env
echo $DB_DATABASE

/opt/lampp/bin/mysqldump -u root -p'root' $DB_DATABASE > database/db_$DB_DATABASE.sql