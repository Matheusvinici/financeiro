# dir="/home/u325366976/domains/serverweb.tech/juazeiroba/"

script_name=$(basename "$0")
echo "Nome do arquivo: $script_name"
script_path=$(readlink -f "$0")
dir=$(echo "$script_path" | sed "s;$script_name;;")

echo $dir

source $dir../../.env

echo "Realizando o backup" $DB_DATABASE
date=$(date '+%Y-%m-%d_%Hh-%Mmin-%Sseg')
echo $date

mysqldump -u$DB_USERNAME -p$DB_PASSWORD $DB_DATABASE > $URL_BACKUPS/escola_presente_$date.sql
cd $URL_BACKUPS
zip escola_presente_$date.sql.zip escola_presente_$date.sql
rm escola_presente_$date.sql
