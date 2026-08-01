# mysql juazeiro33 -u juazeiro33 -pGogo1352 -e "
pre=adm_



cd csvs/ || { clear; echo "Erro: Este script deve ser executado na pasta SCRIPTS"; exit 127; }

# wget -O exportar_alunos.csv --no-check-certificate https://matricula.juazeiro.ba.gov.br/relatorios/exportar_alunos_csv?chave=SIEJaoDahFeh21
wget -O exportar_equipe_escolar.csv --no-check-certificate https://matricula.juazeiro.ba.gov.br/relatorios/exportar_equipe_escolar_csv?chave=SIEJaoDahFeh21
wget -O exportar_professores.csv --no-check-certificate https://matricula.juazeiro.ba.gov.br/relatorios/exportar_professores_csv?chave=SIEJaoDahFeh21
wget -O exportar_turmas.csv --no-check-certificate https://matricula.juazeiro.ba.gov.br/relatorios/turmas_gestao_escolar_csv?chave=SIEJaoDahFeh21

fnames=(exportar_alunos exportar_professores exportar_equipe_escolar exportar_turmas)

mysql juazeiro33 -u juazeiro33 -pGogo1352 -e "
SELECT '' as 'CRIAÇÂO DE TABELAS';
"

for fname in "${fnames[@]}"
do

sed -i '/^$/d' "$fname".csv #remove linhas vazias
sed -i 's/"\(.*\),\(.*\)"/\1 \2/g' "$fname".csv
sed 's/\s*,*\s*$//g' "$fname".csv > tmp.csv

tableName=$(echo "$fname" | cut -d"." -f 1 | sed 's/-tmp//g')
tableName="\`$pre$tableName\`"
columnsNames=$(head --lines=1 tmp.csv | sed 'y/áÁàÀãÃâÂéÉêÊíÍóÓõÕôÔúÚçÇ .-/aAaAaAaAeEeEiIoOoOoOuUcC___/' | sed 's/\[//g' | sed 's/\]//g' | sed 's/(//g'  | sed 's/)//g' |  sed 's/,/` VARCHAR(255),`/g' | tr -d "\r\n")
columnsNames="\`$columnsNames\` VARCHAR(255)"
columnsNames=$(echo "$columnsNames" | sed 's/,`` VARCHAR(255)//g') #remove nomes colunas vazias

mysql juazeiro33 -u juazeiro33 -pGogo1352 -e "

############## Criação de TABELAS
DROP TABLE IF EXISTS $tableName;
CREATE TABLE IF NOT EXISTS $tableName($columnsNames);

LOAD DATA LOCAL INFILE '$fname.csv' INTO TABLE $tableName
CHARACTER SET UTF8
FIELDS TERMINATED BY ','
LINES TERMINATED BY '\n'
IGNORE 1 LINES;

