dir="$PWD"
source $dir/.env

database="$DB_DATABASE"
database_user="$DB_USERNAME"
user_password="$DB_PASSWORD"

$URL_MYSQL -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD -D $DB_DATABASE -e "
SELECT 'Gerando tabela particionada Matriculas' as 'EXECUTANDO...';

CREATE TABLE adm_matriculas_part (
  id bigint(20) UNSIGNED NOT NULL,
  user_id bigint(20) UNSIGNED NOT NULL,
  responsavel_id bigint(20) UNSIGNED DEFAULT NULL,
  calendario_id bigint(20) UNSIGNED NOT NULL,
  matricula bigint(20) NOT NULL,
  escola_id bigint(20) UNSIGNED NOT NULL,
  etapa_id bigint(20) UNSIGNED NOT NULL,
  serie_id_base bigint(20) UNSIGNED DEFAULT NULL,
  serie_id bigint(20) UNSIGNED NOT NULL,
  turno_id bigint(20) UNSIGNED NOT NULL,
  statusmatricula_id bigint(20) UNSIGNED NOT NULL,
  escola_anterior varchar(255) NOT NULL,
  municipio_ultimo_ano_estudado bigint(20) DEFAULT NULL,
  estado_ultimo_ano_estudado bigint(20) DEFAULT NULL,
  rede_ensino_ultimo_ano_estudado varchar(255) DEFAULT NULL,
  ultimo_ano_estudado varchar(255) DEFAULT NULL,
  transporte_publico tinyint(1) NOT NULL,
  tipo_matricula tinyint(1) NOT NULL,
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

INSERT INTO adm_matriculas_part
SELECT * FROM adm_matriculas;
RENAME TABLE adm_matriculas TO adm_matriculas_old, adm_matriculas_part TO adm_matriculas;

CREATE INDEX idx_user_id ON adm_matriculas (user_id);

CREATE INDEX idx_responsavel_id ON adm_matriculas (responsavel_id);
CREATE INDEX idx_matricula ON adm_matriculas (matricula);
CREATE INDEX idx_escola_id ON adm_matriculas (escola_id);
CREATE INDEX idx_etapa_id ON adm_matriculas (etapa_id);
CREATE INDEX idx_serie_id ON adm_matriculas (serie_id);
CREATE INDEX idx_turno_id ON adm_matriculas (turno_id);
CREATE INDEX idx_statusmatricula_id ON adm_matriculas (statusmatricula_id);
CREATE INDEX idx_transporte_publico ON adm_matriculas (transporte_publico);

"