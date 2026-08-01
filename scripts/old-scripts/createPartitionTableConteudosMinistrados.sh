dir="$PWD"
source $dir/.env

database="$DB_DATABASE"
database_user="$DB_USERNAME"
user_password="$DB_PASSWORD"

$URL_MYSQL -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD -D $DB_DATABASE -e "
SELECT 'Gerando tabela Conteúdos Ministrados particionada' as 'EXECUTANDO...';

CREATE TABLE adm_conteudosministrados_part (
  id bigint(20) UNSIGNED NOT NULL,
  unidade_id bigint(20) UNSIGNED NOT NULL,
  data date NOT NULL,
  conteudo text DEFAULT NULL,
  user_id bigint(20) UNSIGNED NOT NULL,
  calendario_id bigint(20) UNSIGNED NOT NULL,
  escola_id bigint(20) UNSIGNED NOT NULL,
  etapa_id bigint(20) UNSIGNED NOT NULL,
  serie_id bigint(20) UNSIGNED NOT NULL,
  turno_id bigint(20) UNSIGNED NOT NULL,
  turma_id bigint(20) UNSIGNED NOT NULL,
  disciplina_id bigint(20) UNSIGNED NOT NULL,
  qtd_aulas int(11) DEFAULT NULL,
  id_clonado bigint(20) UNSIGNED DEFAULT NULL,
  ator bigint(20) UNSIGNED NOT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  deleted tinyint(1) DEFAULT 0,
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

INSERT INTO adm_conteudosministrados_part
SELECT * FROM adm_conteudosministrados;
RENAME TABLE adm_conteudosministrados TO adm_conteudosministrados_old, adm_conteudosministrados_part TO adm_conteudosministrados;

CREATE INDEX idx_unidade_id ON adm_conteudosministrados (unidade_id);
CREATE INDEX idx_data ON adm_conteudosministrados (data);
CREATE INDEX idx_escola_id ON adm_conteudosministrados (escola_id);
CREATE INDEX idx_etapa_id ON adm_conteudosministrados (etapa_id);
CREATE INDEX idx_serie_id ON adm_conteudosministrados (serie_id);
CREATE INDEX idx_turno_id ON adm_conteudosministrados (turno_id);
CREATE INDEX idx_turma_id ON adm_conteudosministrados (turma_id);
CREATE INDEX idx_user_id ON adm_conteudosministrados (user_id);
CREATE INDEX idx_disciplina_id ON adm_conteudosministrados (disciplina_id);
CREATE INDEX idx_deleted ON adm_conteudosministrados (deleted);

"