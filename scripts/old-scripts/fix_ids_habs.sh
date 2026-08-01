dir="$PWD"
source $dir/.env

database="$DB_DATABASE"
database_user="$DB_USERNAME"
user_password="$DB_PASSWORD"

$URL_MYSQL -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD -D $DB_DATABASE -e "

-- Criar backups das tabelas (recomendado antes de alterações)
CREATE TABLE IF NOT EXISTS adm_habilidade_series_backup AS SELECT * FROM adm_habilidade_series;
CREATE TABLE IF NOT EXISTS adm_avaliacoes_hab_backup AS SELECT * FROM adm_avaliacoes_hab;
CREATE TABLE IF NOT EXISTS adm_habilidades_backup AS SELECT * FROM adm_habilidades;

DROP TABLE IF EXISTS id_mapping;

# CREATE TABLE id_mapping (
#   id_original int(11) DEFAULT NULL,
#   id_representante int(11) DEFAULT NULL
# ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


# INSERT INTO id_mapping (id_original, id_representante) VALUES
# (940,881),
# (784,740),
# (860,59),
# (288,238),
# (800,750),
# (900,824),
# (688,679),
# (910,849),
# (825,760),
# (930,870),
# (880,802),
# (670,633),
# (644,556);

-- Atualizar adm_habilidade_series
UPDATE adm_habilidade_series
SET habilidade_id = (SELECT id_representante FROM id_mapping WHERE id_original = adm_habilidade_series.habilidade_id)
WHERE habilidade_id IN (SELECT id_original FROM id_mapping);

-- Atualizar adm_avaliacoes_hab
UPDATE adm_avaliacoes_hab
SET habilidade_id = (SELECT id_representante FROM id_mapping WHERE id_original = adm_avaliacoes_hab.habilidade_id)
WHERE habilidade_id IN (SELECT id_original FROM id_mapping);

-- Excluir IDs duplicados de adm_habilidades
DELETE FROM adm_habilidades
WHERE id IN (SELECT id_original FROM id_mapping)
AND id NOT IN (SELECT id_representante FROM id_mapping);

"

php artisan migrate --force