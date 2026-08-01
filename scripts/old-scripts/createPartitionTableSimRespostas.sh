dir="$PWD"
source $dir/.env

database="$DB_DATABASE"
database_user="$DB_USERNAME"
user_password="$DB_PASSWORD"

$URL_MYSQL -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD -D $DB_DATABASE -e "
SELECT 'Gerando tabela particionada Simulados Respostas' as 'EXECUTANDO...';

CREATE TABLE adm_sim_respostas_part (
  id bigint(20) UNSIGNED NOT NULL,
  simulado_id bigint(20) UNSIGNED NOT NULL,
  aluno_id bigint(20) UNSIGNED NOT NULL,
  item_id bigint(20) UNSIGNED NOT NULL,
  resposta varchar(255) DEFAULT NULL,
  professor_id bigint(20) UNSIGNED NOT NULL,
  ator bigint(20) UNSIGNED NOT NULL,
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id, simulado_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

PARTITION BY LIST (simulado_id) 
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
  PARTITION p10 VALUES IN (10),
  PARTITION p11 VALUES IN (11),
  PARTITION p12 VALUES IN (12),
  PARTITION p13 VALUES IN (13),
  PARTITION p14 VALUES IN (14),
  PARTITION p15 VALUES IN (15),
  PARTITION p16 VALUES IN (16),
  PARTITION p17 VALUES IN (17),
  PARTITION p18 VALUES IN (18),
  PARTITION p19 VALUES IN (19),
  PARTITION p20 VALUES IN (20),
  PARTITION p21 VALUES IN (21),
  PARTITION p22 VALUES IN (22),
  PARTITION p23 VALUES IN (23),
  PARTITION p24 VALUES IN (24),
  PARTITION p25 VALUES IN (25),
  PARTITION p26 VALUES IN (26),
  PARTITION p27 VALUES IN (27),
  PARTITION p28 VALUES IN (28),
  PARTITION p29 VALUES IN (29),
  PARTITION p30 VALUES IN (30)
);

INSERT INTO adm_sim_respostas_part
SELECT * FROM adm_sim_respostas;
RENAME TABLE adm_sim_respostas TO adm_sim_respostas_old, adm_sim_respostas_part TO adm_sim_respostas;

CREATE INDEX idx_simulado_id ON adm_sim_respostas (simulado_id);
CREATE INDEX idx_aluno_id ON adm_sim_respostas (aluno_id);
CREATE INDEX idx_professor_id ON adm_sim_respostas (professor_id);
CREATE INDEX idx_item_id ON adm_sim_respostas (item_id);
CREATE INDEX idx_ator ON adm_sim_respostas (ator);

"