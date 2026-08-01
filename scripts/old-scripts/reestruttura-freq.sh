source .env

    hora_inicial=$(date +"%H:%M:%S")
    echo "Script Inicado às: $hora_inicial"

mysql $DB_DATABASE -u $DB_USERNAME -p"$DB_PASSWORD" -e "
DROP TABLE IF EXISTS adm_aulas;
DROP TABLE IF EXISTS adm_frequencias;
"

mysql -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE < tmp/u325366976_juazeiroba.sql

    hora_final=$(date +"%H:%M:%S")
        echo "Script Inicado às: $hora_inicial"
        echo "Script finalizado às: $hora_final"
