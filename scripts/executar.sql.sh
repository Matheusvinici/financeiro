# loop
clear
dir="$PWD"
source $dir/.env

# $URL_MYSQL -u $DB_USERNAME -p$DB_PASSWORD -e "
# USE $DB_DATABASE;

# "

# bash $dir/scripts/turbinarMysql.sh

# bash $dir/scripts/createPartitionTableAvaResultadosFinais.sh

$URL_MYSQL -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD -D $DB_DATABASE -e "
SELECT '' as 'EXECUTANDO...';

# DELETE FROM adm_horariodeaulas 
# WHERE id NOT IN (
#     SELECT MIN(id)
#     FROM adm_horariodeaulas
#     GROUP BY turma_id, serie_id, horario_id, dia
# );

CREATE TEMPORARY TABLE temp_duplicatas (
    turma_id INT,
    serie_id INT,
    disciplina_id INT,
    horario_id INT,
    dia INT,
    total INT
);

INSERT INTO temp_duplicatas
SELECT turma_id, serie_id, disciplina_id, horario_id, dia, count(*) as total 
FROM adm_horariodeaulas
GROUP BY turma_id, serie_id, horario_id, dia
HAVING total > 1;

DELETE FROM adm_horariodeaulas
WHERE (turma_id, serie_id, horario_id, dia) IN (
    SELECT turma_id, serie_id, horario_id, dia
    FROM temp_duplicatas
) 
AND id NOT IN (
    SELECT MIN(id) FROM adm_horariodeaulas 
    GROUP BY turma_id, serie_id, horario_id, dia
);

DROP TEMPORARY TABLE IF EXISTS temp_duplicatas;

"