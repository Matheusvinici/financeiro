    hora_inicial=$(date +"%H:%M:%S")
    echo "Executando inicada às: $hora_inicial"


script_name=$(basename "$0")
# echo "Nome do arquivo: $script_name"
script_path=$(readlink -f "$0")
dir=$(echo "$script_path" | sed "s;$script_name;;")
# echo $dir
source $dir../../.env

$URL_MYSQL -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE -e "

############# limpa tabelas adm_avaliações e adm_avaliacoes_resultados_finais de registros nulos

DELETE FROM adm_avaliacoes 
WHERE atv1 is null
and atv2 is null
and atv3 is null
and media is null
and recuperacao is null
and media_unidade is null
and habilidade_id is null
and conceito is null;

DELETE FROM adm_avaliacoes_resultados_finais
WHERE media_anual is null
and recuperacao_final is null
and media_final is null;

DROP TABLE IF EXISTS _torestore_adm_atividadescomplementares;
DROP TABLE IF EXISTS _torestore_adm_avaliacoes;
DROP TABLE IF EXISTS _torestore_adm_avaliacoes_resultados_finais;
DROP TABLE IF EXISTS _torestore_adm_conteudosministrados;
DROP TABLE IF EXISTS _torestore_adm_frequencias;

CREATE TABLE _torestore_adm_atividadescomplementares LIKE adm_atividadescomplementares;
CREATE TABLE _torestore_adm_avaliacoes LIKE adm_avaliacoes;
CREATE TABLE _torestore_adm_avaliacoes_resultados_finais LIKE adm_avaliacoes_resultados_finais;
CREATE TABLE _torestore_adm_conteudosministrados LIKE adm_conteudosministrados;
CREATE TABLE _torestore_adm_frequencias LIKE adm_frequencias;

SELECT '' as 'Atividades complementares';
# Atividades complementares
INSERT INTO _torestore_adm_atividadescomplementares
SELECT *
FROM tmp_adm_atividadescomplementares
WHERE ( escola_id, professor_id, disciplina_id, data ) NOT IN (
    SELECT escola_id, professor_id, disciplina_id, data
    FROM adm_atividadescomplementares
    GROUP BY escola_id, professor_id, disciplina_id, data
);

SELECT '' as 'Avaliacoes';
# Avaliacoes
INSERT INTO _torestore_adm_avaliacoes
SELECT *
FROM tmp_adm_avaliacoes
WHERE ( calendario_id, unidade_id, serie_id, user_id, disciplina_id ) NOT IN (
    SELECT calendario_id, unidade_id, serie_id, user_id, disciplina_id
    FROM adm_avaliacoes
    GROUP BY calendario_id, unidade_id, serie_id, user_id, disciplina_id
);

SELECT '' 'Resutados finais';
# Resutados finais
INSERT INTO _torestore_adm_avaliacoes_resultados_finais
SELECT *
FROM tmp_adm_avaliacoes_resultados_finais
WHERE ( calendario_id, serie_id, user_id, disciplina_id ) NOT IN (
    SELECT calendario_id, serie_id, user_id, disciplina_id
    FROM adm_avaliacoes_resultados_finais
    GROUP BY calendario_id, serie_id, user_id, disciplina_id
);

SELECT '' as 'conteudos ministrados';
# conteudos ministrados
INSERT INTO _torestore_adm_conteudosministrados
SELECT *
FROM tmp_adm_conteudosministrados
WHERE ( data, turma_id, serie_id, disciplina_id ) NOT IN (
    SELECT data, turma_id, serie_id, disciplina_id
    FROM adm_conteudosministrados
    GROUP BY data, turma_id, serie_id, disciplina_id
);

SELECT '' as 'frequencias';
# frequencias
INSERT INTO _torestore_adm_frequencias
SELECT *
FROM tmp_adm_frequencias
WHERE ( calendario_id, aluno_id, data, turma_id, serie_id, disciplina_id, periodo ) NOT IN (
    SELECT calendario_id, aluno_id, data, turma_id, serie_id, disciplina_id, periodo
    FROM adm_frequencias
    where updated_at >= '2023-09-29 00:00:00'
    GROUP BY calendario_id, aluno_id, data, turma_id, serie_id, disciplina_id, periodo
);

INSERT INTO adm_atividadescomplementares (calendario_id, escola_id, professor_id, serie_id, disciplina_id, ator, data, atividade, created_at, updated_at)
SELECT calendario_id, escola_id, professor_id, serie_id, disciplina_id, ator, data, atividade, created_at, updated_at
FROM _torestore_adm_atividadescomplementares;

UPDATE adm_atividadescomplementares ac
join tmp_adm_atividadescomplementares tmp_ac 
ON ac.calendario_id = tmp_ac.calendario_id
AND ac.escola_id = tmp_ac.escola_id
AND ac.professor_id = tmp_ac.professor_id
AND ac.disciplina_id = tmp_ac.disciplina_id
AND ac.data = tmp_ac.data
AND ac.updated_at < tmp_ac.updated_at
SET
ac.atividade = tmp_ac.atividade;

###############################################################################
INSERT INTO adm_avaliacoes (calendario_id, unidade_id, serie_id, user_id, disciplina_id, atv1, atv2, atv3, media, recuperacao, media_unidade, habilidade_id, conceito, ator, created_at, updated_at)
SELECT calendario_id, unidade_id, serie_id, user_id, disciplina_id, atv1, atv2, atv3, media, recuperacao, media_unidade, habilidade_id, conceito, ator, created_at, updated_at
FROM _torestore_adm_avaliacoes;

