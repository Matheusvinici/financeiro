dir="$PWD"
source $dir/.env

database="$DB_DATABASE"
database_user="$DB_USERNAME"
user_password="$DB_PASSWORD"

$URL_MYSQL -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD -D $DB_DATABASE -e "
SELECT 'Gerando tabela Frequencia particionada' as 'EXECUTANDO...';

ALTER TABLE adm_frequencias
ADD COLUMN IF NOT EXISTS calendario_id INT AFTER id;

DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS UpdateCalendarioIdInBatches()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE start_id BIGINT DEFAULT 1;
    DECLARE batch_size INT DEFAULT 100000;
    DECLARE max_id BIGINT;

    -- Obter o maior ID da tabela
    SELECT MAX(id) INTO max_id FROM adm_frequencias;

    WHILE done = 0 DO
        -- Atualizar registros no intervalo atual
        UPDATE adm_frequencias f
        JOIN adm_aulas a ON f.aula_id = a.id
        SET f.calendario_id = a.calendario_id
        WHERE f.id BETWEEN start_id AND (start_id + batch_size - 1);

        -- Incrementar para o próximo lote
        SET start_id = start_id + batch_size;

        -- Verificar se atingiu o fim
        IF start_id > max_id THEN
            SET done = 1;
        END IF;
    END WHILE;
END$$

DELIMITER ;

CALL UpdateCalendarioIdInBatches();

UPDATE adm_frequencias SET calendario_id = 2 
WHERE YEAR(created_at) = 2023
and calendario_id is null;

CREATE TABLE adm_frequencias_part (
  id bigint(20) UNSIGNED NOT NULL,
  calendario_id bigint(20) UNSIGNED NULL,
  aula_id bigint(20) UNSIGNED DEFAULT NULL,
  aluno_id bigint(20) UNSIGNED NOT NULL,
  aulas int(11) DEFAULT NULL,
  faltas int(11) NOT NULL,
  justificativa_id bigint(20) UNSIGNED DEFAULT NULL,
  ator int(11) NOT NULL,
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

INSERT INTO adm_frequencias_part
SELECT * FROM adm_frequencias;
RENAME TABLE adm_frequencias TO adm_frequencias_old, adm_frequencias_part TO adm_frequencias;

CREATE INDEX idx_aula_id ON adm_frequencias (aula_id);
CREATE INDEX idx_aluno_id ON adm_frequencias (aluno_id);
CREATE INDEX idx_justificativa_id ON adm_frequencias (justificativa_id);
CREATE INDEX idx_deleted ON adm_frequencias (deleted);

"