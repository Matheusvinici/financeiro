echo $1

if [ -z "$1" ]
then
    echo "Qual arquivo a restaurar?"
    exit 127;
else
    echo "Ok vou restaurar as tabelas para "$1".";
fi

hora_atual=$(date +"%H:%M:%S")
echo "Início: $hora_atual"

echo "Excluindo tabelas adm_ localmente";

/opt/lampp/bin/mysql ep -u root -proot -e "
SET FOREIGN_KEY_CHECKS = 0;
SET @Drop_Stm = CONCAT('DROP TABLE ', (
      SELECT GROUP_CONCAT(TABLE_NAME) AS All_Tables FROM information_schema.tables
      WHERE TABLE_NAME LIKE 'adm_%' AND TABLE_SCHEMA = 'ep'
));
PREPARE Stm FROM @Drop_Stm;
EXECUTE Stm;
DEALLOCATE PREPARE Stm;
SET FOREIGN_KEY_CHECKS = 1;
"

diretorio=$(dirname "$1")

unzip "$1" -d $diretorio
SQL=$(echo "$1" | sed "s/.zip//")

# gunzip -k "$1"
# SQL=$(echo "$1" | sed "s/.gz//")

echo "Restaurando adm_ tables localmente";

/opt/lampp/bin/mysql -u root -proot ep < "$SQL" &&
rm $diretorio/SQL

bash clearCaches.sh

hora_atual=$(date +"%H:%M:%S")
echo "Fim: $hora_atual"