UPDATE adm_avaliacoes ava
join tmp_adm_avaliacoes tmp_ava 
ON ava.calendario_id = tmp_ava.calendario_id
AND ava.unidade_id = tmp_ava.unidade_id
AND ava.serie_id = tmp_ava.serie_id
AND ava.user_id = tmp_ava.user_id
AND ava.disciplina_id = tmp_ava.disciplina_id
AND ava.updated_at < tmp_ava.updated_at
SET
ava.atv1 = tmp_ava.atv1,
ava.atv2 = tmp_ava.atv2,
ava.atv3 = tmp_ava.atv3,
ava.media = tmp_ava.media,
ava.recuperacao = tmp_ava.recuperacao,
ava.media_unidade = tmp_ava.media_unidade,
ava.habilidade_id = tmp_ava.habilidade_id,
ava.conceito = tmp_ava.conceito,
ava.ator = tmp_ava.ator;

###############################################################################
INSERT INTO adm_avaliacoes_resultados_finais (calendario_id, serie_id, user_id, disciplina_id, media_anual, recuperacao_final, media_final, ator, created_at, updated_at)
SELECT calendario_id, serie_id, user_id, disciplina_id, media_anual, recuperacao_final, media_final, ator, created_at, updated_at
FROM _torestore_adm_avaliacoes_resultados_finais;

UPDATE adm_avaliacoes_resultados_finais ref
join tmp_adm_avaliacoes_resultados_finais tmp_ref 
ON ref.calendario_id = tmp_ref.calendario_id
AND ref.serie_id = tmp_ref.serie_id
AND ref.user_id = tmp_ref.user_id
AND ref.disciplina_id = tmp_ref.disciplina_id
AND ref.updated_at < tmp_ref.updated_at
SET
ref.media_anual = tmp_ref.media_anual,
ref.recuperacao_final = tmp_ref.recuperacao_final,
ref.media_final = tmp_ref.media_final,
ref.ator = tmp_ref.ator;

###############################################################################
INSERT INTO adm_conteudosministrados (unidade_id, data, conteudo, user_id, calendario_id, escola_id, etapa_id, serie_id, turno_id, turma_id, disciplina_id, qtd_aulas, ator, created_at, updated_at, deleted)
SELECT unidade_id, data, conteudo, user_id, calendario_id, escola_id, etapa_id, serie_id, turno_id, turma_id, disciplina_id, qtd_aulas, ator, created_at, updated_at, deleted
FROM _torestore_adm_conteudosministrados;

UPDATE adm_conteudosministrados cont_minist
join tmp_adm_conteudosministrados tmp_cont_minist 
ON cont_minist.calendario_id = tmp_cont_minist.calendario_id
AND cont_minist.data = tmp_cont_minist.data
AND cont_minist.turma_id = tmp_cont_minist.turma_id
AND cont_minist.serie_id = tmp_cont_minist.serie_id
AND cont_minist.disciplina_id = tmp_cont_minist.disciplina_id
AND cont_minist.updated_at < tmp_cont_minist.updated_at
SET
cont_minist.conteudo = tmp_cont_minist.conteudo,
cont_minist.qtd_aulas = tmp_cont_minist.qtd_aulas,
cont_minist.data = tmp_cont_minist.data;

##############################################################################
INSERT INTO adm_frequencias (calendario_id, escola_id, professor_id, aluno_id, turma_id, turno_id, etapa_id, serie_id, disciplina_id, periodo, data, aulas, faltas, justificativa_id, ator, created_at, updated_at, deleted)
SELECT calendario_id, escola_id, professor_id, aluno_id, turma_id, turno_id, etapa_id, serie_id, disciplina_id, periodo, data, aulas, faltas, justificativa_id, ator, created_at, updated_at, deleted
FROM _torestore_adm_frequencias;

# UPDATE adm_frequencias freq
# join tmp_adm_frequencias tmp_freq 
# ON freq.calendario_id = tmp_freq.calendario_id
# AND freq.aluno_id = tmp_freq.aluno_id
# AND freq.data = tmp_freq.data
# AND freq.turma_id = tmp_freq.turma_id
# AND freq.serie_id = tmp_freq.serie_id
# AND freq.disciplina_id = tmp_freq.disciplina_id
# AND freq.periodo = tmp_freq.periodo
# AND freq.updated_at < tmp_freq.updated_at
# AND freq.updated_at >= '2023-09-29 00:00:00'
# SET
# freq.periodo = tmp_freq.periodo,
# freq.aulas = tmp_freq.aulas,
# freq.faltas = tmp_freq.faltas,
# freq.justificativa_id = tmp_freq.justificativa_id,
# freq.ator = tmp_freq.ator,
# freq.deleted = tmp_freq.deleted;

############# limpa tabelas adm_avaliações e adm_avaliacoes_resultados_finais de registros nulos

DELETE FROM adm_avaliacoes 
WHERE atv1 is null
and atv2 is null
and atv3 is null
and media is null
and recuperacao is null
and media_unidade is null
and habilidade_id is null
and conceito is null;

DELETE FROM adm_avaliacoes_resultados_finais
WHERE media_anual is null
and recuperacao_final is null
and media_final is null;

"
    hora_final=$(date +"%H:%M:%S")
    echo "Terminado às: $hora_final"