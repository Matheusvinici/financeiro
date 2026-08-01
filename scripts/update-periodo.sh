source .env

$URL_MYSQL -h $DB_HOST -P 3306 -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE -e "
##########################################
UPDATE adm_aulas a
JOIN adm_turmas t ON a.turma_id = t.id
SET a.periodo = CASE
    WHEN t.turno_id = 1 THEN 1
    WHEN t.turno_id = 2 THEN 2
    WHEN t.turno_id = 3 THEN 3
END
WHERE t.turno_id <> 4
and a.periodo IS NULL;

##########################################
UPDATE  adm_aulas  a
join adm_horariodeaulas ha on ha.disciplina_id = a.disciplina_id
join adm_horarios h on h.id = ha.horario_id
join adm_turmas t on t.id = a.turma_id
SET  periodo = CASE WHEN TIME(horai) <= '12:00:00' THEN 1 WHEN TIME(horai) <= '18:00:00' THEN 2 ELSE 3 END
WHERE t.turno_id = 4
and ha.turma_id = a.turma_id
and ha.serie_id = a.serie_id
and a.periodo IS NULL;

"