clear
dir="$PWD"
source $dir/.env

file=$(basename "$1")
SQL=$(echo $file | sed "s/.gz//")

database="$DB_DATABASE"
database_user="$DB_USERNAME"
user_password="$DB_PASSWORD"
mysql_path=$URL_MYSQL

echo "gerando script para resolver duplicidades..."

$mysql_path -h $DB_HOST -P 3306 -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE --column-names -e "
USE $database;
SELECT 
    CONCAT(
        \"UPDATE adm_frequencias JOIN adm_aulas a ON a.id = aula_id JOIN adm_turmas t ON t.id = a.turma_id SET adm_frequencias.deleted = 1 WHERE a.data IN (\",  GROUP_CONCAT(DISTINCT QUOTE(data)),\")\",  \" AND aluno_id = \",  base.aluno_id,  \" AND escola_id <> \",  m.escola_id, \";\" ) AS '-- queries'
FROM 
(
    SELECT 
    f.aluno_id, 
    data,
    COUNT(DISTINCT t.escola_id) AS total_escolas
FROM 
    adm_frequencias f
JOIN 
    adm_aulas a ON a.id = f.aula_id
JOIN 
    adm_turmas t ON t.id = a.turma_id
WHERE 
    a.calendario_id = 4
    AND t.calendario_id = 4
GROUP BY 
    f.aluno_id, a.data 
HAVING 
    total_escolas > 1

) AS base
JOIN adm_users u ON u.id = base.aluno_id
JOIN adm_matriculas m ON m.user_id = u.id
JOIN adm_escolas e ON e.id = m.escola_id
where m.calendario_id = 4
GROUP BY aluno_id;
# SELECT ROW_COUNT() AS linhas_afetadas;
" > $dir/scripts/csvs/alunos_freq_duplicadas.sql

echo "aplicando alterações às duplicadas..."

$mysql_path -h $DB_HOST -P 3306 -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE < $dir/scripts/csvs/alunos_freq_duplicadas.sql

rm $dir/scripts/csvs/alunos_freq_duplicadas.sql
echo "alterações aplicadas com sucesso!"