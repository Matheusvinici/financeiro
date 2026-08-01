# Variaveis necessária para encontrar o arquvo .env
script_name=$(basename "$0")
echo "Nome do arquivo: $script_name"
script_path=$(readlink -f "$0")
dir=$(echo "$script_path" | sed "s;$script_name;;")
echo $dir

# incluidno o arquivo .env
source $dir../.env

    hora_inicial=$(date +"%H:%M:%S")
    echo "Script Inicado às: $hora_inicial"

/opt/lampp/bin/mysql $DB_DATABASE -u root -p$DB_PASSWORD -e "

SELECT '' as 'criando Indices';
ALTER TABLE adm_frequencias ADD INDEX IF NOT EXISTS adm_frequencias_aula_id_index (aula_id); 
ALTER TABLE adm_frequencias ADD INDEX IF NOT EXISTS adm_frequencias_faltas_index (faltas); 

DROP TABLE IF EXISTS nova_tabela;

SELECT '' as 'deletando registros frequencias';
DELETE FROM adm_frequencias
where faltas = 0;

SELECT '' as 'Otimizando tabela frequencias';
OPTIMIZE TABLE adm_frequencias;

SELECT '' as 'criando nova_tabela';
CREATE TABLE IF NOT EXISTS nova_tabela (
    frequencia_id BIGINT,
    aula_id BIGINT
);
ALTER TABLE nova_tabela ADD INDEX IF NOT EXISTS nova_tabela_frequencia_id_index (frequencia_id); 
ALTER TABLE nova_tabela ADD INDEX IF NOT EXISTS nova_tabela_aula_id_index (aula_id); 

SELECT '' as 'populando nova_tabela';
INSERT INTO nova_tabela (frequencia_id, aula_id)
SELECT t1.id AS frequencia_id, t2.id AS aula_id
FROM adm_frequencias AS t1
INNER JOIN adm_aulas AS t2 
ON t1.calendario_id = t2.calendario_id
AND t1.turma_id = t2.turma_id
AND t1.serie_id = t2.serie_id
AND t1.disciplina_id = t2.disciplina_id
AND t1.periodo = t2.periodo
AND t1.data = t2.data;

SELECT '' as 'Atualizando aula_id na tabela frequencias';
UPDATE adm_frequencias AS t1
INNER JOIN nova_tabela AS t2 
ON t1.id = t2.frequencia_id
SET
t1.aula_id = t2.aula_id;

SELECT '' as 'Excluindo foreign key';
ALTER TABLE adm_frequencias DROP FOREIGN KEY IF EXISTS adm_frequencias_calendario_id_foreign;
ALTER TABLE adm_frequencias DROP FOREIGN KEY IF EXISTS adm_frequencias_escola_id_foreign;
ALTER TABLE adm_frequencias DROP FOREIGN KEY IF EXISTS adm_frequencias_etapa_id_foreign;
ALTER TABLE adm_frequencias DROP FOREIGN KEY IF EXISTS adm_frequencias_serie_id_foreign;
ALTER TABLE adm_frequencias DROP FOREIGN KEY IF EXISTS adm_frequencias_turma_id_foreign;
ALTER TABLE adm_frequencias DROP FOREIGN KEY IF EXISTS adm_frequencias_turno_id_foreign;
ALTER TABLE adm_frequencias DROP FOREIGN KEY IF EXISTS disciplina_id;

SELECT '' as 'Excluindo colunas da tabela frequencias';
ALTER TABLE adm_frequencias
DROP COLUMN IF EXISTS calendario_id,
DROP COLUMN IF EXISTS escola_id,
DROP COLUMN IF EXISTS professor_id,
DROP COLUMN IF EXISTS etapa_id,
DROP COLUMN IF EXISTS serie_id,
DROP COLUMN IF EXISTS turma_id,
DROP COLUMN IF EXISTS turno_id,
DROP COLUMN IF EXISTS data,
DROP COLUMN IF EXISTS periodo,
DROP COLUMN IF EXISTS disciplina_id;

SELECT '' as 'Otimizando tabela frequencias';
OPTIMIZE TABLE adm_frequencias;

SELECT '' as 'Script Finalizado';

"
    echo "Script Inicado às: $hora_inicial"

    hora_final=$(date +"%H:%M:%S")
    echo "Script Finalizado  às: $hora_inicial"
