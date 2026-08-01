#!/bin/bash
clear

# Variaveis necessária para encontrar o arquvo .env
script_name=$(basename "$0")
echo "Nome do arquivo: $script_name"
script_path=$(readlink -f "$0")
dir=$(echo "$script_path" | sed "s;$script_name;;")
echo $dir
dirArtisnan=$(echo "$dir" | sed 's/scripts\///g')

# incluidno o arquivo .env
source $dir../.env

if [ -z $1 ] || [ -z $2 ]; then
    echo "local yes/no / online yes/no?"
    exit 127
else
    hora_inicial=$(date +"%H:%M:%S")

    echo "Execução iniciada às: $hora_inicial"
fi

# ano=$(date +%Y)
ano=2025

# read -p "Executar este script Local (0) ou Online (1)? " option

arquivoWorkspace="$dir/csvs/workspace.csv"

if [ -f "$arquivoWorkspace" ]; then
    fnames=(exportar_alunos exportar_professores exportar_equipe_escolar exportar_turmas workspace)
else
    fnames=(exportar_alunos exportar_professores exportar_equipe_escolar exportar_turmas)
fi

if [ $1 == 'local' ]; then
    # local
    database=$DB_DATABASE
    user=$DB_USERNAME
    pass=$DB_PASSWORD
    DB_HOST=$DB_HOST
    pre=0adm_
    mysql_path=/opt/lampp/bin/mysql
    executar_localmente="
        ##################### apenas local #####################
        ########### Index workspace
        SELECT '' as 'Executando localmente';
        CREATE INDEX IF NOT EXISTS idx_Employee_ID ON 0adm_workspace(Employee_ID);
        CREATE INDEX IF NOT EXISTS idx_Email_Address_Required ON 0adm_workspace(Email_Address_Required);
        CREATE INDEX IF NOT EXISTS idx_Building_ID ON 0adm_workspace(Building_ID);

        # Ajustar CPF Workspace
        UPDATE 0adm_workspace
        SET
        Employee_ID = REPLACE(REPLACE(Employee_ID, '.', ''), '-', ''),
        Building_ID = REPLACE(REPLACE(Building_ID, '.', ''), '-', '');

        UPDATE 0adm_workspace
        SET Employee_ID = LPAD(Employee_ID, 11, 0)
        WHERE Org_Unit_Path_Required not like '%/SEAD%'
        and Employee_ID not like 'a%'
        and Employee_ID <> '';
        ##################### apenas local #####################
    "

    # fnames=(exportar_alunos exportar_professores exportar_equipe_escolar exportar_turmas)

elif [ $1 == 'online' ]; then
    # Online
    database=$DB_DATABASE
    user=$DB_USERNAME
    pass=$DB_PASSWORD
    DB_HOST=$DB_HOST
    pre=0adm_
    mysql_path=mysql
    executar_localmente=""

else
    echo "Indique um do seguintes parêmetros para executar o script (online) ou (local)."
    exit 127
fi

cd csvs/ || {
    clear
    echo "Erro: Este script deve ser executado na pasta SCRIPTS"
    exit 127
}

if [ $2 = yes ]; then

# https://matricula.juazeiro.ba.gov.br/area51/exportar_rotas_csv/$ano?chave=SIEJaoDahFeh21
# https://matricula.juazeiro.ba.gov.br/area51/exportar_rotas_alunos_csv/$ano?chave=SIEJaoDahFeh21
# https://matricula.juazeiro.ba.gov.br/relatorios/exportar_alunos_autistas_csv/$ano?chave=SIEJaoDahFeh21

    wget -O exportar_alunos.csv --no-check-certificate https://matricula.juazeiro.ba.gov.br/relatorios/exportar_alunos_csv/$ano?chave=SIEJaoDahFeh21
    codigo_exportar_alunos=$?
    echo "Código $codigo_exportar_alunos"

    # alunos falecidos
    wget -O exportar_alunos_falecido.csv --no-check-certificate https://matricula.juazeiro.ba.gov.br/relatorios/exportar_alunos_falecido_csv/$ano?chave=SIEJaoDahFeh21
    codigo_exportar_alunos_falecidos=$?
    echo "Código $codigo_exportar_alunos_falecidos"


    wget -O 0adm_exportar_alunos_autistas.csv --no-check-certificate https://matricula.juazeiro.ba.gov.br/relatorios/exportar_alunos_autistas_csv/$ano?chave=SIEJaoDahFeh21
    codigo_exportar_alunos_autistas=$?
    echo "Código $codigo_exportar_alunos_autistas"

    wget -O exportar_equipe_escolar.csv --no-check-certificate https://matricula.juazeiro.ba.gov.br/relatorios/exportar_equipe_escolar_csv/$ano?chave=SIEJaoDahFeh21
    codigo_exportar_equipe_escolar=$?
    echo "Código $codigo_exportar_equipe_escolar"

    wget -O exportar_professores.csv --no-check-certificate https://matricula.juazeiro.ba.gov.br/relatorios/exportar_professores_csv/$ano?chave=SIEJaoDahFeh21
    codigo_exportar_professores=$?
    echo "Código $codigo_exportar_professores"
    
    wget -O exportar_turmas.csv --no-check-certificate https://matricula.juazeiro.ba.gov.br/relatorios/turmas_gestao_escolar_csv/$ano?chave=SIEJaoDahFeh21
    codigo_exportar_turmas=$?
    echo "Código $codigo_exportar_turmas"

    # rotas
    wget -O 0adm_exportar_rotas.csv --no-check-certificate https://matricula.juazeiro.ba.gov.br/area51/exportar_rotas_csv/$ano?chave=SIEJaoDahFeh21
    codigo_exportar_rotas=$?
    echo "Código $codigo_exportar_rotas"
    wget -O 0adm_exportar_rota_aluno.csv --no-check-certificate https://matricula.juazeiro.ba.gov.br/area51/exportar_rotas_alunos_csv/$ano?chave=SIEJaoDahFeh21
    codigo_exportar_rotas_alunos=$?
    echo "Código $codigo_exportar_rotas_alunos"

    if [[ $codigo_exportar_alunos != 0 ||  $codigo_exportar_equipe_escolar != 0 || $codigo_exportar_professores != 0 || $codigo_exportar_turmas != 0 || $codigo_exportar_rotas != 0 || $codigo_exportar_rotas_alunos != 0 || $codigo_exportar_alunos_autistas != 0 ]]; then
     echo "Ocorreram erros nos downloads dos arquivos CSVs."
     exit 127
    fi

else
    echo "Arquivos CSVs não serão baixados."
fi

# Consulta SQL para buscar o id do calendário
SQL_QUERY="SELECT id FROM adm_calendarios WHERE ano = $ano LIMIT 1;"
# Executa a consulta no MySQL e captura o resultado na variável
calendario_id=$($mysql_path -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD -D $DB_DATABASE -se "$SQL_QUERY")
echo "Calendário ID: $calendario_id"

# $mysql_path -h $DB_HOST -P 3306 -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE -e "SELECT '' as 'CRIAÇÂO DE TABELAS';"

for fname in "${fnames[@]}"; do

    sed -i '/^$/d' "$fname".csv #remove linhas vazias
    sed -i 's/"\(.*\),\(.*\)"/\1 \2/g' "$fname".csv
    sed 's/\s*,*\s*$//g' "$fname".csv >tmp.csv

    tableName=$(echo "$fname" | cut -d"." -f 1 | sed 's/-tmp//g')
    tableName="\`$pre$tableName\`"
        columnsNames=$(head -n 1 tmp.csv | \
        tr 'áÁàÀãÃâÂéÉêÊíÍóÓõÕôÔúÚçÇ .-' 'aAaAaAaAeEeEiIoOoOoOuUcC___' | \
        sed 's/\[//g; s/\]//g; s/(//g; s/)//g; s/,/` TEXT,`/g' | \
        tr -d '\r\n')
    columnsNames="\`$columnsNames\` TEXT"
    columnsNames=$(echo "$columnsNames" | sed 's/,`` TEXT//g') #remove nomes colunas vazias

$mysql_path -h $DB_HOST -P 3306 -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE -e "

############## Criação de TABELAS
SELECT '' as 'EXCLUINDO TABELAS';
DROP TABLE IF EXISTS $tableName;

SELECT '' as 'CRIANDO TABELAS';
CREATE TABLE IF NOT EXISTS $tableName($columnsNames);

SELECT '' as 'POPULANDO AS TABELAS';
LOAD DATA LOCAL INFILE '$fname.csv' INTO TABLE $tableName
CHARACTER SET UTF8
FIELDS TERMINATED BY ','
LINES TERMINATED BY '\n'
IGNORE 1 LINES;

