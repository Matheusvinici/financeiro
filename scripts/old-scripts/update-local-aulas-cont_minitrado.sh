pass="root"
database="ep"
/opt/lampp/bin/mysql $database -u root -p$pass -e "

# atualiza aulas na tabela adm_conteudosministrados
UPDATE adm_conteudosministrados
SET qtd_aulas = (
    SELECT COUNT(*)
    FROM adm_horariodeaulas
    INNER JOIN adm_horarios ON adm_horariodeaulas.horario_id = adm_horarios.id
    WHERE
        adm_horariodeaulas.turma_id = adm_conteudosministrados.turma_id AND
        adm_horariodeaulas.serie_id = adm_conteudosministrados.serie_id AND
        adm_horariodeaulas.disciplina_id = adm_conteudosministrados.disciplina_id AND
        CASE DAYOFWEEK(adm_conteudosministrados.data)
            WHEN 2 THEN 1  -- Segunda-feira
            WHEN 3 THEN 2  -- Terça-feira
            WHEN 4 THEN 3  -- Quarta-feira
            WHEN 5 THEN 4  -- Quinta-feira
            WHEN 6 THEN 5  -- Sexta-feira
            ELSE NULL
        END = adm_horariodeaulas.dia
        AND adm_horarios.ativo = 1
)
WHERE EXISTS (
    SELECT 1
    FROM adm_horariodeaulas
    INNER JOIN adm_horarios ON adm_horariodeaulas.horario_id = adm_horarios.id
    WHERE
        adm_horariodeaulas.turma_id = adm_conteudosministrados.turma_id AND
        adm_horariodeaulas.serie_id = adm_conteudosministrados.serie_id AND
        adm_horariodeaulas.disciplina_id = adm_conteudosministrados.disciplina_id AND
        CASE DAYOFWEEK(adm_conteudosministrados.data)
            WHEN 2 THEN 1  -- Segunda-feira
            WHEN 3 THEN 2  -- Terça-feira
            WHEN 4 THEN 3  -- Quarta-feira
            WHEN 5 THEN 4  -- Quinta-feira
            WHEN 6 THEN 5  -- Sexta-feira
            ELSE NULL
        END = adm_horariodeaulas.dia
        AND adm_horarios.ativo = 1)
and disciplina_id is not null
and qtd_aulas is null;

# atualiza aulas na tabela adm_conteudosministrados sábados
UPDATE adm_conteudosministrados
SET qtd_aulas = 2
where DAYOFWEEK(adm_conteudosministrados.data) = 7
and disciplina_id is not null
and qtd_aulas is null;
"
