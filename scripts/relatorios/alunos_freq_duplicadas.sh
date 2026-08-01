clear
dir="$PWD"
source $dir/.env

file=$(basename "$1")
SQL=$(echo $file | sed "s/.gz//")

database="$DB_DATABASE"
database_user="$DB_USERNAME"
user_password="$DB_PASSWORD"

echo "gerando relatório de alunos com frequências duplicadas..."

/opt/lampp/bin/mysql -u $database_user -p$user_password --column-names -e "
USE $database;
SELECT e.nome as escola, u.name as aluno, GROUP_CONCAT(DISTINCT data) as datas, total_escolas
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
GROUP BY aluno_id
ORDER BY escola;
SELECT ROW_COUNT() AS linhas_afetadas;
" > $dir/scripts/csvs/relatorios/alunos_freq_duplicadas.csv