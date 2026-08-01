dir="$PWD"
source $dir/.env

database="$DB_DATABASE"
database_user="$DB_USERNAME"
user_password="$DB_PASSWORD"

$URL_MYSQL -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD -D $DB_DATABASE -e "
SELECT 'Gerando tabela Consolidado Freq particionada' as 'EXECUTANDO...';

CREATE TABLE adm_frequencia_consolidado_part (
  id bigint(20) UNSIGNED NOT NULL,
  calendario_id int(11) NOT NULL,
  aluno_id bigint(20) NOT NULL,
  turma_id bigint(20) DEFAULT NULL,
  serie_id int(11) DEFAULT NULL,
  disciplina_id int(11) DEFAULT NULL,
  matriz_id int(11) DEFAULT NULL,
  cha int(11) DEFAULT NULL,
  aulas_registradas int(11) DEFAULT NULL,
  total_faltas int(11) NOT NULL,
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

INSERT INTO adm_frequencia_consolidado_part
SELECT * FROM adm_frequencia_consolidado;
RENAME TABLE adm_frequencia_consolidado TO adm_frequencia_consolidado_old, adm_frequencia_consolidado_part TO adm_frequencia_consolidado;

CREATE INDEX idx_aluno_id ON adm_frequencia_consolidado (aluno_id);
CREATE INDEX idx_turma_id ON adm_frequencia_consolidado (turma_id);
CREATE INDEX idx_serie_id ON adm_frequencia_consolidado (serie_id);
CREATE INDEX idx_disciplina_id ON adm_frequencia_consolidado (disciplina_id);
CREATE INDEX idx_matriz_id ON adm_frequencia_consolidado (matriz_id);
CREATE INDEX idx_cha ON adm_frequencia_consolidado (cha);
CREATE INDEX idx_aulas_registradas ON adm_frequencia_consolidado (aulas_registradas);
CREATE INDEX idx_total_faltas ON adm_frequencia_consolidado (total_faltas); 

"