dir="$PWD"
source $dir/.env

database="$DB_DATABASE"
database_user="$DB_USERNAME"
user_password="$DB_PASSWORD"

$URL_MYSQL -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD -D $DB_DATABASE -e "
SELECT 'Gerando tabela Alunos Resultados Finais particionada Aulas' as 'EXECUTANDO...';

CREATE TABLE adm_alunosresultadosfinais_part (
  id bigint(20) UNSIGNED NOT NULL,
  calendario_id bigint(20) UNSIGNED NOT NULL,
  serie_id bigint(20) UNSIGNED NOT NULL,
  aluno_id bigint(20) UNSIGNED NOT NULL,
  situacaofinaldetalhe_id bigint(20) UNSIGNED NOT NULL,
  ator bigint(20) UNSIGNED NOT NULL,
  created_at timestamp NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT current_timestamp(),      
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

INSERT INTO adm_alunosresultadosfinais_part
SELECT * FROM adm_alunosresultadosfinais;
RENAME TABLE adm_alunosresultadosfinais TO adm_alunosresultadosfinais_old, adm_alunosresultadosfinais_part TO adm_alunosresultadosfinais;

CREATE INDEX idx_serie_id ON adm_alunosresultadosfinais (serie_id);
CREATE INDEX idx_aluno_id ON adm_alunosresultadosfinais (aluno_id);
CREATE INDEX idx_situacaofinaldetalhe_id ON adm_alunosresultadosfinais (situacaofinaldetalhe_id);
CREATE INDEX idx_ator ON adm_alunosresultadosfinais (ator);

"