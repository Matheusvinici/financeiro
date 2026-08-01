dir="$PWD"
source $dir/.env

database="$DB_DATABASE"
database_user="$DB_USERNAME"
user_password="$DB_PASSWORD"

$URL_MYSQL -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD -D $DB_DATABASE -e "
SELECT 'Gerando tabela particionada Aulas' as 'EXECUTANDO...';

CREATE TABLE adm_aulas_part (
  id bigint(20) UNSIGNED NOT NULL,
  calendario_id bigint(20) UNSIGNED NOT NULL,
  professor_id bigint(20) UNSIGNED NOT NULL,
  turma_id bigint(20) UNSIGNED NOT NULL,
  serie_id bigint(20) UNSIGNED NOT NULL,
  disciplina_id int(11) DEFAULT NULL,
  periodo int(11) DEFAULT NULL,
  data date NOT NULL,
  aulas int(11) DEFAULT NULL,
  id_clonado bigint(20) UNSIGNED DEFAULT NULL,
  ator int(11) NOT NULL,
  deleted tinyint(1) DEFAULT 0,
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

INSERT INTO adm_aulas_part
SELECT * FROM adm_aulas;
RENAME TABLE adm_aulas TO adm_aulas_old, adm_aulas_part TO adm_aulas;

CREATE INDEX idx_professor_id ON adm_aulas (professor_id);
CREATE INDEX idx_turma_id ON adm_aulas (turma_id);
CREATE INDEX idx_serie_id ON adm_aulas (serie_id);
CREATE INDEX idx_disciplina_id ON adm_aulas (disciplina_id);
CREATE INDEX idx_periodo ON adm_aulas (periodo);
CREATE INDEX idx_data ON adm_aulas (data);
CREATE INDEX idx_deleted ON adm_aulas (deleted);
CREATE INDEX idx_id_clonado ON adm_aulas (id_clonado);

"