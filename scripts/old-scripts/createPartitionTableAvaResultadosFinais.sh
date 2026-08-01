dir="$PWD"
source $dir/.env

database="$DB_DATABASE"
database_user="$DB_USERNAME"
user_password="$DB_PASSWORD"

$URL_MYSQL -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD -D $DB_DATABASE -e "
SELECT 'Gerando tabela particionada Avaliações Resultados FInais' as 'EXECUTANDO...';

CREATE TABLE adm_avaliacoes_resultados_finais_part (
  id bigint(20) UNSIGNED NOT NULL,
  calendario_id bigint(20) UNSIGNED NOT NULL,
  serie_id bigint(20) UNSIGNED NOT NULL,
  user_id bigint(20) UNSIGNED NOT NULL,
  disciplina_id bigint(20) UNSIGNED NOT NULL,
  media_anual double(5,2) DEFAULT NULL,
  recuperacao_final double(5,2) DEFAULT NULL,
  media_final double(5,2) DEFAULT NULL,
  ator int(11) NOT NULL,
  situacaofinaldetalhe_id bigint(20) UNSIGNED DEFAULT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
      PRIMARY KEY (id, calendario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
PARTITION BY LIST (calendario_id) 
(
  PARTITION p1 VALUES IN (1),
  PARTITION p2 VALUES IN (2),
  PARTITION p3 VALUES IN (3),
  PARTITION p4 VALUES IN (4),
  PARTITION p5 VALUES IN (5),
  PARTITION p6 VALUES IN (6),
  PARTITION p7 VALUES IN (7),
  PARTITION p8 VALUES IN (8),
  PARTITION p9 VALUES IN (9),
  PARTITION p10 VALUES IN (10)
);

INSERT INTO adm_avaliacoes_resultados_finais_part
SELECT * FROM adm_avaliacoes_resultados_finais;
RENAME TABLE adm_avaliacoes_resultados_finais TO adm_avaliacoes_resultados_finais_old, adm_avaliacoes_resultados_finais_part TO adm_avaliacoes_resultados_finais;

CREATE INDEX idx_serie_id ON adm_avaliacoes_resultados_finais (serie_id);
CREATE INDEX idx_user_id ON adm_avaliacoes_resultados_finais (user_id);
CREATE INDEX idx_disciplina_id ON adm_avaliacoes_resultados_finais (disciplina_id);
CREATE INDEX idx_situacaofinaldetalhe_id ON adm_avaliacoes_resultados_finais (situacaofinaldetalhe_id);

"