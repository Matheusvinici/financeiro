pass="root"
database="ep"
/opt/lampp/bin/mysql $database -u root -p$pass -e "
INSERT INTO adm_aluno_turma (
user_id, turma_id, ativo, created_at, updated_at
)
SELECT adm_frequencias.aluno_id, adm_frequencias.turma_id, 0, '2023-03-06 20:35:11', adm_frequencias.updated_at FROM adm_frequencias
left join adm_aluno_turma altu on altu.user_id =  adm_frequencias.aluno_id AND altu.turma_id = adm_frequencias.turma_id
where altu.user_id is null
group by adm_frequencias.aluno_id, adm_frequencias.turma_id
order by adm_frequencias.updated_at desc;

UPDATE adm_aluno_turma a
LEFT JOIN 0adm_exportar_alunos exp_a ON exp_a.adm_user_id = a.user_id AND exp_a.adm_turma_id = a.turma_id and a.ativo = 1
JOIN adm_turmas t ON t.id = a.turma_id
SET a.ativo = 0,
    a.updated_at = now()
WHERE exp_a.adm_user_id is null
and t.calendario_id = 2;
"
