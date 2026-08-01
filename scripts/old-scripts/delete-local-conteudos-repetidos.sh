pass="root"
database="ep"
/opt/lampp/bin/mysql $database -u root -p$pass -e "

# EXPLAIN DELETE FROM adm_conteudosministrados 
# WHERE conteudo IS NULL
# AND (data, turma_id, serie_id, disciplina_id) IN (
#     SELECT data, turma_id, serie_id, disciplina_id
#     FROM adm_conteudosministrados
#     GROUP BY data, turma_id, serie_id, disciplina_id
#     HAVING COUNT(*) > 1
# );

Drop table IF EXISTS temp_table;
CREATE TABLE temp_table LIKE adm_conteudosministrados;

INSERT INTO temp_table
SELECT *
FROM adm_conteudosministrados
WHERE conteudo IS NOT NULL
OR (data, turma_id, serie_id, disciplina_id) NOT IN (
    SELECT data, turma_id, serie_id, disciplina_id
    FROM adm_conteudosministrados
    GROUP BY data, turma_id, serie_id, disciplina_id
    HAVING COUNT(*) > 1
);

# RENAME TABLE temp_table TO adm_conteudosministrados;

"
