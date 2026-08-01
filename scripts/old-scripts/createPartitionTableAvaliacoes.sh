dir="$PWD"
source $dir/.env

database="$DB_DATABASE"
database_user="$DB_USERNAME"
user_password="$DB_PASSWORD"

$URL_MYSQL -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD -D $DB_DATABASE -e "
SELECT 'Gerando tabela Avaliações particionada' as 'EXECUTANDO...';

CREATE TABLE adm_avaliacoes_part (
  id BIGINT(20) UNSIGNED NOT NULL,
  calendario_id BIGINT(20) UNSIGNED NOT NULL,
  unidade_id BIGINT(20) UNSIGNED NOT NULL,
  serie_id BIGINT(20) UNSIGNED NOT NULL,
  user_id BIGINT(20) UNSIGNED NOT NULL,
  disciplina_id BIGINT(20) UNSIGNED NOT NULL,
  atv1 DOUBLE(5,2) DEFAULT NULL,
  atv2 DOUBLE(5,2) DEFAULT NULL,
  atv3 DOUBLE(5,2) DEFAULT NULL,
  Natv1 VARCHAR(1) DEFAULT NULL,
  Natv2 VARCHAR(1) DEFAULT NULL,
  Natv3 VARCHAR(1) DEFAULT NULL,
  media DOUBLE(5,2) DEFAULT NULL,
  recuperacao DOUBLE(5,2) DEFAULT NULL,
  media_unidade DOUBLE(5,2) DEFAULT NULL,
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

INSERT INTO adm_avaliacoes_part
SELECT * FROM adm_avaliacoes;
RENAME TABLE adm_avaliacoes TO adm_avaliacoes_old, adm_avaliacoes_part TO adm_avaliacoes;

CREATE INDEX idx_unidade_id ON adm_avaliacoes (unidade_id);
CREATE INDEX idx_serie_id ON adm_avaliacoes (serie_id);
CREATE INDEX idx_user_id ON adm_avaliacoes (user_id);
CREATE INDEX idx_disciplina_id ON adm_avaliacoes (disciplina_id);
CREATE INDEX idx_habilidade_id ON adm_avaliacoes (habilidade_id);

"