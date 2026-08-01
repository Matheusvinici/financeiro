dir="$PWD"
source $dir/.env

database="$DB_DATABASE"
database_user="$DB_USERNAME"
user_password="$DB_PASSWORD"

$URL_MYSQL -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD -D $DB_DATABASE -e "

INSERT INTO adm_diag_habilidade_respostas (
    aluno_id,
    unidade_id,
    json_item_id_conceito,
    json_item_id_ator,
    obs,
    created_at,
    updated_at
)
SELECT 
    lb_r.aluno_id,
    lb_r.unidade_id,
    lb_r.json_item_id_conceito,
    lb_r.json_item_id_ator,
    lb_r.obs,
    lb_r.created_at,
    lb_r.updated_at 
FROM adm_lb_diagnosticos lb_r
JOIN adm_lb_unidades und ON und.id = lb_r.unidade_id
JOIN adm_lb_linhadebases lb ON lb.id = und.linhadebase_id
WHERE lb.calendario_id = 4
AND lb.serie_id = 6;

UPDATE adm_diag_habilidade_respostas
SET 
    json_item_id_conceito = JSON_OBJECT(
        '1', JSON_EXTRACT(json_item_id_conceito, '$.2145'),
        '2', JSON_EXTRACT(json_item_id_conceito, '$.2146'),
        '3', JSON_EXTRACT(json_item_id_conceito, '$.2147'),
        '4', JSON_EXTRACT(json_item_id_conceito, '$.2148'),
        '5', JSON_EXTRACT(json_item_id_conceito, '$.2149'),
        '6', JSON_EXTRACT(json_item_id_conceito, '$.2150'),
        '7', JSON_EXTRACT(json_item_id_conceito, '$.2151'),
        '8', JSON_EXTRACT(json_item_id_conceito, '$.2152'),
        '9', JSON_EXTRACT(json_item_id_conceito, '$.2153'),
        '10', JSON_EXTRACT(json_item_id_conceito, '$.2154'),
        '11', JSON_EXTRACT(json_item_id_conceito, '$.2155'),
        '12', JSON_EXTRACT(json_item_id_conceito, '$.2156'),
        '13', JSON_EXTRACT(json_item_id_conceito, '$.2157'),
        '14', JSON_EXTRACT(json_item_id_conceito, '$.2158'),
        '15', JSON_EXTRACT(json_item_id_conceito, '$.2159'),
        '16', JSON_EXTRACT(json_item_id_conceito, '$.2160'),
        '17', JSON_EXTRACT(json_item_id_conceito, '$.2161'),
        '18', JSON_EXTRACT(json_item_id_conceito, '$.2162'),
        '19', JSON_EXTRACT(json_item_id_conceito, '$.2163'),
        '20', JSON_EXTRACT(json_item_id_conceito, '$.2164'),
        '21', JSON_EXTRACT(json_item_id_conceito, '$.2165'),
        '22', JSON_EXTRACT(json_item_id_conceito, '$.2166')
    ),
    
    json_item_id_ator = JSON_OBJECT(
        '1', JSON_EXTRACT(json_item_id_ator, '$.2145'),
        '2', JSON_EXTRACT(json_item_id_ator, '$.2146'),
        '3', JSON_EXTRACT(json_item_id_ator, '$.2147'),
        '4', JSON_EXTRACT(json_item_id_ator, '$.2148'),
        '5', JSON_EXTRACT(json_item_id_ator, '$.2149'),
        '6', JSON_EXTRACT(json_item_id_ator, '$.2150'),
        '7', JSON_EXTRACT(json_item_id_ator, '$.2151'),
        '8', JSON_EXTRACT(json_item_id_ator, '$.2152'),
        '9', JSON_EXTRACT(json_item_id_ator, '$.2153'),
        '10', JSON_EXTRACT(json_item_id_ator, '$.2154'),
        '11', JSON_EXTRACT(json_item_id_ator, '$.2155'),
        '12', JSON_EXTRACT(json_item_id_ator, '$.2156'),
        '13', JSON_EXTRACT(json_item_id_ator, '$.2157'),
        '14', JSON_EXTRACT(json_item_id_ator, '$.2158'),
        '15', JSON_EXTRACT(json_item_id_ator, '$.2159'),
        '16', JSON_EXTRACT(json_item_id_ator, '$.2160'),
        '17', JSON_EXTRACT(json_item_id_ator, '$.2161'),
        '18', JSON_EXTRACT(json_item_id_ator, '$.2162'),
        '19', JSON_EXTRACT(json_item_id_ator, '$.2163'),
        '20', JSON_EXTRACT(json_item_id_ator, '$.2164'),
        '21', JSON_EXTRACT(json_item_id_ator, '$.2165'),
        '22', JSON_EXTRACT(json_item_id_ator, '$.2166')
    );

"