alter table $tableName convert to character set utf8mb4 collate utf8mb4_unicode_ci;
ALTER TABLE $tableName ADD COLUMN \`id\` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT FIRST

"
rm tmp.csv

done

mysql juazeiro33 -u juazeiro33 -pGogo1352 -e "




############## Criação de INDEX
SELECT '' as 'CRIAÇÂO DE INDEX';

CREATE INDEX IF NOT EXISTS idx_aluno__id ON adm_exportar_alunos(aluno__id);
CREATE INDEX IF NOT EXISTS idx_aluno__responsavel__cpf ON adm_exportar_alunos(responsavel__cpf);
CREATE INDEX IF NOT EXISTS idx_escola__inep ON adm_exportar_alunos(escola__inep);
CREATE INDEX IF NOT EXISTS idx_escola_id ON adm_exportar_alunos(escola__id);
CREATE INDEX IF NOT EXISTS idx_sala_id ON adm_exportar_alunos(sala__id);
CREATE INDEX IF NOT EXISTS idx_turma_id ON adm_exportar_alunos(turma__id);
CREATE INDEX IF NOT EXISTS idx_turma_nome ON adm_exportar_alunos(turma__nome);
CREATE INDEX IF NOT EXISTS idx_turno_id ON adm_exportar_alunos(turno__id);

CREATE INDEX IF NOT EXISTS idx_idnumber ON adm_users(idnumber);
CREATE INDEX IF NOT EXISTS idx_idnumber ON adm_turmas(idnumber);
CREATE INDEX IF NOT EXISTS idx_idnumber ON adm_escolas(idnumber);
CREATE INDEX IF NOT EXISTS idx_idnumber ON adm_disciplinas(idnumber);


ALTER TABLE adm_exportar_professores MODIFY COLUMN professor__id varchar(11);
ALTER TABLE adm_exportar_professores MODIFY COLUMN escola__id varchar(11);
ALTER TABLE adm_exportar_professores MODIFY COLUMN etapa__id varchar(11);
ALTER TABLE adm_exportar_professores MODIFY COLUMN serie__id varchar(11);
ALTER TABLE adm_exportar_professores MODIFY COLUMN turma__id varchar(11);
ALTER TABLE adm_exportar_professores MODIFY COLUMN turno__id varchar(11);
ALTER TABLE adm_exportar_professores MODIFY COLUMN disciplina__id varchar(11);

CREATE INDEX IF NOT EXISTS idx_escola__inep ON adm_exportar_turmas(escola__inep);
CREATE INDEX IF NOT EXISTS idx_escola_id ON adm_exportar_turmas(escola__id);
CREATE INDEX IF NOT EXISTS idx_sala_id ON adm_exportar_turmas(sala__id);
CREATE INDEX IF NOT EXISTS idx_turma_id ON adm_exportar_turmas(turma__id);
CREATE INDEX IF NOT EXISTS idx_turma_nome ON adm_exportar_turmas(turma__nome);
CREATE INDEX IF NOT EXISTS idx_turno_id ON adm_exportar_turmas(turno__id);

CREATE INDEX IF NOT EXISTS idx_funcionario__id ON adm_exportar_equipe_escolar(funcionario__id);
CREATE INDEX IF NOT EXISTS idx_funcionario__cpf ON adm_exportar_equipe_escolar(funcionario__cpf);
CREATE INDEX IF NOT EXISTS idx_escola__id ON adm_exportar_equipe_escolar(escola__id);

CREATE INDEX IF NOT EXISTS idx_professor__id ON adm_exportar_professores(professor__id);
CREATE INDEX IF NOT EXISTS idx_professor__cpf ON adm_exportar_professores(professor__cpf);
CREATE INDEX IF NOT EXISTS idx_escola__inep ON adm_exportar_professores(escola__inep);
CREATE INDEX IF NOT EXISTS idx_ano_letivo ON adm_exportar_professores(ano_letivo);
CREATE INDEX IF NOT EXISTS idx_turma__id ON adm_exportar_professores(turma__id);
CREATE INDEX IF NOT EXISTS idx_escola__id ON adm_exportar_professores(escola__id);
CREATE INDEX IF NOT EXISTS idx_disciplina__id ON adm_exportar_professores(disciplina__id);

SELECT '' as 'FINALIZADO CRIAÇÂO DE INDEX';

# Ajusta CPF
UPDATE adm_exportar_alunos
SET
responsavel__cpf = replace(replace(responsavel__cpf, '.', ''), '-', '')
WHERE responsavel__cpf <> ''
and responsavel__cpf = responsavel__cpf;

# Ajusta CPF
UPDATE adm_exportar_equipe_escolar
SET
funcionario__cpf = replace(replace(funcionario__cpf, '.', ''), '-', '')
WHERE funcionario__cpf <> ''
and funcionario__cpf = funcionario__cpf;

# Ajusta CPF
UPDATE adm_exportar_professores
SET professor__cpf = replace(replace(professor__cpf, '.', ''), '-', '')
WHERE professor__cpf <> ''
and professor__cpf = professor__cpf;

# Ajusta ID Lingua Estrangeira Moderna Inglês para Língua Inglesa
UPDATE adm_exportar_professores
SET disciplina__id = 39
WHERE disciplina__id = '9';

# Ajusta CPF
UPDATE adm_users
SET cpf = replace(replace(cpf, '.', ''), '-', '')
WHERE cpf <> ''
and cpf = cpf;

########################
# Atualizar CPF para 11 dígitos USERS
UPDATE adm_users
SET cpf = LPAD(cpf, 11, 0)
WHERE length(cpf) < 11
and cpf <> ''
and cpf = cpf;

# Atualizar CPF para 11 dígitos EXPORTAR_ALUNOS
UPDATE adm_exportar_alunos
SET responsavel__cpf = LPAD(responsavel__cpf, 11, 0)
WHERE length(responsavel__cpf) < 11
and responsavel__cpf <> ''
and responsavel__cpf = responsavel__cpf;


# Atualizar CPF para 11 dígitos EQUIPE ESCOLAR
UPDATE adm_exportar_equipe_escolar
SET funcionario__cpf = LPAD(funcionario__cpf, 11, 0)
WHERE length(funcionario__cpf) < 11
and funcionario__cpf <> ''
and funcionario__cpf = funcionario__cpf;


############# Criação de SALAS inexistentes
SELECT '' as 'Criação de SALAS inexistentes';

Insert INTO adm_salas
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
FROM adm_exportar_alunos a
join adm_escolas e on e.idnumber = a.escola__id
WHERE NOT EXISTS (SELECT idnumber FROM adm_salas WHERE idnumber = a.sala__id)
and a.sala__id <> ''
group by a.sala__id;

# ############## Criação de TURMAS inexistentes
SELECT '' as 'Criação de TURMAS inexistentes';

Insert INTO adm_turmas
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
    ator,
    ativo,
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
    1 as ator,
    1 as ativo,
    now(),
    now()
FROM adm_exportar_turmas tu
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

Insert INTO adm_turmas
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
    ator,
    ativo,
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
    1 as ator,
    1 as ativo,
    now(),
    now()
FROM adm_exportar_turmas tu
join adm_escolas e on e.idnumber = tu.escola__id
join adm_calendarios c on c.ano = tu.ano__letivo
join adm_salas s on s.escola_id = e.id and s.idnumber = tu.sala__id
join adm_turnos t on t.idnumber = tu.turno__id
WHERE NOT EXISTS
(SELECT idnumber FROM adm_turmas
WHERE idnumber = concat(tu.ano__letivo, '_', tu.escola__id, '_', tu.sala__id, '_', tu.turno__id))
and tu.turma__nome like '%MULTI%'
and tu.turma__id <> ''
group by tu.ano__letivo, tu.escola__id, tu.sala__id, tu.turno__id;


################################################## Atualiza tabela esportar_alunos com ID adm
SELECT '' as 'Atualiza tabela esportar_alunos com ID adm';
-- cria colunas
ALTER TABLE adm_exportar_alunos ADD COLUMN IF NOT EXISTS adm_serie_id VARCHAR(20) AFTER id;
ALTER TABLE adm_exportar_alunos ADD COLUMN IF NOT EXISTS adm_turma_id VARCHAR(20) AFTER id;
ALTER TABLE adm_exportar_alunos ADD COLUMN IF NOT EXISTS adm_user_id INT AFTER id;
ALTER TABLE adm_exportar_alunos ADD COLUMN IF NOT EXISTS adm_escola_id INT AFTER id;

UPDATE adm_exportar_alunos AS t1
INNER JOIN adm_escolas AS t2 ON t1.escola__id = t2.idnumber
SET t1.adm_escola_id = t2.id;
ALTER TABLE adm_exportar_alunos ADD INDEX IF NOT EXISTS adm_escola_id_index (adm_escola_id);

UPDATE adm_exportar_alunos AS t1
INNER JOIN adm_turmas AS t2 ON t1.turma__id = t2.idnumber
SET t1.adm_turma_id = t2.id;
ALTER TABLE adm_exportar_alunos ADD INDEX IF NOT EXISTS adm_turma_id_index (adm_turma_id);

# turmas Multisseriadas
UPDATE adm_exportar_alunos AS t1
INNER JOIN adm_turmas AS t2 ON concat(t1.ano_letivo, '_', t1.escola__id, '_', t1.sala__id, '_', t1.turno__id) = t2.idnumber
SET t1.adm_turma_id = t2.id
where t1.turma__nome like '%MULTI%';
ALTER TABLE adm_exportar_alunos ADD INDEX IF NOT EXISTS adm_turma_id_index (adm_turma_id);

UPDATE adm_exportar_alunos AS t1
INNER JOIN adm_series AS t2 ON t1.serie__id = t2.idnumber
SET t1.adm_serie_id = t2.id;
ALTER TABLE adm_exportar_alunos ADD INDEX IF NOT EXISTS adm_serie_id_index (adm_serie_id);
################################################## FIM Atualiza tabela esportar_alunos com ID adm


################################################## Atualiza tabela esportar_alunos com ID adm
SELECT '' as 'Atualiza tabela esportar_turmas com ID adm';
-- cria colunas
ALTER TABLE adm_exportar_turmas ADD COLUMN IF NOT EXISTS adm_serie_id VARCHAR(20) AFTER id;
ALTER TABLE adm_exportar_turmas ADD COLUMN IF NOT EXISTS adm_turma_id VARCHAR(20) AFTER id;
ALTER TABLE adm_exportar_turmas ADD COLUMN IF NOT EXISTS adm_user_id INT AFTER id;
ALTER TABLE adm_exportar_turmas ADD COLUMN IF NOT EXISTS adm_escola_id INT AFTER id;

UPDATE adm_exportar_turmas AS t1
INNER JOIN adm_escolas AS t2 ON t1.escola__id = t2.idnumber
SET t1.adm_escola_id = t2.id;
ALTER TABLE adm_exportar_turmas ADD INDEX IF NOT EXISTS adm_escola_id_index (adm_escola_id);

UPDATE adm_exportar_turmas AS t1
INNER JOIN adm_turmas AS t2 ON t1.turma__id = t2.idnumber
SET t1.adm_turma_id = t2.id;
ALTER TABLE adm_exportar_turmas ADD INDEX IF NOT EXISTS adm_turma_id_index (adm_turma_id);

# turmas Multisseriadas
UPDATE adm_exportar_turmas AS t1
INNER JOIN adm_turmas AS t2 ON concat(t1.ano__letivo, '_', t1.escola__id, '_', t1.sala__id, '_', t1.turno__id) = t2.idnumber
SET t1.adm_turma_id = t2.id
where t1.turma__nome like '%MULTI%';
ALTER TABLE adm_exportar_turmas ADD INDEX IF NOT EXISTS adm_turma_id_index (adm_turma_id);

UPDATE adm_exportar_turmas AS t1
INNER JOIN adm_series AS t2 ON t1.serie__id = t2.idnumber
SET t1.adm_serie_id = t2.id;
ALTER TABLE adm_exportar_turmas ADD INDEX IF NOT EXISTS adm_serie_id_index (adm_serie_id);
################################################## FIM Atualiza tabela esportar_alunos com ID adm


############## VINCULOS SERIE_TURMA
SELECT '' as 'VINCULOS SERIE_TURMA';

Insert INTO adm_serie_turma
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
FROM adm_exportar_turmas ext
join adm_calendarios c on c.ano = ext.ano__letivo
WHERE NOT EXISTS (SELECT serie_id, turma_id FROM adm_serie_turma
WHERE serie_id = ext.adm_serie_id
and turma_id = ext.adm_turma_id)
and ext.turma__nome not like '%MULTI%'
and ext.adm_turma_id is not null
and ext.adm_serie_id is not null
group by ext.adm_turma_id, ext.adm_serie_id;


############## Insere professores em ADM_USERS não existentes
INSERT INTO adm_users
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
FROM adm_exportar_professores p
WHERE NOT EXISTS (SELECT idnumber FROM adm_users WHERE idnumber = p.professor__id)
and p.professor__nome <> 'Vag1a Real'
and p.professor__nome <> 'Vag2a Real'
group by p.professor__id;


############## Insere alunos em ADM_USERS não existentes
SELECT '' as 'Insere alunos em ADM_USERS não existentes';


INSERT INTO adm_users
	(
    name,
    email,
    username,
    avatar,
    password,
    idnumber,
    data_nascimento,
    nome_responsavel,
    cpf_responsavel,
    ne
	)
SELECT
a.aluno__nome,
concat('aluno.','sem_email', a.aluno__id, '@' 'juazeiro.ba.gov.br') as email,
concat('a', a.aluno__id),
'img_avatar.png',
'aluno2023',
concat('a', a.aluno__id) as idnumber,
case when a.aluno__data_nascimento = '' then '1900-01-01' else a.aluno__data_nascimento end,
a.responsavel__nome,
replace(replace(a.responsavel__cpf, '.', ''), '-', ''),
case when a.aluno__possui_deficiencia = 'true' then 1 else 0 end
from adm_exportar_alunos a
where NOT EXISTS
(select idnumber from adm_users where idnumber = concat('a',a.aluno__id))
group by a.aluno__id;


############## Matricular alunos
SELECT '' as 'Matricular alunos';


INSERT INTO adm_matriculas
    (
    user_id,
    calendario_id,
    matricula,
    escola_id,
    etapa_id,
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
    concat(c.ano, LPAD(replace(u.idnumber, 'a', ''), 8, 0)) as matricula,
    e.id,
    s.etapa_id,
    s.id,
    t.id,
    1 as statusmatricula_id,
    'Não Informado' as escola_anterior,
    case when a.utiliza_transporte = 'true' then 1 else 0 end,
    0 as tipo_matricula,
    concat('', now()),
    now()
from adm_exportar_alunos a
join adm_calendarios c on c.ano = a.ano_letivo
join adm_users u on u.idnumber = concat('a',a.aluno__id)
join adm_escolas e on e.idnumber = a.escola__id
join adm_turnos t on t.idnumber = a.turno__id
join adm_series s on s.idnumber = a.serie__id
where NOT EXISTS (select user_id, calendario_id from adm_matriculas
where user_id = u.id
and calendario_id = c.id)
group by a.aluno__id;


################################################## Atualiza tabela esportar_alunos com ID adm
SELECT '' as 'Atualiza tabela esportar_alunos com ID adm para adm_user';

-- atualiza colunas
UPDATE adm_exportar_alunos AS t1
INNER JOIN adm_users AS t2 ON concat('a', t1.aluno__id) = t2.idnumber
SET t1.adm_user_id = t2.id;
ALTER TABLE adm_exportar_alunos ADD INDEX IF NOT EXISTS adm_user_id_index (adm_user_id);
################################################## FIM Atualiza tabela esportar_alunos com ID adm


############## Alocar alunos
SELECT '' as 'Alocar alunos';

INSERT INTO adm_aluno_turma
    (
    user_id,
    turma_id,
    created_at,
    updated_at
    )
	SELECT
        u.id,
        t.id,
        concat('', now()),
        now()
    from adm_exportar_alunos a
        join adm_users u on u.idnumber = concat('a',aluno__id)
        join adm_matriculas m on m.user_id = u.id
        join adm_turmas t on t.idnumber = a.turma__id
        join adm_calendarios c on c.ano = a.ano_letivo
    where NOT EXISTS (select user_id, turma_id from adm_aluno_turma
    where user_id = u.id
    and turma_id = t.id)
    and c.ano = a.ano_letivo
    and t.descricao not like '%MULTI%'
    GROUP BY a.aluno__id;


############## Alocar alunos em turmas multi
SELECT '' as 'Alocar alunos em turmas multi';

INSERT INTO adm_aluno_turma
    (
    user_id,
    turma_id,
    created_at,
    updated_at
    )
	SELECT
        u.id,
        t.id,
        concat('', now()),
        now()
    from adm_exportar_alunos a
        join adm_calendarios c on c.ano = a.ano_letivo
        join adm_users u on u.idnumber = concat('a',aluno__id)
        join adm_matriculas m on m.user_id = u.id
        join adm_escolas e on e.idnumber = a.escola__id
        join adm_salas sa on sa.idnumber = a.sala__id and sa.escola_id = e.id
        join adm_turnos tu on tu.idnumber = a.turno__id
        join adm_turmas t on t.sala_id = sa.id
    where NOT EXISTS (select user_id, turma_id from adm_aluno_turma
    where user_id = u.id
    and turma_id = t.id)
    and t.descricao like '%MULTI%'
    GROUP BY a.aluno__id;


# ############## remove alocações de professores
SET @variavel_ano_letivo := (SELECT ano_letivo FROM adm_exportar_professores LIMIT 1);
SET @variavel_calendario_id := (SELECT id FROM adm_calendarios c where c.ano = @variavel_ano_letivo LIMIT 1);
DELETE pt FROM adm_professor_turma pt
join adm_turmas t on t.id = pt.turma_id
where t.calendario_id = @variavel_calendario_id;


############## Alocar professores
SELECT '' as 'Alocar professores';

################################################### Alocação de professores em turmas
-- cria colunas
ALTER TABLE adm_exportar_professores ADD COLUMN IF NOT EXISTS adm_disciplina_id INT AFTER id;
ALTER TABLE adm_exportar_professores ADD COLUMN IF NOT EXISTS adm_turma_id VARCHAR(20) AFTER id;
ALTER TABLE adm_exportar_professores ADD COLUMN IF NOT EXISTS adm_user_id INT AFTER id;
ALTER TABLE adm_exportar_professores ADD COLUMN IF NOT EXISTS adm_escola_id INT AFTER id;

UPDATE adm_exportar_professores AS t1
INNER JOIN adm_escolas AS t2 ON t1.escola__id = t2.idnumber
SET t1.adm_escola_id = t2.id;
ALTER TABLE adm_exportar_professores ADD INDEX IF NOT EXISTS adm_escola_id_index (adm_escola_id);

-- atualiza colunas
UPDATE adm_exportar_professores AS t1
INNER JOIN adm_users AS t2 ON t1.professor__id = t2.idnumber
SET t1.adm_user_id = t2.id;
ALTER TABLE adm_exportar_professores ADD INDEX IF NOT EXISTS adm_user_id_index (adm_user_id);

UPDATE adm_exportar_professores AS t1
INNER JOIN adm_turmas AS t2 ON t1.turma__id = t2.idnumber
SET t1.adm_turma_id = t2.id;
ALTER TABLE adm_exportar_professores ADD INDEX IF NOT EXISTS adm_turma_id_index (adm_turma_id);

UPDATE adm_exportar_professores AS t1
INNER JOIN adm_disciplinas AS t2 ON t1.disciplina__id = t2.idnumber
SET t1.adm_disciplina_id = t2.id;
ALTER TABLE adm_exportar_professores ADD INDEX IF NOT EXISTS adm_disciplina_id_index (adm_disciplina_id);

-- matrizes comum e disciplinas polivalentes
INSERT INTO adm_professor_turma(user_id, turma_id, escola_id, disciplina_id, ativo, ator, created_at, updated_at)
SELECT
ex.adm_user_id,
ex.adm_turma_id,
ex.adm_escola_id,
dm.disciplina_id,
1,
1,
now(),
now()
from adm_exportar_professores ex
join adm_serie_turma st on st.turma_id = ex.adm_turma_id
join adm_turmas as t on t.id = st.turma_id
join adm_matrizes m on m.serie_id = st.serie_id
left join adm_disciplina_matriz dm on m.id = dm.matriz_id
WHERE ex.adm_disciplina_id is null
and ex.adm_user_id is not null
and ex.adm_turma_id is not null
and ex.adm_escola_id is not null
and m.escola_id = 0
group by ex.adm_escola_id, ex.adm_turma_id, ex.adm_user_id, dm.disciplina_id;

-- matrizes específicas e disciplinas polivalentes
INSERT INTO adm_professor_turma(user_id, turma_id, escola_id, disciplina_id, ativo, ator, created_at, updated_at)
SELECT
ex.adm_user_id,
ex.adm_turma_id,
ex.adm_escola_id,
dm.disciplina_id,
1,
1,
now(),
now()
from adm_exportar_professores ex
join adm_serie_turma st on st.turma_id = ex.adm_turma_id
join adm_turmas as t on t.id = st.turma_id
join adm_matrizes m on m.serie_id = st.serie_id
left join adm_disciplina_matriz dm on m.id = dm.matriz_id
WHERE ex.adm_disciplina_id is null
and ex.adm_user_id is not null
and ex.adm_turma_id is not null
and ex.adm_escola_id is not null
and m.escola_id = t.escola_id
and m.id = st.matriz_id
group by ex.adm_escola_id, ex.adm_turma_id, ex.adm_user_id, dm.disciplina_id;

-- disciplinas especificas
INSERT INTO adm_professor_turma(user_id, turma_id, escola_id, disciplina_id, ativo, ator, created_at, updated_at)
SELECT
ex.adm_user_id,
ex.adm_turma_id,
ex.adm_escola_id,
ex.adm_disciplina_id,
1,
1,
now(),
now()
from adm_exportar_professores ex
WHERE ex.adm_disciplina_id is not null
and ex.adm_user_id is not null
and ex.adm_turma_id is not null
and ex.adm_escola_id is not null
group by ex.adm_escola_id, ex.adm_turma_id, ex.adm_user_id, ex.adm_disciplina_id;
################################################### FIM Alocação de professores em turmas

################## Scripts Finalizado!
SELECT '' as 'Scripts Finalizados';

"