alter table $tableName convert to character set utf8mb4 collate utf8mb4_unicode_ci;
ALTER TABLE $tableName ADD COLUMN \`id\` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT FIRST;

"
    rm tmp.csv

done


if [ -f "$arquivoWorkspace" ]; then
    $mysql_path -h $DB_HOST -P 3306 -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE -e "
        SELECT '' as 'Tratando tabela workspace';
        $executar_localmente
    "
else
    echo -e "\033[33m\n O arquivo $arquivoWorkspace não existe.\n\033[0m"
fi


if [ $1 == 'local' ]; then
    /usr/bin/php "$dirArtisnan"artisan db:seed --class=ExportarRotasSeeder --force
    /usr/bin/php "$dirArtisnan"artisan db:seed --class=ExportarRotaAlunoSeeder --force
    /usr/bin/php "$dirArtisnan"artisan db:seed --class=ExportarAlunosAutistasSeeder --force
elif [ $1 == 'online' ]; then
    /usr/local/bin/php "$dirArtisnan"artisan db:seed --class=ExportarRotasSeeder --force --no-interaction
    /usr/local/bin/php "$dirArtisnan"artisan db:seed --class=ExportarRotaAlunoSeeder --force --no-interaction
    /usr/local/bin/php "$dirArtisnan"artisan db:seed --class=ExportarAlunosAutistasSeeder --force --no-interaction

fi


$mysql_path -h $DB_HOST -P 3306 -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE -e "


############## Criação de INDEX
SET SQL_BIG_SELECTS=1;

SELECT '' as 'CRIAÇÂO DE INDEX';

CREATE INDEX IF NOT EXISTS idx_aluno__id ON 0adm_exportar_alunos(aluno__id);
CREATE INDEX IF NOT EXISTS idx_aluno__responsavel__cpf ON 0adm_exportar_alunos(responsavel__cpf);
CREATE INDEX IF NOT EXISTS idx_escola__inep ON 0adm_exportar_alunos(escola__inep);
CREATE INDEX IF NOT EXISTS idx_escola_id ON 0adm_exportar_alunos(escola__id);
CREATE INDEX IF NOT EXISTS idx_sala_id ON 0adm_exportar_alunos(sala__id);
CREATE INDEX IF NOT EXISTS idx_turma_id ON 0adm_exportar_alunos(turma__id);
CREATE INDEX IF NOT EXISTS idx_turma_nome ON 0adm_exportar_alunos(turma__nome);
CREATE INDEX IF NOT EXISTS idx_turno_id ON 0adm_exportar_alunos(turno__id);
CREATE INDEX IF NOT EXISTS idx_aluno_cor ON 0adm_exportar_alunos(aluno__cor);

CREATE INDEX IF NOT EXISTS idx_idnumber ON adm_users(idnumber);
CREATE INDEX IF NOT EXISTS idx_idnumber ON adm_turmas(idnumber);
CREATE INDEX IF NOT EXISTS idx_idnumber ON adm_escolas(idnumber);
CREATE INDEX IF NOT EXISTS idx_idnumber ON adm_disciplinas(idnumber);

ALTER TABLE 0adm_exportar_professores MODIFY COLUMN professor__id varchar(11);
ALTER TABLE 0adm_exportar_professores MODIFY COLUMN escola__id varchar(11);
ALTER TABLE 0adm_exportar_professores MODIFY COLUMN etapa__id varchar(11);
ALTER TABLE 0adm_exportar_professores MODIFY COLUMN serie__id varchar(11);
ALTER TABLE 0adm_exportar_professores MODIFY COLUMN turma__id varchar(11);
ALTER TABLE 0adm_exportar_professores MODIFY COLUMN turno__id varchar(11);
ALTER TABLE 0adm_exportar_professores MODIFY COLUMN disciplina__id varchar(11);

CREATE INDEX IF NOT EXISTS idx_escola__inep ON 0adm_exportar_turmas(escola__inep);
CREATE INDEX IF NOT EXISTS idx_escola_id ON 0adm_exportar_turmas(escola__id);
CREATE INDEX IF NOT EXISTS idx_sala_id ON 0adm_exportar_turmas(sala__id);
CREATE INDEX IF NOT EXISTS idx_turma_id ON 0adm_exportar_turmas(turma__id);
CREATE INDEX IF NOT EXISTS idx_turma_nome ON 0adm_exportar_turmas(turma__nome);
CREATE INDEX IF NOT EXISTS idx_turno_id ON 0adm_exportar_turmas(turno__id);

CREATE INDEX IF NOT EXISTS idx_funcionario__id ON 0adm_exportar_equipe_escolar(funcionario__id);
CREATE INDEX IF NOT EXISTS idx_funcionario__cpf ON 0adm_exportar_equipe_escolar(funcionario__cpf);
CREATE INDEX IF NOT EXISTS idx_escola__id ON 0adm_exportar_equipe_escolar(escola__id);

CREATE INDEX IF NOT EXISTS idx_professor__id ON 0adm_exportar_professores(professor__id);
CREATE INDEX IF NOT EXISTS idx_professor__cpf ON 0adm_exportar_professores(professor__cpf);
CREATE INDEX IF NOT EXISTS idx_escola__inep ON 0adm_exportar_professores(escola__inep);
CREATE INDEX IF NOT EXISTS idx_ano_letivo ON 0adm_exportar_professores(ano_letivo);
CREATE INDEX IF NOT EXISTS idx_turma__id ON 0adm_exportar_professores(turma__id);
CREATE INDEX IF NOT EXISTS idx_escola__id ON 0adm_exportar_professores(escola__id);
CREATE INDEX IF NOT EXISTS idx_disciplina__id ON 0adm_exportar_professores(disciplina__id);

SELECT '' as 'FINALIZADO CRIAÇÂO DE INDEX';

################################### Atualização 0adm_exportar_equipe_escolar ###################################
################################### Atualização 0adm_exportar_equipe_escolar ###################################
############## Insere colunas da tabela 0adm_exportar_equipe_escolar
SELECT '' as 'Atualiza tabela 0adm_exportar_equipe_escolar com ID adm';


ALTER TABLE 0adm_exportar_equipe_escolar
ADD COLUMN IF NOT EXISTS adm_user_id INT AFTER id,
ADD COLUMN IF NOT EXISTS adm_papel_id INT AFTER id,
ADD COLUMN IF NOT EXISTS adm_escola_id INT AFTER id,
ADD COLUMN IF NOT EXISTS adm_funcao_id INT AFTER adm_escola_id;


############## Ajusta CPF
SELECT '' as 'AJUSTE DE CPF 0adm_exportar_equipe_escolar';
# Ajusta CPF
SELECT '' as 'AJUSTE DE CPF 0adm_exportar_equipe_escolar';
UPDATE 0adm_exportar_equipe_escolar
SET
funcionario__cpf = replace(replace(funcionario__cpf, '.', ''), '-', '')
WHERE funcionario__cpf <> ''
and funcionario__cpf = funcionario__cpf;

UPDATE 0adm_exportar_equipe_escolar
SET funcionario__cpf = LPAD(funcionario__cpf,11,0)
WHERE funcionario__cpf <> ''
and funcionario__cpf = funcionario__cpf;

############## Insere funcionarios não existentes em ADM_USERS
SELECT '' as 'Insere funcionarios em ADM_USERS não existentes';
INSERT IGNORE INTO adm_users
	(
    name,
    email,
    username,
    avatar,
    password,
    idnumber,
    cpf
	)
SELECT
    p.funcionario__nome,
    concat('funcionario.', p.funcionario__id, '@' 'juazeiro.ba.gov.br') as email,
    p.funcionario__id,
    'img_avatar.png',
    p.funcionario__cpf,
    p.funcionario__id,
    p.funcionario__cpf
FROM 0adm_exportar_equipe_escolar p
WHERE NOT EXISTS (SELECT 1 FROM adm_users WHERE cpf = p.funcionario__cpf)
and p.funcionario__nome not like 'Vag%a Real'
and p.funcionario__cpf <> ''
and p.funcionario__cpf <> '00000000000'
group by p.funcionario__id;


############## Atualiza IDs adm_papel_id e Cargos/Funções
SELECT '' as 'Atualiza IDs adm_papel_id e Cargos/Funções';
UPDATE 0adm_exportar_equipe_escolar AS t1
# Pepel (tabela adm_roles) no sistema Educajua Pedagógico
SET t1.adm_papel_id = 
CASE 
WHEN funcao__id = 11 THEN 6
WHEN funcao__id = 12 THEN 6
WHEN funcao__id = 52 THEN 5
WHEN funcao__id = 53 THEN 5
WHEN funcao__id = 50 THEN 5
WHEN funcao__id = 59 THEN 4
WHEN funcao__id = 61 THEN 4
WHEN funcao__id = 62 THEN 4
WHEN funcao__id = 64 THEN 4
WHEN funcao__id = 65 THEN 4
WHEN funcao__id = 66 THEN 4
WHEN funcao__id = 110 THEN 21 #professor AEE
WHEN funcao__id = 111 THEN 21 #professor AEE
WHEN funcao__id = 141 THEN 24 #professor Instrutor de Libras
WHEN funcao__id = 82 THEN 34  #interprete de Libras
WHEN funcao__id = 107 THEN 39 #professor Brailista
WHEN funcao__id = 206 THEN 3
WHEN funcao__id = 207 THEN 3
WHEN funcao__id = 222 THEN 9
WHEN funcao__id = 223 THEN 9
WHEN funcao__id = 224 THEN 9
WHEN funcao__id = 38 THEN 17
WHEN funcao__id = 39 THEN 17
WHEN funcao__id = 55 THEN 16
WHEN funcao__id = 56 THEN 16
END,

########## função (tabela adm_cargos) na escola

t1.adm_funcao_id = 
CASE 
WHEN funcao__id = 11 THEN 1
WHEN funcao__id = 12 THEN 1
WHEN funcao__id = 38 THEN 3
WHEN funcao__id = 39 THEN 3
WHEN funcao__id = 42 THEN 4
WHEN funcao__id = 52 THEN 19
WHEN funcao__id = 53 THEN 19
WHEN funcao__id = 50 THEN 19
WHEN funcao__id = 55 THEN 6
WHEN funcao__id = 56 THEN 6
WHEN funcao__id = 59 THEN 18
WHEN funcao__id = 61 THEN 18
WHEN funcao__id = 62 THEN 18
WHEN funcao__id = 64 THEN 18
WHEN funcao__id = 65 THEN 18
WHEN funcao__id = 66 THEN 18
WHEN funcao__id = 110 THEN 16 #Professor de Atendimento Educacional Especializado
WHEN funcao__id = 111 THEN 16 #Professor de Atendimento Educacional Especializado
WHEN funcao__id = 141 THEN 24 #Professor instrutor de Libras
WHEN funcao__id = 82 THEN 26  #interprete de Libras
WHEN funcao__id = 107 THEN 27 #professor Brailista
WHEN funcao__id = 206 THEN 17
WHEN funcao__id = 207 THEN 17
WHEN funcao__id = 222 THEN 20
WHEN funcao__id = 223 THEN 20
WHEN funcao__id = 224 THEN 20
END
;
ALTER TABLE 0adm_exportar_equipe_escolar ADD INDEX IF NOT EXISTS adm_papel_id_index (adm_papel_id);


############## Insere ID adm_escola_id
SELECT '' as 'Insere ID adm_escola_id';
UPDATE 0adm_exportar_equipe_escolar AS t1
INNER JOIN adm_escolas AS t2 ON t1.escola__id = t2.idnumber
SET t1.adm_escola_id = t2.id;
ALTER TABLE 0adm_exportar_equipe_escolar ADD INDEX IF NOT EXISTS adm_escola_id_index (adm_escola_id);


############## Insere ID adm_user_id
SELECT '' as 'Insere ID adm_user_id';
UPDATE 0adm_exportar_equipe_escolar AS t1
INNER JOIN adm_users AS t2 ON t1.funcionario__cpf = t2.cpf
SET t1.adm_user_id = t2.id
where t1.funcionario__cpf <> '';
ALTER TABLE 0adm_exportar_equipe_escolar ADD INDEX IF NOT EXISTS adm_user_id_index (adm_user_id);
################################### FIM Atualização 0adm_exportar_equipe_escolar ###################################
################################### FIM Atualização 0adm_exportar_equipe_escolar ###################################


############## Remove/Adiciona vinculos e permissões invalidos de equipe gestora 
SELECT '' as 'Removendo Vinculos e Permissões inválidos de equipe gestora';
DELETE FROM adm_permissoes
WHERE NOT EXISTS 
(
    SELECT 1 FROM 0adm_exportar_equipe_escolar exp_p
    WHERE exp_p.adm_user_id IS NOT NULL
    AND exp_p.adm_user_id = adm_permissoes.user_id
    AND exp_p.adm_escola_id = adm_permissoes.escola_id
    AND exp_p.adm_papel_id = adm_permissoes.role_id
    GROUP BY exp_p.adm_escola_id, exp_p.adm_user_id, exp_p.adm_papel_id
)
AND (
role_id = 6
OR role_id = 5
OR role_id = 4
OR role_id = 3
OR role_id = 9
OR role_id = 17
OR role_id = 16
OR role_id = 21
OR role_id = 24
);

###########Atualiza vinculos para situacao inativas
# UPDATE adm_vinculos AS t1
# SET 
# t1.situacao = 0,
# updated_at = now()
# WHERE NOT EXISTS (
#     SELECT 1 FROM 0adm_exportar_equipe_escolar not_t2
#     where not_t2.adm_funcao_id = t1.cargo_id
#     AND not_t2.adm_user_id = t1.user_id
#     AND not_t2.adm_funcao_id is not null
# );

###### vinculos
SELECT '' as 'Adicionando Vinculos de equipe gestora';
INSERT IGNORE INTO adm_vinculos 
(
cargo_id,
user_id,
situacao,
created_at,
updated_at
)
SELECT 
adm_funcao_id,
adm_user_id,
1,
now(),
now()
FROM 0adm_exportar_equipe_escolar ext
WHERE NOT EXISTS 
	(SELECT 1 FROM adm_vinculos 
 	where cargo_id = ext.adm_funcao_id
	and user_id = ext.adm_user_id)
and adm_funcao_id is not null
group by adm_funcao_id, adm_user_id;


################### Remove/Adiciona permissões de equipe gestora
SELECT '' as 'Removendo Vinculos e Permissões inválidos de equipe gestora';
DELETE p FROM adm_model_has_roles p
WHERE NOT EXISTS (
    SELECT 1 FROM 0adm_exportar_equipe_escolar exp_p
    WHERE exp_p.adm_user_id = p.model_id
    AND exp_p.adm_papel_id = p.role_id
AND adm_user_id is NOT null
AND adm_papel_id is not null
AND adm_escola_id is not null

    GROUP BY exp_p.adm_user_id
)
AND (
    role_id = 6
OR role_id = 5
OR role_id = 4
OR role_id = 3
OR role_id = 9
OR role_id = 17
OR role_id = 16
OR role_id = 21
OR role_id = 24
);


############################## Adiciona Vinculo Unidade de funcionarios
SELECT '' as 'Adicionando Vinculo Unidade de funcionarios';

INSERT IGNORE INTO adm_permissoes (role_id, user_id, escola_id, created_at, updated_at)
SELECT adm_papel_id, adm_user_id, adm_escola_id, now(), now() FROM 0adm_exportar_equipe_escolar exp_p
WHERE NOT EXISTS (
    SELECT 1 FROM adm_permissoes p
    WHERE exp_p.adm_user_id = p.user_id
    AND exp_p.adm_escola_id = p.escola_id
    AND exp_p.adm_papel_id = p.role_id
)
AND adm_user_id is not null
AND adm_papel_id is not null
AND adm_escola_id is not null
group by adm_escola_id, adm_user_id, adm_papel_id;


############################## Adiciona permissao de funcionarios
SELECT '' as 'Adicionando Permissões de funcionarios';

INSERT IGNORE INTO adm_model_has_roles (role_id, model_type, model_id)
SELECT adm_papel_id, 'App\\\Models\\\User', adm_user_id FROM 0adm_exportar_equipe_escolar exp_p
where NOT EXISTS
	(
    SELECT 1 FROM adm_model_has_roles mhr
    where mhr.role_id = adm_papel_id
    and mhr.model_id =  exp_p.adm_user_id
    )
AND adm_user_id is NOT null
AND adm_papel_id is not null
group by exp_p.adm_user_id, adm_papel_id;

## Removendo dados desnecessários da table exportar_professores
DELETE FROM 0adm_exportar_professores
WHERE professor__nome like 'Vag%a Real'
or professor__nome like 'Vaga%Concurso';

# Ajusta CPF
SELECT '' as 'AJUSTE DE CPF 0adm_exportar_alunos';
UPDATE 0adm_exportar_alunos
SET
responsavel__cpf = replace(replace(responsavel__cpf, '.', ''), '-', '')
WHERE responsavel__cpf <> ''
and responsavel__cpf = responsavel__cpf;

# Ajusta CPF
SELECT '' as 'AJUSTE DE CPF 0adm_exportar_equipe_escolar';
UPDATE 0adm_exportar_equipe_escolar
SET
funcionario__cpf = replace(replace(funcionario__cpf, '.', ''), '-', '')
WHERE funcionario__cpf <> ''
and funcionario__cpf = funcionario__cpf;

# Ajusta CPF
SELECT '' as 'AJUSTE DE CPF 0adm_exportar_professores';
UPDATE 0adm_exportar_professores
SET professor__cpf = replace(replace(professor__cpf, '.', ''), '-', '')
WHERE professor__cpf <> ''
and professor__cpf = professor__cpf;

# Ajusta CPF
SELECT '' as 'AJUSTE DE CPF 0adm_exportar_professores';
UPDATE 0adm_exportar_professores
SET professor__cpf = LPAD(professor__cpf,11,0)
WHERE professor__cpf <> ''
and professor__cpf = professor__cpf;

# Ajusta ID Lingua Estrangeira Moderna Inglês para Língua Inglesa
SELECT '' as 'AJUSTE DE ID Lingua Estrangeira Moderna Inglês para Língua Inglesa';
UPDATE 0adm_exportar_professores
SET disciplina__id = 39
WHERE disciplina__id = '9';

# Ajusta CPF
SELECT '' as 'AJUSTE DE CPF adm_users';
UPDATE adm_users
SET cpf = replace(replace(cpf, '.', ''), '-', '')
WHERE cpf <> ''
and cpf = cpf;

########################
# Atualizar CPF para 11 dígitos USERS
SELECT '' as 'Atualizar CPF para 11 dígitos USERS';
UPDATE adm_users
SET cpf = LPAD(cpf, 11, 0)
WHERE length(cpf) < 11
and cpf <> ''
and cpf = cpf;

# Atualizar CPF para 11 dígitos EXPORTAR_ALUNOS
SELECT '' as 'Atualizar CPF para 11 dígitos EXPORTAR_ALUNOS';
UPDATE 0adm_exportar_alunos
SET responsavel__cpf = LPAD(responsavel__cpf, 11, 0)
WHERE length(responsavel__cpf) < 11
and responsavel__cpf <> ''
and responsavel__cpf = responsavel__cpf;


# Atualizar CPF para 11 dígitos EQUIPE ESCOLAR
SELECT '' as 'Atualizar CPF para 11 dígitos EQUIPE ESCOLAR';
UPDATE 0adm_exportar_equipe_escolar
SET funcionario__cpf = LPAD(funcionario__cpf, 11, 0)
WHERE length(funcionario__cpf) < 11
and funcionario__cpf <> ''
and funcionario__cpf = funcionario__cpf;


############# Criação de SALAS inexistentes
SELECT '' as 'Criação de SALAS inexistentes';

Insert IGNORE INTO adm_salas
    (
    idnumber,
    nome,
    capacidade,
    area,
    escola_id,
    extensao,
    created_at,
    updated_at
    )
SELECT
    a.sala__id,
    a.sala__nome,
    50 as capacidade,
    50 as area,
    e.id,
    40 as extensao,
    now(),
    now()
FROM 0adm_exportar_turmas a
join adm_escolas e on e.idnumber = a.escola__id
WHERE NOT EXISTS (SELECT idnumber FROM adm_salas WHERE idnumber = a.sala__id)
and a.sala__id <> ''
group by a.sala__id;


# ############## Criação de TURMAS inexistentes
SELECT '' as 'Criação de TURMAS inexistentes';

Insert IGNORE INTO adm_turmas
    (
    idnumber,
    calendario_id,
    descricao,
    turma,
    escola_id,
    multi,
    sala_id,
    turno_id,
    programa_id,
    modalidade,
    ator,
    ativo,
    data_inicio_letivo_turma,
    data_fim_letivo_turma,
    fechada,
    created_at,
    updated_at
    )
SELECT
    tu.turma__id,
    c.id,
    tu.serie__nome,
    substr(tu.turma__nome, -1),
    e.id,
    0 as multi,
    s.id,
    t.id,
    null as programa_id,
    modalidade,
    1 as ator,
    1 as ativo,
    c.inicio,
    c.fim,
    0 as fechada,
    now(),
    now()
FROM 0adm_exportar_turmas tu
join adm_escolas e on e.idnumber = tu.escola__id
join adm_calendarios c on c.ano = tu.ano__letivo
join adm_salas s on s.idnumber = tu.sala__id
join adm_turnos t on t.idnumber = tu.turno__id
WHERE NOT EXISTS (SELECT idnumber FROM adm_turmas WHERE idnumber = tu.turma__id)
and tu.turma__nome not like '%MULTI%'
and tu.turma__id <> ''
group by tu.turma__id;

# ############## Criação de TURMAS MULTISSERIADAS inexistentes
SELECT '' as 'Criação de TURMAS MULTISSERIADAS inexistentes';

Insert IGNORE INTO adm_turmas
    (
    idnumber,
    calendario_id,
    descricao,
    turma,
    escola_id,
    multi,
    sala_id,
    turno_id,
    programa_id,
    modalidade,
    ator,
    ativo,
    data_inicio_letivo_turma,
    data_fim_letivo_turma,
    fechada,
    created_at,
    updated_at
    )
SELECT
    concat(tu.ano__letivo, '_', tu.escola__id, '_', tu.sala__id, '_', tu.turno__id),
    c.id,
    tu.turma__nome,
    ' ' as turma,
    e.id,
    1 as multi,
    s.id,
    t.id,
    null as programa_id,
    modalidade,
    1 as ator,
    1 as ativo,
    c.inicio,
    c.fim,
    0 as fechada,
    now(),
    now()
FROM 0adm_exportar_turmas tu
join adm_escolas e on e.idnumber = tu.escola__id
join adm_calendarios c on c.ano = tu.ano__letivo
join adm_salas s on s.escola_id = e.id and s.idnumber = tu.sala__id
join adm_turnos t on t.idnumber = tu.turno__id
WHERE NOT EXISTS
(SELECT idnumber FROM adm_turmas
WHERE idnumber = concat(tu.ano__letivo, '_', tu.escola__id, '_', tu.sala__id, '_', tu.turno__id)
)
# AND NOT EXISTS (
#     SELECT 1 
#     FROM 0adm_turmas_portal_id_turma_id pt 
#     WHERE pt.portal_id = tu.turma__id
# )
and tu.turma__nome like '%MULTI%'
and tu.turma__id <> ''
group by tu.ano__letivo, tu.escola__id, tu.sala__id, tu.turno__id;


################################################## Atualiza tabela esportar_alunos com ID adm
SELECT '' as 'Atualiza tabela esportar_alunos com ID adm';
-- cria colunas
ALTER TABLE 0adm_exportar_alunos
ADD COLUMN IF NOT EXISTS adm_statusmatricula_id INT AFTER id,
ADD COLUMN IF NOT EXISTS adm_turno_id INT AFTER id,
ADD COLUMN IF NOT EXISTS adm_serie_id VARCHAR(20) AFTER id,
ADD COLUMN IF NOT EXISTS adm_etapa_id VARCHAR(20) AFTER id,
ADD COLUMN IF NOT EXISTS adm_turma_id VARCHAR(20) AFTER id,
ADD COLUMN IF NOT EXISTS adm_user_id INT AFTER id,
ADD COLUMN IF NOT EXISTS adm_escola_id INT AFTER id,
ADD COLUMN IF NOT EXISTS adm_cor_raca_id INT AFTER adm_statusmatricula_id;

UPDATE 0adm_exportar_alunos AS t1
SET t1.status = REPLACE(REPLACE(REPLACE(t1.status, CHAR(13, 10), ''), '\n', ''), '\r', '');

UPDATE 0adm_exportar_alunos AS t1
INNER JOIN adm_escolas AS t2 ON t1.escola__id = t2.idnumber
SET t1.adm_escola_id = t2.id;
ALTER TABLE 0adm_exportar_alunos ADD INDEX IF NOT EXISTS adm_escola_id_index (adm_escola_id);

UPDATE 0adm_exportar_alunos AS t1
INNER JOIN adm_turmas AS t2 ON t1.turma__id = t2.idnumber
SET t1.adm_turma_id = t2.id;
ALTER TABLE 0adm_exportar_alunos ADD INDEX IF NOT EXISTS adm_turma_id_index (adm_turma_id);

# turmas Multisseriadas
UPDATE 0adm_exportar_alunos AS t1
INNER JOIN adm_turmas AS t2 ON concat(t1.ano_letivo, '_', t1.escola__id, '_', t1.sala__id, '_', t1.turno__id) = t2.idnumber
SET t1.adm_turma_id = t2.id
where t1.turma__nome like '%MULTI%';
ALTER TABLE 0adm_exportar_alunos ADD INDEX IF NOT EXISTS adm_turma_id_index (adm_turma_id);

UPDATE 0adm_exportar_alunos AS t1
INNER JOIN adm_series AS t2 ON t1.serie__id = t2.idnumber
SET t1.adm_serie_id = t2.id;
ALTER TABLE 0adm_exportar_alunos ADD INDEX IF NOT EXISTS adm_serie_id_index (adm_serie_id);

UPDATE 0adm_exportar_alunos AS t1
INNER JOIN adm_etapas AS t2 ON t1.etapa__id = t2.idnumber
SET t1.adm_etapa_id = t2.id;
ALTER TABLE 0adm_exportar_alunos ADD INDEX IF NOT EXISTS adm_etapa_id_index (adm_etapa_id);

UPDATE 0adm_exportar_alunos AS t1
INNER JOIN adm_racas_cores AS t2 ON t1.aluno__cor = t2.idnumber
SET t1.adm_cor_raca_id = t2.id;
ALTER TABLE 0adm_exportar_alunos ADD INDEX IF NOT EXISTS adm_cor_raca_id_index (adm_cor_raca_id);

UPDATE 0adm_exportar_alunos AS t1
SET t1.adm_etapa_id =   CASE
                            WHEN t1.serie__nome = 'Etapa I' THEN 6
                            WHEN t1.serie__nome = 'Etapa II' THEN 6
                            WHEN t1.serie__nome = 'Etapa III' THEN 6
                            WHEN t1.serie__nome = 'Etapa IV' THEN 7
                            WHEN t1.serie__nome = 'Etapa V' THEN 7
                            WHEN t1.serie__nome = 'EJA EAD - IV E V' THEN 7
                        END
where adm_etapa_id is null;
ALTER TABLE 0adm_exportar_alunos ADD INDEX IF NOT EXISTS adm_etapa_id_index (adm_etapa_id);

## Atualiza Status Matriculas
UPDATE 0adm_exportar_alunos AS t1
INNER JOIN adm_statusmatriculas AS t2 ON t1.status = t2.idnumber
SET t1.adm_statusmatricula_id = t2.id;
ALTER TABLE 0adm_exportar_alunos ADD INDEX IF NOT EXISTS adm_statusmatricula_id_index (adm_statusmatricula_id);

## Turno ID
UPDATE 0adm_exportar_alunos AS t1
INNER JOIN adm_turnos AS t2 ON t1.turno__id = t2.idnumber
SET t1.adm_turno_id = t2.id;
ALTER TABLE 0adm_exportar_alunos ADD INDEX IF NOT EXISTS adm_turno_id_index (adm_turno_id);
################################################## FIM Atualiza tabela exportar_alunos com ID adm

################################################## Atualiza tabela exportar_turmas com IDs adm
SELECT '' as 'Atualiza tabela exportar_turmas com ID adm';
-- cria colunas
ALTER TABLE 0adm_exportar_turmas ADD COLUMN IF NOT EXISTS adm_serie_id INT(20) AFTER id;
ALTER TABLE 0adm_exportar_turmas ADD COLUMN IF NOT EXISTS adm_turma_id INT(20) AFTER id;
ALTER TABLE 0adm_exportar_turmas ADD COLUMN IF NOT EXISTS adm_sala_id INT(20) AFTER id;
ALTER TABLE 0adm_exportar_turmas ADD COLUMN IF NOT EXISTS adm_turno_id INT(20) AFTER id;
ALTER TABLE 0adm_exportar_turmas ADD COLUMN IF NOT EXISTS adm_escola_id INT AFTER id;

UPDATE 0adm_exportar_turmas AS t1
INNER JOIN adm_escolas AS t2 ON t1.escola__id = t2.idnumber
SET t1.adm_escola_id = t2.id;
ALTER TABLE 0adm_exportar_turmas ADD INDEX IF NOT EXISTS adm_escola_id_index (adm_escola_id);

UPDATE 0adm_exportar_turmas AS t1
INNER JOIN adm_turmas AS t2 ON t1.turma__id = t2.idnumber
SET t1.adm_turma_id = t2.id;
ALTER TABLE 0adm_exportar_turmas ADD INDEX IF NOT EXISTS adm_turma_id_index (adm_turma_id);

UPDATE 0adm_exportar_turmas AS t1
INNER JOIN adm_salas AS t2 ON t1.sala__id = t2.idnumber
SET t1.adm_sala_id = t2.id;
ALTER TABLE 0adm_exportar_turmas ADD INDEX IF NOT EXISTS adm_sala_id_index (adm_sala_id);

UPDATE 0adm_exportar_turmas AS t1
INNER JOIN adm_turnos AS t2 ON t1.turno__id = t2.idnumber
SET t1.adm_turno_id = t2.id;
ALTER TABLE 0adm_exportar_turmas ADD INDEX IF NOT EXISTS adm_turno_id_index (adm_turno_id);

# turmas Multisseriadas
UPDATE 0adm_exportar_turmas AS t1
INNER JOIN adm_turmas AS t2 ON concat(t1.ano__letivo, '_', t1.escola__id, '_', t1.sala__id, '_', t1.turno__id) = t2.idnumber
SET t1.adm_turma_id = t2.id
where t1.turma__nome like '%MULTI%';
ALTER TABLE 0adm_exportar_turmas ADD INDEX IF NOT EXISTS adm_turma_id_index (adm_turma_id);

UPDATE 0adm_exportar_turmas AS t1
INNER JOIN adm_series AS t2 ON t1.serie__id = t2.idnumber
SET t1.adm_serie_id = t2.id;
ALTER TABLE 0adm_exportar_turmas ADD INDEX IF NOT EXISTS adm_serie_id_index (adm_serie_id);
################################################## FIM Atualiza tabela esportar_turmas com IDs adm


############## VINCULOS SERIE_TURMA
SELECT '' as 'VINCULOS SERIE_TURMA';

Insert IGNORE INTO adm_serie_turma
    (
    matriz_id,
    serie_id,
    turma_id,
    vagas,
    created_at,
    updated_at
    )
SELECT
    null as matriz_id,
    ext.adm_serie_id,
    ext.adm_turma_id,
    50,
    now(),
    now()
FROM 0adm_exportar_turmas ext
join adm_calendarios c on c.ano = ext.ano__letivo
WHERE NOT EXISTS (SELECT serie_id, turma_id FROM adm_serie_turma
WHERE serie_id = ext.adm_serie_id
and turma_id = ext.adm_turma_id)
and ext.adm_turma_id is not null
and ext.adm_serie_id is not null
group by ext.adm_turma_id, ext.adm_serie_id;


############ deleta vínculos de serie_turma inexistentes
DELETE st FROM adm_serie_turma st
join adm_turmas t on t.id = st.turma_id
join adm_calendarios c on c.id = t.calendario_id
WHERE NOT EXISTS (
    SELECT 1
    FROM 0adm_exportar_turmas ext
    WHERE st.serie_id = ext.adm_serie_id
    AND st.turma_id = ext.adm_turma_id
)
and c.id = $calendario_id;


############## Insere professores em ADM_USERS não existentes
SELECT '' as 'Insere professores em ADM_USERS não existentes';

INSERT IGNORE INTO adm_users
	(
    name,
    email,
    username,
    avatar,
    password,
    idnumber,
    cpf
	)
SELECT
    p.professor__nome,
    concat('professor.', p.professor__id, '@' 'juazeiro.ba.gov.br') as email,
    p.professor__id,
    'img_avatar.png',
    p.professor__cpf,
    p.professor__id,
    p.professor__cpf
FROM 0adm_exportar_professores p
WHERE NOT EXISTS (SELECT 1 FROM adm_users WHERE cpf = p.professor__cpf)
and p.professor__nome not like 'Vag%a Real'
and p.professor__cpf <> ''
group by p.professor__id;


############## Insere alunos em ADM_USERS não existentes
SELECT '' as 'Insere alunos em ADM_USERS não existentes';


INSERT IGNORE INTO adm_users
	(
    name,
    nome_social,
    usar_nome_social,
    email,
    username,
    avatar,
    password,
    idnumber,
    data_nascimento,
    nome_responsavel,
    cpf_responsavel,
    ne,
    cor_raca
	)
SELECT
a.aluno__nome,
a.aluno__nome_social,
case when a.aluno__utiliza_nome_social = 'true' then 1 else 0 end,
concat('aluno.','sem_email', a.aluno__id, '@' 'juazeiro.ba.gov.br') as email,
concat('a', a.aluno__id),
'img_avatar.png',
'aluno2023',
concat('a', a.aluno__id) as idnumber,
case when a.aluno__data_nascimento = '' then '1900-01-01' else a.aluno__data_nascimento end,
a.responsavel__nome,
replace(replace(a.responsavel__cpf, '.', ''), '-', ''),
case when a.aluno__possui_deficiencia = 'true' then 1 else 0 end,
adm_cor_raca_id
from 0adm_exportar_alunos a
where NOT EXISTS
(select idnumber from adm_users where idnumber = concat('a',a.aluno__id))
group by a.aluno__id;


############## Matricular alunos
SELECT '' as 'Matricular alunos';

INSERT IGNORE INTO adm_matriculas
    (
    user_id,
    calendario_id,
    matricula,
    escola_id,
    etapa_id,
    serie_id_base,
    serie_id,
    turno_id,
    statusmatricula_id,
    escola_anterior,
    transporte_publico,
    tipo_matricula,
    created_at,
    updated_at
    )
SELECT
    u.id,
    c.id,
    concat(c.ano, LPAD(u.id, 8, 0)) as matricula,
    e.id,
    s.etapa_id,
    s.id,
    s.id,
    t.id,
    1 as statusmatricula_id,
    'Não Informado' as escola_anterior,
    case when a.utiliza_transporte = 'true' then 1 else 0 end,
    0 as tipo_matricula,
    concat('', now()),
    now()
from 0adm_exportar_alunos a
join adm_calendarios c on c.ano = a.ano_letivo
join adm_users u on u.idnumber = concat('a',a.aluno__id)
join adm_escolas e on e.idnumber = a.escola__id
join adm_turnos t on t.idnumber = a.turno__id
join adm_series s on s.idnumber = a.serie__id
where NOT EXISTS (select user_id, calendario_id from adm_matriculas
where user_id = u.id
and calendario_id = c.id)
-- and a.adm_statusmatricula_id = 1
group by a.aluno__id;


################################################## Atualiza tabela exportar_alunos com ID adm
SELECT '' as 'Atualiza tabela esportar_alunos com ID adm para adm_user';

-- atualiza colunas
UPDATE 0adm_exportar_alunos AS t1
INNER JOIN adm_users AS t2 ON concat('a', t1.aluno__id) = t2.idnumber
SET t1.adm_user_id = t2.id;
ALTER TABLE 0adm_exportar_alunos ADD INDEX IF NOT EXISTS adm_user_id_index (adm_user_id);
################################################## FIM Atualiza tabela esportar_alunos com ID adm

# ############## remove alocações de aluno_turma
SELECT '' as 'Resolvendo alocações de aluno_turma';
UPDATE adm_aluno_turma a
LEFT JOIN 0adm_exportar_alunos exp_a ON exp_a.adm_user_id = a.user_id AND exp_a.adm_turma_id = a.turma_id and a.ativo = 1
JOIN adm_turmas t ON t.id = a.turma_id
SET a.ativo = 0,
    a.updated_at = now()
WHERE exp_a.adm_user_id is null
and t.calendario_id = $calendario_id;

SELECT '' as 'Alocar alunos';

INSERT IGNORE INTO adm_aluno_turma
    (
    user_id,
    turma_id,
    at_serie_id,
    ativo,
    created_at,
    updated_at
    )
	SELECT
        a.adm_user_id,
        a.adm_turma_id,
        a.adm_serie_id,
        1,
        now(),
        now()
    from 0adm_exportar_alunos a
        join adm_calendarios c on c.ano = a.ano_letivo
    where NOT EXISTS 
        (select user_id, turma_id from adm_aluno_turma
        where user_id = a.adm_user_id
        and turma_id = a.adm_turma_id
        and ativo = 1)
    and c.ano = a.ano_letivo
    and a.adm_turma_id is not null
    and status = 3
    GROUP BY a.aluno__id;

############### atualizar alocação de aluno

UPDATE adm_aluno_turma atu
JOIN (
    SELECT 
        exp_a.adm_user_id, 
        exp_tu.adm_turma_id, 
        s.id AS serie_id
    FROM 0adm_exportar_alunos exp_a
    JOIN 0adm_exportar_turmas exp_tu ON exp_tu.turma__id = exp_a.turma__id
    JOIN adm_series s ON s.idnumber = exp_tu.serie__id
    GROUP BY exp_a.aluno__id
) subquery ON subquery.adm_user_id = atu.user_id AND subquery.adm_turma_id = atu.turma_id
SET atu.at_serie_id = subquery.serie_id
WHERE atu.ativo = 1
  AND atu.at_serie_id != subquery.serie_id;

############## Alocar professores
SELECT '' as 'Ajuste de aloção de professores';

################################### Atualização exportar_professores ###################################
################################### Atualização exportar_professores ###################################
################################### Atualização exportar_professores ###################################
################################### Atualização exportar_professores ###################################
################################### Atualização exportar_professores ###################################
################################### Atualização exportar_professores ###################################
################################### Atualização exportar_professores ###################################
################################### Atualização exportar_professores ###################################
################################### Atualização exportar_professores ###################################
-- cria colunas
ALTER TABLE 0adm_exportar_professores ADD COLUMN IF NOT EXISTS adm_disciplina_id INT AFTER id;
ALTER TABLE 0adm_exportar_professores ADD COLUMN IF NOT EXISTS adm_turma_id VARCHAR(20) AFTER id;
ALTER TABLE 0adm_exportar_professores ADD COLUMN IF NOT EXISTS adm_user_id INT AFTER id;
ALTER TABLE 0adm_exportar_professores ADD COLUMN IF NOT EXISTS adm_escola_id INT AFTER id;

UPDATE 0adm_exportar_professores AS t1
INNER JOIN adm_escolas AS t2 ON t1.escola__id = t2.idnumber
SET t1.adm_escola_id = t2.id;
ALTER TABLE 0adm_exportar_professores ADD INDEX IF NOT EXISTS adm_escola_id_index (adm_escola_id);

#########################################
-- atualiza colunas adm_user_id
UPDATE 0adm_exportar_professores AS t1
INNER JOIN adm_users AS t2 ON t1.professor__cpf = t2.cpf
SET t1.adm_user_id = t2.id
where t1.professor__cpf <> '';
ALTER TABLE 0adm_exportar_professores ADD INDEX IF NOT EXISTS adm_user_id_index (adm_user_id);

-- atualiza colunas adm_turma_id comuns
UPDATE 0adm_exportar_professores AS t1
INNER JOIN adm_turmas AS t2 ON t1.turma__id = t2.idnumber
SET t1.adm_turma_id = t2.id
where t1.turma__nome not like '%MULTI%';
ALTER TABLE 0adm_exportar_professores ADD INDEX IF NOT EXISTS adm_turma_id_index (adm_turma_id);

# -- atualiza colunas adm_turma_id das turmas MULTISSERIADAS
UPDATE 0adm_exportar_professores AS t1
INNER JOIN adm_turmas AS t2 ON concat(t1.ano_letivo, '_', t1.escola__id, '_', t1.sala__id, '_', t1.turno__id) = t2.idnumber
SET t1.adm_turma_id = t2.id
where t1.turma__nome like '%MULTI%';

-- atualiza colunas adm_disciplina_id
UPDATE 0adm_exportar_professores AS t1
INNER JOIN adm_disciplinas AS t2 ON t1.disciplina__id = t2.idnumber
SET t1.adm_disciplina_id = t2.id;
ALTER TABLE 0adm_exportar_professores ADD INDEX IF NOT EXISTS adm_disciplina_id_index (adm_disciplina_id);

################################### FIM Atualização exportar_professores ###################################
################################### FIM Atualização exportar_professores ###################################
################################### FIM Atualização exportar_professores ###################################
################################### FIM Atualização exportar_professores ###################################
################################### FIM Atualização exportar_professores ###################################
################################### FIM Atualização exportar_professores ###################################
################################### FIM Atualização exportar_professores ###################################
################################### FIM Atualização exportar_professores ###################################
################################### FIM Atualização exportar_professores ###################################

############################## Ajusta Vinculo Unidade de professores
SELECT '' as 'Removendo Vinculo Unidade de professores';

############## Remove vinculos de professores invalidos
DELETE FROM adm_permissoes
WHERE NOT EXISTS (
    SELECT 1 FROM \`0adm_exportar_professores\` exp_p
    WHERE exp_p.adm_user_id IS NOT NULL
    AND exp_p.adm_user_id = adm_permissoes.user_id
    AND exp_p.adm_escola_id = adm_permissoes.escola_id
    GROUP BY exp_p.adm_escola_id, exp_p.adm_user_id
)
AND role_id = 10;

############################## Adiciona permissao de professores
SELECT '' as 'Adiciona permissoes de professores';
DELETE p FROM adm_model_has_roles p
WHERE NOT EXISTS (
    SELECT 1 FROM \`0adm_exportar_professores\` exp_p
    WHERE exp_p.adm_user_id = p.model_id
    GROUP BY exp_p.adm_user_id
)
AND role_id = 10
AND model_id <> 69723;

############################## Adiciona Vinculo Unidade de professores
SELECT '' as 'Adicionando Vinculo Unidade de professores';

INSERT IGNORE INTO adm_permissoes (role_id, user_id, escola_id, created_at, updated_at)
SELECT 10, adm_user_id, adm_escola_id, now(), now() FROM 0adm_exportar_professores exp_p
WHERE NOT EXISTS (
    SELECT 1 FROM adm_permissoes p
    WHERE exp_p.adm_user_id = p.user_id
    AND exp_p.adm_escola_id = p.escola_id
)
AND adm_user_id is NOT null
group by adm_escola_id, adm_user_id;

############################## Adiciona permissao de professores
SELECT '' as 'Adicionando Permissões de professores';


INSERT IGNORE INTO adm_model_has_roles (role_id, model_type, model_id)
SELECT 10, 'App\\\Models\\\User', adm_user_id FROM 0adm_exportar_professores exp_p
where NOT EXISTS
	(SELECT 1 FROM adm_model_has_roles mhr
     where mhr.role_id = 10
     and mhr.model_id =  exp_p.adm_user_id)
and exp_p.adm_user_id is NOT null
group by exp_p.adm_user_id;

####################################################
####################################################
########## Ajusta alocações de professores #########
########## Ajusta alocações de professores #########
####################################################
####################################################
####################################################
############### Ajusta alocações de professores professor_turma
########## cria uma tabela temporário com as alocações de professores


DROP TABLE IF EXISTS 0temp_alocacao_professor;

CREATE TABLE 0temp_alocacao_professor (
  user_id INT,
  turma_id INT,
  escola_id INT,
  disciplina_id INT
);


# atualiza IDs de turmas do portal e pedagógico
CREATE TABLE IF NOT EXISTS 0adm_turmas_portal_id_turma_id
(
    portal_id INT,
    turma_id INT
);
#####################################################
INSERT IGNORE INTO 0adm_turmas_portal_id_turma_id
    (
    portal_id,
    turma_id
    )
SELECT
    tu.turma__id,
    adm_turma_id
FROM 0adm_exportar_turmas tu
WHERE NOT EXISTS 
(SELECT 1 FROM 0adm_turmas_portal_id_turma_id 
WHERE portal_id = tu.turma__id)
and tu.turma__id <> ''
group by tu.turma__id;


######################################################
## atualizar os IDs de turmas do portal e pedagógico
UPDATE 0adm_turmas_portal_id_turma_id tpi
SET tpi.turma_id = (
    SELECT tu.adm_turma_id
    FROM 0adm_exportar_turmas tu
    WHERE tu.turma__id = tpi.portal_id
    AND tu.turma__id <> ''
)
WHERE tpi.turma_id = 0
AND EXISTS (
    SELECT 1
    FROM 0adm_exportar_turmas tu
    WHERE tu.turma__id = tpi.portal_id
    AND tu.turma__id <> ''
);

######################################################
##############################################################################
################ Atualização de tabela adm_0adm_exportar_rotas ###############
SELECT '' as 'Atualização de tabela adm_0adm_exportar_rotas';
UPDATE IGNORE adm_0adm_exportar_rotas
SET adm_turno_id = (
    SELECT id FROM adm_turnos WHERE idnumber = adm_0adm_exportar_rotas.turno_id
);

############# Criação de ROTAS inexistentes
SELECT '' as 'Criação de ROTAS inexistentes';

INSERT IGNORE INTO adm_rotas
    (
    idnumber,
    nome_antigo,
    nome,
    turno_id,
    capacidade,
    motorista_nome,
    created_at,
    updated_at
    )
SELECT
    rota_id,
    rota_nome_antigo,
    rota_nome,
    adm_turno_id,
    capacidade,
    motorista_nome,
    now(),
    now()
FROM adm_0adm_exportar_rotas a
WHERE NOT EXISTS (SELECT idnumber FROM adm_rotas WHERE idnumber = rota_id);


##############################################################################
################ Atualiza colunas tabela adm_0adm_exportar_rota_aluno ####################
SELECT '' as 'Atualiza colunas tabela adm_0adm_exportar_rota_aluno';
ALTER TABLE adm_0adm_exportar_rota_aluno;
UPDATE IGNORE adm_0adm_exportar_rota_aluno
SET adm_rota_id = (
    SELECT id FROM adm_rotas WHERE idnumber = adm_0adm_exportar_rota_aluno.rota_id
);
UPDATE IGNORE adm_0adm_exportar_rota_aluno
SET adm_aluno_id = (
    SELECT id FROM adm_users WHERE idnumber = concat('a', adm_0adm_exportar_rota_aluno.aluno_id)
);
UPDATE IGNORE adm_0adm_exportar_rota_aluno
SET adm_escola_id = (
    SELECT id FROM adm_escolas WHERE idnumber = adm_0adm_exportar_rota_aluno.escola_id
);
UPDATE IGNORE adm_0adm_exportar_rota_aluno
SET adm_turma_id = (
    SELECT portal_turma.turma_id FROM 0adm_turmas_portal_id_turma_id portal_turma WHERE portal_turma.portal_id = adm_0adm_exportar_rota_aluno.turma_id
);


##############################################################################
################ Atualização de tabela adm_rota_aluno ####################
SELECT '' as 'Atualização de tabela adm_rota_aluno';

DELETE ra
FROM adm_0adm_exportar_rota_aluno ra
INNER JOIN (
    SELECT 
        rota_id,
        aluno_id,
        escola_id,
        turma_id,
        criado_em,
        MIN(id) as id_to_keep
    FROM (
        SELECT 
            rota_id,
            aluno_id,
            escola_id,
            turma_id,
            criado_em,
            id,
            status,
            ROW_NUMBER() OVER (
                PARTITION BY rota_id, aluno_id, escola_id, turma_id, criado_em
                ORDER BY 
                    status = 1 DESC, -- Prioriza status = 1
                    updated_at DESC  -- Se houver múltiplos status = 1, mantém o mais recente
            ) as rn
        FROM adm_0adm_exportar_rota_aluno
    ) t
    WHERE rn = 1
    GROUP BY rota_id, aluno_id, escola_id, turma_id, criado_em
) keep
ON ra.rota_id = keep.rota_id
AND ra.aluno_id = keep.aluno_id
AND ra.escola_id = keep.escola_id
AND ra.turma_id = keep.turma_id
AND ra.criado_em = keep.criado_em
WHERE ra.id != keep.id_to_keep;

# INSERT IGNORE INTO adm_rota_aluno
#     (
#     rota_id,
#     aluno_id,
#     escola_id,
#     turma_id,
#     status,
#     criado_em,
#     modificado_em,
#     created_at,
#     updated_at
#     )
# SELECT
#     adm_rota_id,
#     adm_aluno_id,
#     adm_escola_id,
#     adm_turma_id,
#     status,
#     criado_em,
#     modificado_em,
#     now(),
#     now()
# FROM adm_0adm_exportar_rota_aluno exp_rota_aluno
# WHERE NOT EXISTS (
# SELECT 1 FROM adm_rota_aluno
# WHERE rota_id = exp_rota_aluno.adm_rota_id
# AND aluno_id = exp_rota_aluno.adm_aluno_id
# AND escola_id = exp_rota_aluno.adm_escola_id
# AND turma_id = exp_rota_aluno.adm_turma_id
# AND criado_em = exp_rota_aluno.criado_em
# );

INSERT IGNORE INTO adm_rota_aluno (
    rota_id,
    aluno_id,
    escola_id,
    turma_id,
    status,
    criado_em,
    modificado_em,
    created_at,
    updated_at
)
SELECT
    adm_rota_id,
    adm_aluno_id,
    adm_escola_id,
    adm_turma_id,
    status,
    criado_em,
    modificado_em,
    NOW(),
    NOW()
FROM adm_0adm_exportar_rota_aluno
ON DUPLICATE KEY UPDATE
    status = VALUES(status),
    modificado_em = VALUES(modificado_em),
    updated_at = NOW();
############################## FIM ROTAS


CREATE INDEX index_0temp_alocacao_professor_user_id ON 0temp_alocacao_professor (user_id);
CREATE INDEX index_0temp_alocacao_professor_turma_id ON 0temp_alocacao_professor (turma_id);
CREATE INDEX index_0temp_alocacao_professor_escola_id ON 0temp_alocacao_professor (escola_id);
CREATE INDEX index_0temp_alocacao_professor_disciplina_id ON 0temp_alocacao_professor (disciplina_id);

CREATE INDEX IF NOT EXISTS idx_adm_professor_turma_user_id ON adm_professor_turma (user_id);
CREATE INDEX IF NOT EXISTS idx_adm_professor_turma_turma_id ON adm_professor_turma (turma_id);
CREATE INDEX IF NOT EXISTS idx_adm_professor_turma_escola_id ON adm_professor_turma (escola_id);
CREATE INDEX IF NOT EXISTS idx_adm_professor_turma_disciplina_id ON adm_professor_turma (disciplina_id);

CREATE INDEX idx_composto1 ON 0temp_alocacao_professor (turma_id, escola_id);
CREATE INDEX idx_composto2 ON 0temp_alocacao_professor (turma_id, disciplina_id);
CREATE INDEX idx_composto3 ON 0temp_alocacao_professor (turma_id, user_id, disciplina_id);
CREATE INDEX idx_composto4 ON 0temp_alocacao_professor (escola_id, user_id, disciplina_id);


-- matrizes específicas e disciplinas base comum
INSERT IGNORE INTO 0temp_alocacao_professor(user_id, turma_id, escola_id, disciplina_id)
SELECT
ex.adm_user_id,
ex.adm_turma_id,
ex.adm_escola_id,
dm.disciplina_id
from 0adm_exportar_professores ex
join adm_serie_turma st on st.turma_id = ex.adm_turma_id
join adm_turmas as t on t.id = st.turma_id
join adm_matrizes m on m.serie_id = st.serie_id
left join adm_disciplina_matriz dm on m.id = dm.matriz_id
WHERE ex.adm_disciplina_id is null
and ex.adm_user_id is not null
and ex.adm_turma_id is not null
and ex.adm_escola_id is not null
#and m.escola_id = t.escola_id
and m.id = st.matriz_id
and dm.base_comum = 1
and NOT EXISTS (
    SELECT 1 from 0temp_alocacao_professor not_pt
    where escola_id = ex.adm_escola_id
    and turma_id = ex.adm_turma_id
    and user_id = ex.adm_user_id
    and disciplina_id = dm.disciplina_id
)
group by ex.adm_escola_id, ex.adm_turma_id, ex.adm_user_id, dm.disciplina_id;

-- disciplinas especificas
INSERT IGNORE INTO 0temp_alocacao_professor(user_id, turma_id, escola_id, disciplina_id)
SELECT
ex.adm_user_id,
ex.adm_turma_id,
ex.adm_escola_id,
ex.adm_disciplina_id
from 0adm_exportar_professores ex
WHERE ex.adm_disciplina_id is not null
and ex.adm_user_id is not null
and ex.adm_turma_id is not null
and ex.adm_escola_id is not null
and NOT EXISTS (
    SELECT 1 from 0temp_alocacao_professor not_pt
    where escola_id = ex.adm_escola_id
    and turma_id = ex.adm_turma_id
    and user_id = ex.adm_user_id
    and disciplina_id = ex.adm_disciplina_id
)
group by ex.adm_escola_id, ex.adm_turma_id, ex.adm_user_id, ex.adm_disciplina_id;

####################################################
########## FIM Tabelas temporarias    ##############
########## FIM Tabelas temporarias    ##############
####################################################
####################################################
####################################################
####################################################

SELECT '' as 'Removendo alocações de professores - professor_turma';

UPDATE adm_professor_turma AS t1
join adm_turmas t on t.id = t1.turma_id
SET t1.ativo = 0
WHERE NOT EXISTS (
    SELECT 1 FROM 0temp_alocacao_professor not_t2
    where not_t2.escola_id = t1.escola_id
    AND not_t2.turma_id = t1.turma_id
    AND not_t2.user_id = t1.user_id
    AND not_t2.disciplina_id = t1.disciplina_id
)
AND t.calendario_id = $calendario_id
AND t.escola_id <> 142;


############### Alocar professores
SELECT '' as 'Alocando professores - professor_turma';

INSERT IGNORE INTO adm_professor_turma(user_id, turma_id, escola_id, disciplina_id, ativo, ator, created_at, updated_at)
SELECT
    temp.user_id,
    temp.turma_id,
    temp.escola_id,
    temp.disciplina_id,
    1,
    1,
    NOW(),
    NOW()
FROM 0temp_alocacao_professor temp
LEFT JOIN adm_professor_turma pt
    ON temp.escola_id = pt.escola_id
    AND temp.turma_id = pt.turma_id
    AND temp.user_id = pt.user_id
    AND temp.disciplina_id = pt.disciplina_id
    AND pt.ativo = 1
WHERE pt.escola_id IS NULL;

SELECT '' as 'FIM Alocando professores - professor_turma';


################################################### FIM Alocação de professores em turmas
################################################### FIM Alocação de professores em turmas
################################################### FIM Alocação de professores em turmas
################################################### FIM Alocação de professores em turmas


UPDATE adm_turmas t
SET ativo = 0
where NOT EXISTS (SELECT 1 from 0adm_exportar_turmas exp where t.id = exp.adm_turma_id)
and t.calendario_id = $calendario_id;


SELECT '' as 'Atualiza turmas turmas existentes';
UPDATE adm_turmas t
join 0adm_exportar_turmas expt on expt.adm_turma_id = t.id
SET descricao = expt.serie__nome,
t.turma  = substr(expt.turma__nome, -1),
t.escola_id = expt.adm_escola_id,
t.multi = case when expt.multi = 'True' then 1 else 0 end,
t.sala_id = expt.adm_sala_id,
t.turno_id = expt.adm_turno_id,
t.programa_id = null,
t.modalidade = expt.modalidade,
t.ator = 1,
t.ativo = 1
WHERE calendario_id = $calendario_id
and expt.multi like '%False%';

# Atualiza turmas existentes MULTISSERIADA
UPDATE adm_turmas t
join 0adm_exportar_turmas expt on expt.adm_turma_id = t.id
SET descricao = expt.turma__nome,
t.turma  = ' ',
t.escola_id = expt.adm_escola_id,
t.multi = case when expt.multi = 'True' then 1 else 0 end,
t.sala_id = expt.adm_sala_id,
t.turno_id = expt.adm_turno_id,
t.programa_id = null,
t.modalidade = expt.modalidade,
t.ator = 1,
t.ativo = 1
WHERE calendario_id = $calendario_id
and expt.multi like '%True%';

# Atualizar Status de matriculas de alunos ausentes do CSV para transferidos
SELECT '' as 'Atualizar Status de matriculas de alunos ausentes do CSV para transferidos';

UPDATE adm_matriculas m
SET statusmatricula_id = 2
WHERE calendario_id = $calendario_id
and statusmatricula_id <> 8
and NOT EXISTS (SELECT 1 FROM 0adm_exportar_alunos exp where exp.adm_user_id = m.user_id);

# Atualiza dados de matriculas
SELECT '' as 'Atualiza dados de matriculas';

UPDATE adm_matriculas m
join 0adm_exportar_alunos a on a.adm_user_id = m.user_id
join adm_turmas t on t.id = a.adm_turma_id
SET
m.escola_id = a.adm_escola_id,
m.etapa_id = a.adm_etapa_id,
m.serie_id_base = a.adm_serie_id,
m.serie_id = a.adm_serie_id,
m.turno_id = a.adm_turno_id,
m.statusmatricula_id = a.adm_statusmatricula_id,
m.transporte_publico = case when a.utiliza_transporte = 'True' then 1 else 0 end,
m.updated_at = now()
WHERE m.calendario_id = $calendario_id
and m.statusmatricula_id <> 8
and t.calendario_id = $calendario_id
and a.adm_etapa_id is not null
and a.adm_serie_id is not null;

# Atualiza informações de alunos
UPDATE adm_users u
join 0adm_exportar_alunos a on a.adm_user_id = u.id
SET
u.ne = case when a.aluno__possui_deficiencia = 'True' then 1 else 0 end,
u.name = a.aluno__nome,
u.nome_social = a.aluno__nome_social,
u.usar_nome_social = case when a.aluno__utiliza_nome_social = 'True' then 1 else 0 end,
u.nome_responsavel = a.responsavel__nome,
u.cor_raca = a.adm_cor_raca_id,
u.data_nascimento = case when a.aluno__data_nascimento = '' then NULL else a.aluno__data_nascimento end
;


################### Criar vínculos de cargos de professores na tabela vínculos
INSERT IGNORE INTO adm_vinculos 
(
cargo_id,
user_id,
situacao,
created_at,
updated_at
)
SELECT 
21,
adm_user_id,
1,
now(),
now()
FROM 0adm_exportar_professores ext
WHERE NOT EXISTS 
	(SELECT 1 FROM adm_vinculos 
 	where cargo_id = 21
	and user_id = ext.adm_user_id)
and adm_user_id is not null
group by adm_user_id;

#############################################################################################################
SELECT '' as 'Atualizar alunos autistas';
UPDATE adm_users u
SET u.autista = CASE 
    WHEN EXISTS (
        SELECT 1 
        FROM adm_0adm_exportar_alunos_autistas eaa
        JOIN 0adm_exportar_alunos a ON a.aluno__id = eaa.aluno__id
        WHERE a.adm_user_id = u.id
    ) THEN true 
    ELSE false 
END;

################## Scripts Finalizado!
SELECT '' as 'Scripts Finalizados';

"
echo "Hora incial: $hora_inicial"
hora_atual=$(date +"%H:%M:%S")
echo "Hora atual: $hora_atual"
