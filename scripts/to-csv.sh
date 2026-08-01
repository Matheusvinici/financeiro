dir="$PWD"
source $dir/.env

database="$DB_DATABASE"
database_user="$DB_USERNAME"
pass="$DB_PASSWORD"

/opt/lampp/bin/mysql $database -u $database_user -p$pass -e "

WITH esgoto_long AS (
  -- Categoria: Rede Pública
  SELECT
    CO_ENTIDADE,
    e.nome AS nome_escola,
    'Esgotamento Sanitário' AS tipo_esgoto,
    IN_ESGOTO_REDE_PUBLICA AS possui
  FROM adm_microdados_ed_basica_2024 mi
  JOIN adm_escolas e ON e.codigo = mi.CO_ENTIDADE
  WHERE IN_ESGOTO_REDE_PUBLICA = 1
  OR IN_ESGOTO_FOSSA_SEPTICA = 1

  UNION ALL

  -- Categoria unificada: Fossa (qualquer tipo: Séptica, Comum ou Rudimentar)
  SELECT
    CO_ENTIDADE,
    e.nome AS nome_escola,
    'Fossa' AS tipo_esgoto,
    1 AS possui  -- Atribui 1 para qualquer fossa detectada
  FROM adm_microdados_ed_basica_2024 mi
  JOIN adm_escolas e ON e.codigo = mi.CO_ENTIDADE
  WHERE 
     IN_ESGOTO_FOSSA_COMUM = 1 
     OR IN_ESGOTO_FOSSA = 1
)

-- Agrupamento por escola com GROUP_CONCAT
SELECT
  CO_ENTIDADE,
  nome_escola,
  GROUP_CONCAT(tipo_esgoto SEPARATOR ', ') AS tipos_esgoto_concatenados
FROM esgoto_long
GROUP BY CO_ENTIDADE, nome_escola
ORDER BY  tipo_esgoto, nome_escola;
" > relatorio_esgoto_por_escola.csv

/opt/lampp/bin/mysql $database -u $database_user -p$pass -e "
WITH esgoto_long AS (
  -- Prioridade 1: Todas com Rede Pública (inclui eventuais com fossa também)
  SELECT
    CO_ENTIDADE,
    e.nome AS nome_escola,
    'Rede Pública' AS tipo_esgoto,
    1 AS possui
  FROM adm_microdados_ed_basica_2024 mi
  JOIN adm_escolas e ON e.codigo = mi.CO_ENTIDADE
  WHERE IN_ESGOTO_REDE_PUBLICA = 1
  OR IN_ESGOTO_FOSSA_SEPTICA = 1 

  UNION ALL

  -- Prioridade 2: Só Fossa se NÃO tiver Rede Pública
  SELECT
    CO_ENTIDADE,
    e.nome AS nome_escola,
    'Fossa' AS tipo_esgoto,
    1 AS possui
  FROM adm_microdados_ed_basica_2024 mi
  JOIN adm_escolas e ON e.codigo = mi.CO_ENTIDADE
  WHERE (IN_ESGOTO_FOSSA_COMUM = 1 OR IN_ESGOTO_FOSSA = 1)
    AND IN_ESGOTO_REDE_PUBLICA = 0  -- Exclui as que já foram capturadas acima
),

-- Total geral de escolas únicas (para %)
total_escolas AS (
  SELECT COUNT(DISTINCT CO_ENTIDADE) AS total_geral
  FROM adm_microdados_ed_basica_2024 mi
  JOIN adm_escolas e ON e.codigo = mi.CO_ENTIDADE
  WHERE TP_SITUACAO_FUNCIONAMENTO = 1  -- Escolas ativas
)

-- Relatório: Totais e percentuais
SELECT
  el.tipo_esgoto,
  COUNT(DISTINCT el.CO_ENTIDADE) AS total_escolas_tipo,
  ROUND(COUNT(DISTINCT el.CO_ENTIDADE) * 100.0 / t.total_geral, 2) AS percentual
FROM esgoto_long el
CROSS JOIN total_escolas t
GROUP BY el.tipo_esgoto, t.total_geral
ORDER BY total_escolas_tipo DESC;" > relatorio_esgoto_geral.csv