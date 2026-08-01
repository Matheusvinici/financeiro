mysql juazeiro33 -u juazeiro33 -pGogo1352 -e "

# atualiza aulas na tabela frequencia
UPDATE adm_frequencias
SET aulas = (
    SELECT COUNT(*)
    FROM adm_horariodeaulas
    INNER JOIN adm_horarios ON adm_horariodeaulas.horario_id = adm_horarios.id
    WHERE
        adm_horariodeaulas.turma_id = adm_frequencias.turma_id AND
        adm_horariodeaulas.serie_id = adm_frequencias.serie_id AND
        adm_horariodeaulas.disciplina_id = adm_frequencias.disciplina_id AND
        CASE DAYOFWEEK(adm_frequencias.data)
            WHEN 2 THEN 1  -- Segunda-feira
            WHEN 3 THEN 2  -- Terça-feira
            WHEN 4 THEN 3  -- Quarta-feira
            WHEN 5 THEN 4  -- Quinta-feira
            WHEN 6 THEN 5  -- Sexta-feira
            ELSE NULL
        END = adm_horariodeaulas.dia AND
        CASE WHEN TIME(adm_horarios.horai) <= '12:00:00' THEN 1 WHEN TIME(horai) <= '18:00:00' THEN 2 ELSE 3 END = adm_frequencias.periodo
)
WHERE EXISTS (
    SELECT 1
    FROM adm_horariodeaulas
    INNER JOIN adm_horarios ON adm_horariodeaulas.horario_id = adm_horarios.id
    WHERE
        adm_horariodeaulas.turma_id = adm_frequencias.turma_id AND
        adm_horariodeaulas.serie_id = adm_frequencias.serie_id AND
        adm_horariodeaulas.disciplina_id = adm_frequencias.disciplina_id AND
        CASE DAYOFWEEK(adm_frequencias.data)
            WHEN 2 THEN 1  -- Segunda-feira
            WHEN 3 THEN 2  -- Terça-feira
            WHEN 4 THEN 3  -- Quarta-feira
            WHEN 5 THEN 4  -- Quinta-feira
            WHEN 6 THEN 5  -- Sexta-feira
            ELSE NULL
        END = adm_horariodeaulas.dia AND
        CASE WHEN TIME(adm_horarios.horai) <= '12:00:00' THEN 1 WHEN TIME(horai) <= '18:00:00' THEN 2 ELSE 3 END = adm_frequencias.periodo);

UPDATE adm_frequencias
SET aulas = 2
where DAYOFWEEK(adm_frequencias.data) = 7
and disciplina_id is not null;
"
