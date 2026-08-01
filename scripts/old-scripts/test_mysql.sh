pass="root"
database="ep"
/opt/lampp/bin/mysql $database -u root -p$pass -e "
INSERT INTO adm_frequencia_consolidado (calendario_id, aluno_id, turma_id, serie_id, disciplina_id, total_faltas)
select adm_aulas.calendario_id as calendario_id, 
adm_f.aluno_id as aluno_id, 
adm_at.turma_id, 
adm_m.serie_id, 
adm_aulas.disciplina_id as disciplina_id, 
SUM(adm_f.faltas) AS total_faltas from adm_aulas 
inner join adm_frequencias as adm_f on adm_f.aula_id = adm_aulas.id 
inner join adm_matriculas as adm_m on adm_m.user_id = adm_f.aluno_id 
inner join adm_aluno_turma as adm_at on adm_at.user_id = adm_m.user_id 
where adm_f.justificativa_id is null 
and adm_aulas.disciplina_id is not null 
and adm_m.calendario_id = 2 
and statusmatricula_id = 1 
and adm_at.ativo = 1 
and adm_m.serie_id in (12, 13, 14, 15, 19, 20) 
group by adm_f.aluno_id, adm_aulas.disciplina_id
"