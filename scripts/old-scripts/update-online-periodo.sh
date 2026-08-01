mysql juazeiro33 -u juazeiro33 -pGogo1352 -e "

UPDATE adm_frequencias
SET periodo = CASE
			WHEN turno_id = 1 THEN 1
			WHEN turno_id = 2 THEN 2
			WHEN turno_id = 3 THEN 3
END
where turno_id <> 4;

# UPDATE  adm_frequencias  f
# join adm_horariodeaulas ha on ha.disciplina_id = f.disciplina_id
# join adm_horarios h on h.id = ha.horario_id
# SET  periodo = CASE WHEN TIME(horai) <= '12:00:00' THEN 1 WHEN TIME(horai) <= '18:00:00' THEN 2 ELSE 3 END
# WHERE f.turno_id = 4
# and ha.turma_id = f.turma_id
# and ha.serie_id = f.serie_id;

"
