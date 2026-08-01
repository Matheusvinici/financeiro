pass="root"
database="ep"
/opt/lampp/bin/mysql $database -u root -p$pass -e "
UPDATE adm_aulas a
JOIN turmas t ON a.turma_id = t.id
SET a.periodo = CASE
    WHEN t.turno_id = 1 THEN 1
    WHEN t.turno_id = 2 THEN 2
    WHEN t.turno_id = 3 THEN 3
END
WHERE t.turno_id <> 4;

# UPDATE  adm_frequencias  f
# join adm_horariodeaulas ha on ha.disciplina_id = f.disciplina_id
# join adm_horarios h on h.id = ha.horario_id
# SET  periodo = CASE WHEN TIME(horai) <= '12:00:00' THEN 1 WHEN TIME(horai) <= '18:00:00' THEN 2 ELSE 3 END
# WHERE f.turno_id = 4
# and ha.turma_id = f.turma_id
# and ha.serie_id = f.serie_id;

"
