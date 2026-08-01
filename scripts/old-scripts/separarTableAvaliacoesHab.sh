dir="$PWD"
source $dir/.env

database="$DB_DATABASE"
database_user="$DB_USERNAME"
user_password="$DB_PASSWORD"

$URL_MYSQL -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD -D $DB_DATABASE -e "
SELECT 'Gerando tabela particionada' as 'EXECUTANDO...';

CREATE TABLE adm_avaliacoes_hab (
  id BIGINT(20) UNSIGNED NOT NULL,
  calendario_id BIGINT(20) UNSIGNED NOT NULL,
  unidade_id BIGINT(20) UNSIGNED NOT NULL,
  serie_id BIGINT(20) UNSIGNED NOT NULL,
  user_id BIGINT(20) UNSIGNED NOT NULL,
  disciplina_id BIGINT(20) UNSIGNED NOT NULL,
  habilidade_id BIGINT(20) UNSIGNED DEFAULT NULL,
  conceito VARCHAR(255) DEFAULT NULL,
  ator INT(11) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
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

INSERT INTO adm_avaliacoes_hab
SELECT 
  id, calendario_id, unidade_id, serie_id, user_id, disciplina_id, habilidade_id, conceito, ator, created_at, updated_at

FROM adm_avaliacoes
WHERE habilidade_id IS NOT NULL;

delete from adm_avaliacoes where habilidade_id IS NOT NULL;

CREATE INDEX idx_unidade_id ON adm_avaliacoes_hab (unidade_id);
CREATE INDEX idx_serie_id ON adm_avaliacoes_hab (serie_id);
CREATE INDEX idx_user_id ON adm_avaliacoes_hab (user_id);
CREATE INDEX idx_disciplina_id ON adm_avaliacoes_hab (disciplina_id);
CREATE INDEX idx_habilidade_id ON adm_avaliacoes_hab (habilidade_id);

ALTER TABLE adm_avaliacoes DROP COLUMN conceito;
ALTER TABLE adm_avaliacoes DROP COLUMN habilidade_id;

"