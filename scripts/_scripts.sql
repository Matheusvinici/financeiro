-- Insere Usuários
INSERT INTO users (role_id,name,email,password)
SELECT 2, concat(First_Name_[Required], ' ', Last_Name_[Required]), Email_Address_[Required], Password_[Required] FROM ep.mdl0_workspace
where ep.mdl0_workspace.Email_Address_[Required] not in (select email from users)


Gerar arquivos para tabela PIVOT
php artisan make:model -mcrp TableName


Calcular distancia
SELECT
	l.id,
    l.lat,
    l.lng,
    ( 3959 * acos( cos( radians(c.lat) )
              * cos( radians( l.lat ) )
              * cos( radians( l.lng ) - radians(c.lng) )
              + sin( radians(c.lat) )
              * sin( radians( l.lat ) ) ) ) AS distancia
FROM
	locais AS l
    JOIN (
      SELECT
      	-20.282957 AS lat,
      	-40.401991 AS lng
    ) AS c
ORDER BY
	distancia


>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
-- Insere vinculos professores a partir da SEDUC
INSERT INTO adm_vinculos(matricula, cargo_id, data_admissao, tipo_vinculo, fator_alocacao, regime_trabalho, nivel, situacao, user_id, created_at, updated_at)
select
MATRICULA_DO_SERVIDOR_SEM_O_DIGITO,
21, -- ID da função/cargo
STR_TO_DATE(DATA_DE_ADMISSAO, '%d/%m/%Y'),
CASE
when VINCULO = 'Cargo em Comissão' then 7
when VINCULO = 'Concursado' then 2
when VINCULO = 'Efetivo' then 3
when VINCULO = 'Trabalhador Temporário' then 1
END,
'1/1',
40,
0,
1,
u.id,
now(),
now()
from adm_mdl0_servidores s
join adm_users u on u.cpf = s.CPF
where s.FUNCAO_EXERCIDA like '%Professor%'
and SITUACAO = "ATIVO"
group by s.CPF;


-- Insere vinculos secretario a partir da SEDUC
INSERT INTO adm_vinculos(matricula, cargo_id, data_admissao, tipo_vinculo, fator_alocacao, regime_trabalho, nivel, situacao, user_id, created_at, updated_at)

select
MATRICULA_DO_SERVIDOR_SEM_O_DIGITO,
17, -- ID da função/cargo
STR_TO_DATE(DATA_DE_ADMISSAO, '%d/%m/%Y'),
CASE
when VINCULO = 'Cargo em Comissão' then 7
when VINCULO = 'Concursado' then 2
when VINCULO = 'Efetivo' then 3
when VINCULO = 'Trabalhador Temporário' then 1
END,
'1/1',
40,
0,
1,
u.id,
now(),
now()
from adm_mdl0_servidores s
join adm_users u on u.cpf = s.CPF
where s.FUNCAO_EXERCIDA like '%Secretario%'
and SITUACAO = "ATIVO"
group by s.CPF;


-- Insere vinculos coordenadro a partir da SEDUC
INSERT INTO adm_vinculos(matricula, cargo_id, data_admissao, tipo_vinculo, fator_alocacao, regime_trabalho, nivel, situacao, user_id, created_at, updated_at)

select
MATRICULA_DO_SERVIDOR_SEM_O_DIGITO,
19, -- ID da função/cargo
STR_TO_DATE(DATA_DE_ADMISSAO, '%d/%m/%Y'),
CASE
when VINCULO = 'Cargo em Comissão' then 7
when VINCULO = 'Concursado' then 2
when VINCULO = 'Efetivo' then 3
when VINCULO = 'Trabalhador Temporário' then 1
END,
'1/1',
40,
0,
1,
u.id,
now(),
now()
from adm_mdl0_servidores s
join adm_users u on u.cpf = s.CPF
where s.FUNCAO_EXERCIDA like 'COORDENADOR PEDAGOGICO%'
and SITUACAO = "ATIVO"
group by s.CPF;


-- Insere vinculos Gestor a partir da SEDUC
INSERT INTO adm_vinculos(matricula, cargo_id, data_admissao, tipo_vinculo, fator_alocacao, regime_trabalho, nivel, situacao, user_id, created_at, updated_at)

select
MATRICULA_DO_SERVIDOR_SEM_O_DIGITO,
18, -- ID da função/cargo
STR_TO_DATE(DATA_DE_ADMISSAO, '%d/%m/%Y'),
CASE
when VINCULO = 'Cargo em Comissão' then 7
when VINCULO = 'Concursado' then 2
when VINCULO = 'Efetivo' then 3
when VINCULO = 'Trabalhador Temporário' then 1
END,
'1/1',
40,
0,
1,
u.id,
now(),
now()
from adm_mdl0_servidores s
join adm_users u on u.cpf = s.CPF
where s.FUNCAO_EXERCIDA like 'Diretor%'
and SITUACAO = "ATIVO"
group by s.CPF;


-- Insere vinculos Vice Gestor a partir da SEDUC
INSERT INTO adm_vinculos(matricula, cargo_id, data_admissao, tipo_vinculo, fator_alocacao, regime_trabalho, nivel, situacao, user_id, created_at, updated_at)

select
MATRICULA_DO_SERVIDOR_SEM_O_DIGITO,
20, -- ID da função/cargo
STR_TO_DATE(DATA_DE_ADMISSAO, '%d/%m/%Y'),
CASE
when VINCULO = 'Cargo em Comissão' then 7
when VINCULO = 'Concursado' then 2
when VINCULO = 'Efetivo' then 3
when VINCULO = 'Trabalhador Temporário' then 1
END,
'1/1',
40,
0,
1,
u.id,
now(),
now()
from adm_mdl0_servidores s
join adm_users u on u.cpf = s.CPF
where s.FUNCAO_EXERCIDA like 'Vice Diretor%'
and SITUACAO = "ATIVO"
group by s.CPF;


-- Insere vinculos Articulador a partir da SEDUC
INSERT INTO adm_vinculos(matricula, cargo_id, data_admissao, tipo_vinculo, fator_alocacao, regime_trabalho, nivel, situacao, user_id, created_at, updated_at)

select
MATRICULA_DO_SERVIDOR_SEM_O_DIGITO,
1, -- ID da função/cargo
STR_TO_DATE(DATA_DE_ADMISSAO, '%d/%m/%Y'),
CASE
when VINCULO = 'Cargo em Comissão' then 7
when VINCULO = 'Concursado' then 2
when VINCULO = 'Efetivo' then 3
when VINCULO = 'Trabalhador Temporário' then 1
END,
'1/1',
40,
0,
1,
u.id,
now(),
now()
from adm_mdl0_servidores s
join adm_users u on u.cpf = s.CPF
where s.FUNCAO_EXERCIDA like 'Articulador%'
and SITUACAO = "ATIVO"
group by s.CPF;

>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>


>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
/*
Adicionar Função de usuários e vinculos a escolas
 */
INSERT INTO adm_permissoes(role_id, user_id, escola_id, created_at, updated_at)
SELECT
3,
u.id,
e.codigo,
now(),
now()
from adm_mdl0_servidores s
join adm_users u on u.cpf = s.CPF
join adm_escolas e on e.codigo = s.CODIGO_INEP
where s.FUNCAO_EXERCIDA like '%Secretario%'
and s.SITUACAO = "ATIVO";

INSERT INTO adm_permissoes(role_id, user_id, escola_id, created_at, updated_at)
SELECT
5,
u.id,
e.codigo,
now(),
now()
from adm_mdl0_servidores s
join adm_users u on u.cpf = s.CPF
join adm_escolas e on e.codigo = s.CODIGO_INEP
where s.FUNCAO_EXERCIDA like 'COORDENADOR PEDAGOGICO%'
and s.SITUACAO = "ATIVO";

INSERT INTO adm_permissoes(role_id, user_id, escola_id, created_at, updated_at)
SELECT
4,
u.id,
e.codigo,
now(),
now()
from adm_mdl0_servidores s
join adm_users u on u.cpf = s.CPF
join adm_escolas e on e.codigo = s.CODIGO_INEP
where s.FUNCAO_EXERCIDA like 'Diretor%'
and s.SITUACAO = "ATIVO";

INSERT INTO adm_permissoes(role_id, user_id, escola_id, created_at, updated_at)
SELECT
9,
u.id,
e.codigo,
now(),
now()
from adm_mdl0_servidores s
join adm_users u on u.cpf = s.CPF
join adm_escolas e on e.codigo = s.CODIGO_INEP
where s.FUNCAO_EXERCIDA like 'Vice Diretor%'
and s.SITUACAO = "ATIVO";

INSERT INTO adm_permissoes(role_id, user_id, escola_id, created_at, updated_at)
SELECT
6,
u.id,
e.codigo,
now(),
now()
from adm_mdl0_servidores s
join adm_users u on u.cpf = s.CPF
join adm_escolas e on e.codigo = s.CODIGO_INEP
where s.FUNCAO_EXERCIDA like 'Articulador%'
and s.SITUACAO = "ATIVO";
>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>


>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
/*
Alocação de professores nas turmas
Ajustar disciplinas
*/
-- cria colunas
ADD COLUMN IF NOT EXISTS user_id INT AFTER id,
ADD COLUMN IF NOT EXISTS escola_id INT AFTER id,
ADD COLUMN IF NOT EXISTS turma_id INT AFTER id;
ADD COLUMN IF NOT EXISTS disciplina_id INT AFTER id;

-- Ajustar nome de disciplinas na tabela consolidado_notas_alunos
UPDATE `adm_mdl0_relatorio_geral_servidores` SET `DISCIPLINA` = 'Língua Portuguesa' WHERE `DISCIPLINA` = 'Ensino da Língua Portuguesa';
UPDATE `adm_mdl0_relatorio_geral_servidores` SET `DISCIPLINA` = 'Língua Inglesa' WHERE `DISCIPLINA` = 'LÍNGUA ESTRANGEIRA MODERNA INGLÊS';
UPDATE `adm_mdl0_relatorio_geral_servidores` SET `DISCIPLINA` = 'Ciências' WHERE `DISCIPLINA` = 'Ciências Naturais';

ALTER TABLE `adm_mdl0_relatorio_geral_servidores` ADD INDEX IF NOT EXISTS `DISCIPLINA_index` (`DISCIPLINA`);
ALTER TABLE `adm_mdl0_relatorio_geral_servidores` ADD INDEX IF NOT EXISTS `CPF_index` (`CPF`);
ALTER TABLE `adm_mdl0_relatorio_geral_servidores` ADD INDEX IF NOT EXISTS `CDIGO_INEP_ESCOLA_index` (`CDIGO_INEP_ESCOLA`);
ALTER TABLE `adm_mdl0_relatorio_geral_servidores` ADD INDEX IF NOT EXISTS `TURMA_index` (`TURMA`);

-- Insert professor polivalente
INSERT INTO `adm_professor_turma`(`user_id`, `turma_id`, `escola_id`, `disciplina_id`, `ativo`, `ator`, `created_at`, `updated_at`)

SELECT
u.id,
t.id,
e.id,
d.id,
1,
1,
now(),
now()
from adm_mdl0_relatorio_geral_servidores s
join adm_users u on u.cpf = s.CPF
join adm_turmas t on t.idnumber = s.TURMA
join adm_escolas e on e.codigo = s.CDIGO_INEP_ESCOLA
-- join adm_disciplinas d on d.nome = s.DISCIPLINA
where s.DISCIPLINA = 'Atividade Polivalente'


>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>



>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
Atualizar etapa
UPDATE tablename SET `etapa_id`=
CASE
when serie_id = 1 then 1
when serie_id = 2 then 1
when serie_id = 3 then 1
when serie_id = 4 then 1

when serie_id = 5 then 2
when serie_id = 6 then 2

when serie_id = 7 then 3
when serie_id = 8 then 3
when serie_id = 9 then 3
when serie_id = 10 then 3
when serie_id = 11 then 3

when serie_id = 12 then 4
when serie_id = 13 then 4
when serie_id = 14 then 4
when serie_id = 15 then 4

when serie_id = 16 then 6
when serie_id = 17 then 6
when serie_id = 18 then 6

when serie_id = 19 then 7
when serie_id = 20 then 7
END


================================================================
-- Atualiza emails dos alunos
UPDATE adm_users u , `adm_/tmp/workspace.csv` w
SET
u.email=w.Email_Address_Required
WHERE u.idnumber = Employee_ID
and u.idnumber like 'a%'
AND NOT EXISTS (
    SELECT 1
    FROM adm_users u2
    WHERE u2.email = w.Email_Address_Required
);

-- Atualiza emails dos funcionarios
UPDATE adm_users u , `adm_/tmp/workspace.csv` w
SET
u.email=w.Email_Address_Required
WHERE u.cpf = Employee_ID
and u.idnumber not like 'a%'
AND NOT EXISTS (
    SELECT 1
    FROM adm_users u2
    WHERE u2.email = w.Email_Address_Required
);
==================

-- Atualiza idnumber dos alunos
UPDATE adm_users u , adm_exportar_alunos a
SET
u.idnumber=a.aluno__id
WHERE concat(u.name, u.data_nascimento, u.nome_responsavel) = concat(`aluno__nome`,`aluno__data_nascimento`, `responsavel__nome`)

-- Atualiza idnumber da equipe escolar
UPDATE adm_users u , adm_exportar_equipe_escolar ee
SET
u.idnumber=ee.funcionario__id
WHERE u.cpf = replace(replace(ee.funcionario__cpf, ',', ''), '-', '')

-- Atualiza idnumber dos professores
UPDATE adm_users u , adm_exportar_professores p
SET
u.idnumber=p.professor__id
WHERE u.cpf = replace(replace(ee.professor__id, ',', ''), '-', '')


select * FROM `adm_turmas` AS t1, `adm_turmas` AS t2
    WHERE t1.`id`>t2.`id`
    AND t1.`calendario_id`=t2.`calendario_id`
    AND t1.`escola_id`=t2.`escola_id`
    AND t1.`descricao`=t2.`descricao`
    AND t1.`turma`=t2.`turma`
    AND t1.`turno_id`=t2.`turno_id`

/* Remove turmas MULTISSERIADAS Duplicadas */
DELETE t1 FROM `adm_turmas` AS t1, `adm_turmas` AS t2
    WHERE t1.`id`>t2.`id`
    AND t1.`calendario_id`=t2.`calendario_id`
    AND t1.`escola_id`=t2.`escola_id`
    AND t1.`descricao`=t2.`descricao`
    AND t1.`turma`=t2.`turma`
    AND t1.`turno_id`=t2.`turno_id`

/* Remove series_turmas MULTISSERIADAS Duplicadas */
DELETE t1 FROM `adm_serie_turma` AS t1, `adm_serie_turma` AS t2
    WHERE t1.`id`>t2.`id`
    AND t1.`turma_id`=t2.`turma_id`
    AND t1.`serie_id`=t2.`serie_id`


/* Atualizar CPF para 11 dígitos */
UPDATE adm_users SET cpf = LPAD(cpf, 11, 0)
WHERE length(cpf) < 11
and cpf <> ''
and cpf = cpf

/* Atualiza IDs de turmas em adm_turmas */
UPDATE `adm_turmas` t
join adm_escolas e on e.id = t.escola_id
join adm_salas s on s.id = t.sala_id
join adm_turnos turnos on turnos.id = t.turno_id
SET t.idnumber = concat(2023, '_', e.idnumber, '_', s.idnumber, '_', turnos.idnumber)
where t.idnumber is null

/* remove vinculo de series de turmas Multi da tabela serie_turma */
DELETE st FROM `adm_serie_turma` st
join adm_turmas t on t.id = st.turma_id
join adm_escolas e on e.id = t.escola_id
WHERE t.multi = 1
and calendario_id = 2
and e.idnumber = 14

-- gera planilha para workspace de proefssores para workspace
SELECT
w.First_Name_Required as 'First Name',
w.Last_Name_Required as 'Last Name',
w.Email_Address_Required as 'Email Address',
p.professor__cpf as Password,
w.Org_Unit_Path_Required as 'Org Unit Path',
p.professor__cpf as Employee_ID
FROM `0adm_professores` p
join adm_workspace w on concat(w.First_Name_Required, ' ', w.Last_Name_Required) = p.professor__nome
where w.Email_Address_Required not like '%aluno.%'
and w.Org_Unit_Path_Required <> '/SEAD';

-- Atualiza campo email 0adm_professores
UPDATE 0adm_professores p
join adm_workspace w on concat(w.First_Name_Required, ' ', w.Last_Name_Required) = p.professor__nome
SET email = w.Email_Address_Required
where w.Email_Address_Required not like '%aluno.%'
and w.Org_Unit_Path_Required <> '/SEAD';

-- Atualiza campo email adm_users
UPDATE `adm_users` u
join 0adm_professores p on p.professor__cpf = u.cpf
SET u.email = p.email
where p.email is not null
and NOT EXISTS (select email from adm_users where email = p.email);

-- ou

UPDATE `adm_users` u
join 0adm_professores p on p.professor__cpf = u.cpf
SET u.email = p.email
where p.email is not null
and p.email NOT IN (select email from (select * from adm_users) as m2);

-- Verifica usuários duplicados
SELECT nome, cpf, COUNT(*) as duplicated
FROM adm_users
where cpf <> ''
GROUP BY cpf
HAVING duplicated > 1

-- Atualiza idnumber dos professores pela table exportar_professores
UPDATE `adm_users` u
join adm_exportar_professores p on p.professor__cpf = u.cpf
SET idnumber = p.professor__id

-- lista de funcionarios com email institucional
SELECT
SUBSTRING_INDEX(TRIM(u.name), ' ', 1) AS 'First Name',
TRIM(SUBSTRING(TRIM(u.name), INSTR(TRIM(u.name), substring_index(TRIM(u.name), ' ', 1))+LENGTH(substring_index(TRIM(u.name), ' ', 1)))) AS 'Last Name',
u.email as 'Email Address',
u.cpf as Password,
'/SEDUC' as 'Org Unit Path',
u.cpf as 'Employee ID'
from adm_users u
where idnumber not like 'a%'
and email like '%juazeiro%'
and email not like 'aluno.%';

-- Atualiza Employee_ID de alunos
SELECT
`First_Name_Required` 'First Name',
`Last_Name_Required` 'Last Name',
`Email_Address_Required` 'Email Address',
'educajua2023' as 'Password',
`Org_Unit_Path_Required` 'Org Unit Path',
u.idnumber 'Employee ID'
FROM adm_workspace w
join `adm_users` u on u.email = w.Email_Address_Required;

-- atualiza created_professores
UPDATE `created_emails_professores` t1, adm_workspace t2
SET t1.`Email Address` = t2.`Email_Address_Required`
where t1.`Employee ID` = t2.`Employee_ID`

UPDATE adm_users t1, created_emails_professores t2
SET t1.email = t2.`Email Address`
where t1.cpf = t2.`Employee ID`
and NOT EXISTS (SELECT 1 from adm_users u where u.email = t2.`Email Address`)

-- atualiza emails users alunos online
CREATE INDEX IF NOT EXISTS idx_Employee ON TABLE 573(Employee ID);
CREATE INDEX IF NOT EXISTS Email ON TABLE 573(Email Address);

UPDATE adm_users t1, TABLE 573 t2
SET t1.email = t2.Email Address
where t1.idnumber = t2.Employee ID
and t2.Email Address NOT IN (select email from (select * from adm_users) as m2);

-- Check if is a Number
cast('John123456' AS UNSIGNED)

-- Verifica escolas sem horário cadastrados
SELECT
e.id,
e.`nome`,
(SELECT count(DISTINCT t.id)*20 from adm_turmas t where t.escola_id = e.id
and calendario_id = 2
group by t.escola_id
) qtd_total,
(
    SELECT count(DISTINCT ha.id)
    from adm_horariodeaulas ha
    join adm_turmas t on t.id = ha.turma_id
    where t.escola_id = e.id
    and ha.disciplina_id is not null
    and calendario_id = 2
    group by t.escola_id
) qtd_cadastrados
FROM `adm_escolas` e
where e.nome not like '%EMEI%'
group by e.id
order by qtd_cadastrados, qtd_total desc;

-- Marcar alunos ausentes no csv como transferidos na matricula
UPDATE `adm_matriculas` m
SET `statusmatricula_id` = 2
WHERE `calendario_id` = 2
and NOT EXISTS (SELECT 1 FROM `0adm_exportar_alunos` exp where exp.adm_user_id = m.user_id);


# remove turmas inexistentes
SET @variavel_ano_letivo := (SELECT ano_letivo FROM 0adm_exportar_alunos LIMIT 1);
SET @variavel_calendario_id := (SELECT id FROM adm_calendarios c where c.ano = @variavel_ano_letivo LIMIT 1);
DELETE t FROM adm_turmas t
where NOT EXISTS (SELECT 1 from 0adm_exportar_turmas exp where t.id = exp.adm_turma_id)
and t.calendario_id = @variavel_calendario_id;

# Atualiza turmas existentes
SET @variavel_ano_letivo := (SELECT ano_letivo FROM 0adm_exportar_alunos LIMIT 1);
SET @variavel_calendario_id := (SELECT id FROM adm_calendarios c where c.ano = @variavel_ano_letivo LIMIT 1);
UPDATE adm_turmas t
join 0adm_exportar_turmas expt on expt.adm_turma_id = t.id
SET descricao = expt.serie__nome,
t.turma  = substr(expt.turma__nome, -1),
t.escola_id = expt.adm_escola_id,
t.multi = case when expt.multi = 'True' then 1 else 0 end,
t.sala_id = expt.adm_sala_id,
t.turno_id = expt.adm_turno_id,
t.programa_id = null,
t.ator = 1,
t.ativo = 1
WHERE calendario_id = @variavel_calendario_id


insert into adm_conteudosministrados (
            id,
            unidade_id,
            data,
            conteudo,
            user_id,
            calendario_id,
            escola_id,
            etapa_id,
            serie_id,
            turno_id,
            turma_id,
            disciplina_id,
            qtd_aulas,
            ator,
            created_at,
            updated_at
        )
        select
            id,
            unidade_id,
            data,
            conteudo,
            user_id,
            calendario_id,
            escola_id,
            etapa_id,
            serie_id,
            turno_id,
            turma_id,
            disciplina_id,
            qtd_aulas,
            ator,
            created_at,
            updated_at
from 0backup_adm_conteudosministrados bkp
where not EXISTS (SELECT 1 from adm_conteudosministrados c where c.id = bkp.id)
and EXISTS (SELECT 1 from adm_turmas t where t.id = bkp.turma_id)
group by id;


UPDATE `adm_conteudosministrados` SET `deleted` = NULL WHERE `adm_conteudosministrados`.`user_id` = 967;

SELECT u.name, email FROM `adm_matriculas` m
join adm_users u on u.id = user_id
where `calendario_id` = 2
and u.name not like '%TESTE%'
and m.statusmatricula_id = 1


SELECT u.name, u.email FROM `adm_professor_turma` p
join adm_users u on u.id = p.user_id
where email not like '%professor%'
group by `user_id`;

-- Adicioanr ID da Matriz as tabela serie_turma
UPDATE adm_serie_turma st
join adm_matrizes m on m.serie_id = st.serie_id
join adm_turmas t on t.id = st.turma_id
SET
st.matriz_id = m.id
where m.`escola_id` = 0
and m.codigo = 'Padrão'
and m.calendario_id = 2
and t.calendario_id = 2
and st.matriz_id is null;

/* altera o tipo de todas as colunas  */
select distinct concat('alter table ',
                       table_name,
                       ' modify ',
                       column_name,
                       ' VARCHAR(255) ',
                       if(is_nullable = 'NO', ' NOT ', ''),
                       ' NULL;')
  from information_schema.columns
  where table_schema = 'backup'
  and table_name = 'adm_atividadescomplementares'


--   Gerar planilha Professor Editora Moderna
SELECT distinct
e.codigo 'INEP DA ESCOLA',
e.nome 'NOME DA ESCOLA',
s.nome 'SERIE',
concat(t.descricao, ' ', t.turma) 'TURMA',
case
when turno_id = 1 then 'M'
when turno_id = 2 then 'V'
when turno_id = 3 then 'N'
when turno_id = 4 then 'I'
end 'TURNO',
u.name 'NOME DO EDUCADOR',
u.email 'MAIL',
'Professor' as FUNCAO
FROM adm_professor_turma pt
join adm_users u on u.id = pt.user_id
join adm_turmas t on t.id = pt.turma_id
join adm_serie_turma st on st.turma_id = t.id
join adm_series s on s.id = st.serie_id
join adm_escolas e on e.id = t.escola_id
where calendario_id = 2
AND s.id BETWEEN 8 AND 15

-- gera planilha professores Aprender 1º ano
SELECT
u.name 'NOME',
u.email 'E-mail',
u.cpf 'CPF',
GROUP_CONCAT(e.nome) 'ESCOLAS',
GROUP_CONCAT(concat(
    s.nome, ' - ',
    case
    when turno_id = 1 then 'Manhã'
    when turno_id = 2 then 'Tarde'
    when turno_id = 3 then 'Noite'
    when turno_id = 4 then 'Integral'
    end,
    ' - ',
    t.descricao,
    ' ',
    t.turma
)) 'Turmas (série/turno/turma)'
FROM adm_professor_turma pt
join adm_users u on u.id = pt.user_id
join adm_turmas t on t.id = pt.turma_id
join adm_serie_turma st on st.turma_id = t.id
join adm_series s on s.id = st.serie_id
join adm_escolas e on e.id = t.escola_id
where calendario_id = 2
AND s.id = 7
group by u.id;

-------------------------------------------------------------------
# atualiza aulas na tabela frequencia
UPDATE adm_frequencias
SET aulas = (
    SELECT COUNT(*)
    FROM adm_horariodeaulas
    INNER JOIN adm_horarios ON adm_horariodeaulas.horario_id = adm_horarios.id
    WHERE
        adm_horariodeaulas.turma_id = adm_frequencias.turma_id AND
        adm_horariodeaulas.serie_id = adm_frequencias.serie_id AND
        adm_horariodeaulas.disciplina_id = adm_frequencias.disciplina_id AND
        CASE DAYOFWEEK(adm_frequencias.data)
            WHEN 2 THEN 1  -- Segunda-feira
            WHEN 3 THEN 2  -- Terça-feira
            WHEN 4 THEN 3  -- Quarta-feira
            WHEN 5 THEN 4  -- Quinta-feira
            WHEN 6 THEN 5  -- Sexta-feira
            ELSE NULL
        END = adm_frequencias.periodo AND
        CASE WHEN TIME(adm_horarios.horai) <= '12:00:00' THEN 1 WHEN TIME(horai) <= '18:00:00' THEN 2 ELSE 3 END = adm_frequencias.periodo
)
WHERE EXISTS (
    SELECT 1
    FROM adm_horariodeaulas
    INNER JOIN adm_horarios ON adm_horariodeaulas.horario_id = adm_horarios.id
    WHERE
        adm_horariodeaulas.turma_id = adm_frequencias.turma_id AND
        adm_horariodeaulas.serie_id = adm_frequencias.serie_id AND
        adm_horariodeaulas.disciplina_id = adm_frequencias.disciplina_id AND
        CASE DAYOFWEEK(adm_frequencias.data)
            WHEN 2 THEN 1  -- Segunda-feira
            WHEN 3 THEN 2  -- Terça-feira
            WHEN 4 THEN 3  -- Quarta-feira
            WHEN 5 THEN 4  -- Quinta-feira
            WHEN 6 THEN 5  -- Sexta-feira
            ELSE NULL
        END = adm_frequencias.periodo AND
        CASE WHEN TIME(adm_horarios.horai) <= '12:00:00' THEN 1 WHEN TIME(horai) <= '18:00:00' THEN 2 ELSE 3 END = adm_frequencias.periodo);

---atualiza como resposta para "NULA" para questões nulas
UPDATE adm_sim_respostas AS a
JOIN (
  SELECT aluno_id
  FROM adm_sim_respostas
    JOIN adm_sim_item_serie ise on ise.id = item_id
    JOIN adm_sim_habilidades hab on hab.id = ise.habilidade_id
  WHERE resposta is not null
  and disciplina_id = 1
  GROUP BY aluno_id
) AS diagnosticados ON a.aluno_id = diagnosticados.aluno_id
JOIN adm_sim_item_serie ise on ise.id = a.item_id
JOIN adm_sim_habilidades hab on hab.id = ise.habilidade_id
SET a.resposta = 'NULA'
WHERE a.resposta is null
and disciplina_id = 1;

UPDATE adm_sim_respostas AS a
JOIN (
  SELECT aluno_id
  FROM adm_sim_respostas
    JOIN adm_sim_item_serie ise on ise.id = item_id
    JOIN adm_sim_habilidades hab on hab.id = ise.habilidade_id
  WHERE resposta is not null
  and disciplina_id = 2
  GROUP BY aluno_id
) AS diagnosticados ON a.aluno_id = diagnosticados.aluno_id
JOIN adm_sim_item_serie ise on ise.id = a.item_id
JOIN adm_sim_habilidades hab on hab.id = ise.habilidade_id
SET a.resposta = 'NULA'
WHERE a.resposta is null
and disciplina_id = 2;


-- Buscar alunos para sistema presença
SELECT 
	DISTINCT
    anloc.`UF`, 
    anloc.`IBGE`, 
    anloc.`MUNICIPIO`, 
    anloc.`NIS`, 
    anloc.`NOME_ALUNO`, 
    anloc.`RESPONSAVEL`, 
    e.nome AS ESCOLA, 
    CONCAT(t.descricao, ' ', t.turma) AS TURMA,
    CASE 
        WHEN t.turno_id = 1 THEN 'Manhã'
        WHEN t.turno_id = 2 THEN 'Tarde'
        WHEN t.turno_id = 3 THEN 'Noite'
        WHEN t.turno_id = 4 THEN 'Integral'
    END AS TURNO
FROM `aluno_situacao_nloc_export_1692883867577` anloc
LEFT JOIN adm_users u ON u.name = anloc.NOME_ALUNO AND u.data_nascimento = anloc.`NASCIMENTO` AND u.nome_responsavel = anloc.`RESPONSAVEL`
LEFT JOIN adm_aluno_turma altu ON altu.user_id = u.id
LEFT JOIN adm_turmas t ON t.id = altu.turma_id AND t.calendario_id = 2
LEFT JOIN adm_escolas e ON e.id = t.escola_id
group by NOME_ALUNO;


-- relatóri de horario de aulas por escolas/turmas
SELECT e.nome, e.id, t.descricao, t.turma, tu.nome, ha.turma_id,
(SELECT count(*) 
 FROM adm_horariodeaulas sub_ha 
 where sub_ha.turma_id = ha.turma_id
 and sub_ha.ator is not null
) as ator_is_not_null,
(SELECT count(*) 
 FROM adm_horariodeaulas sub_ha 
 where sub_ha.turma_id = ha.turma_id
 and sub_ha.ator is null
) as ator_is_null
FROM `adm_horariodeaulas` ha
join adm_turmas as t on t.id = ha.turma_id
join adm_escolas e on e.id = t.escola_id
join adm_turnos tu on tu.id = t.turno_id
where t.calendario_id = 2
group by `turma_id`
order by e.nome;

-- Delete horarios sobressalentes (sem ator)
DELETE ha FROM `adm_horariodeaulas` ha
where ha.turma_id IN (
2907,
2908,
2229,
4869,
4860,
2717,
3063,
3062,
2356,
2355,
2837,
2838,
3130,
2389,
4391,
4392,
4406,
2798,
5130,
3247,
2716,
2904,
2901,
2903,
2900,
2902
) and ha.ator is null;


DELETE FROM `adm_horariodeaulas`
where `turma_id` = 2702
and MONTH(`updated_at`) = 3; 

DELETE FROM `adm_horariodeaulas`
where `turma_id` = 2757
and MONTH(`updated_at`) = 3; 

DELETE FROM `adm_horariodeaulas`
where `turma_id` = 3381
and MONTH(`updated_at`) = 3; 

DELETE FROM `adm_horariodeaulas`
where `turma_id` = 2662
and MONTH(`updated_at`) = 3; 

DELETE FROM `adm_horariodeaulas`
where `turma_id` = 2955
and MONTH(`updated_at`) = 3; 

DELETE FROM `adm_horariodeaulas`
where `turma_id` = 2960
and MONTH(`updated_at`) = 3; 

DELETE FROM `adm_horariodeaulas`
where `turma_id` = 2445
and MONTH(`updated_at`) = 2; 

DELETE FROM `adm_horariodeaulas`
where `turma_id` = 4405
and MONTH(`updated_at`) = 3; 

DELETE FROM `adm_horariodeaulas`
where `turma_id` = 2400
and MONTH(`updated_at`) = 2; 

DELETE FROM `adm_horariodeaulas`
where `turma_id` = 5218
and MONTH(`updated_at`) = 3; 

DELETE FROM `adm_horariodeaulas`
where `turma_id` = 2411
and MONTH(`updated_at`) = 3; 

DELETE FROM `adm_horariodeaulas`
where `turma_id` = 2421
and MONTH(`updated_at`) = 3; 


-- obter histórico de alunos, turmas
INSERT INTO aluno_turma (
user_id, turma_id, updated_at, ativo
)
SELECT adm_frequencias.aluno_id, adm_frequencias.turma_id, adm_frequencias.updated_at, 0 FROM `adm_frequencias`
left join adm_aluno_turma altu on altu.user_id =  adm_frequencias.aluno_id AND altu.turma_id = adm_frequencias.turma_id
where altu.user_id is null
group by adm_frequencias.aluno_id, adm_frequencias.turma_id
order by adm_frequencias.updated_at desc;


-- formato de planilha para upload workspace
SELECT
SUBSTRING_INDEX(TRIM(name), ' ', 1) AS 'First Name',
TRIM(SUBSTRING(TRIM(name), INSTR(TRIM(name), substring_index(TRIM(name), ' ', 1))+LENGTH(substring_index(TRIM(name), ' ', 1)))) AS 'Last Name',
email AS 'Email Address',
'educajua' as 'Password',
'/SEDUC/Alunos' as 'Org Unit Path',
CONCAT(idnumber)  as 'Employee ID'

FROM adm_users u
where u.idnumber = 'a31449';

-- Ajuste em caso de inserção indevida de Nota de recuperacao_final
UPDATE `adm_avaliacoes_resultados_finais` 
SET 
`media_final` = media_anual,
recuperacao_final = null
where `recuperacao_final` is not null
and media_anual >= 6.0


-- arredondamento de notas medias:
UPDATE `adm_avaliacoes_resultados_finais` SET
`media_anual` = 
    CASE 
        WHEN ROUND(media_anual, 2) - FLOOR(ROUND(media_anual, 2)) <= 0.24 THEN FLOOR(ROUND(media_anual, 2))
        WHEN ROUND(media_anual, 2) - FLOOR(ROUND(media_anual, 2)) <= 0.74 THEN FLOOR(ROUND(media_anual, 2)) + 0.5
        ELSE CEIL(ROUND(media_anual, 2))
    END
WHERE `media_anual` is not null
and `media_anual` > 0.00  

UPDATE adm_avaliacoes_resultados_finais
SET media_final = CASE
    WHEN recuperacao_final >= 5.0 AND recuperacao_final <= 6.0 THEN 6.0
    WHEN recuperacao_final > 6.0 THEN recuperacao_final
    WHEN recuperacao_final <= 5.0 AND recuperacao_final > media_anual THEN recuperacao_final
    ELSE media_anual
END
where media_anual is not null

UPDATE `adm_disciplina_matriz` SET
conserva = 0
where disciplina_id = 10

#######################################################################################
criar procedimento de arredondamento de notas
DELIMITER ;;
CREATE DEFINER=`root`@`%` FUNCTION `arredondamento`(numero DOUBLE) RETURNS double
    DETERMINISTIC
BEGIN
    RETURN ROUND(numero * 2) / 2;
END ;;
DELIMITER ;


###################################################################
Criar procedimento
DELIMITER ;;
CREATE DEFINER=`mariadb`@`%` PROCEDURE `UpdateCalendarioIdInBatches`()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE start_id BIGINT DEFAULT 1;
    DECLARE batch_size INT DEFAULT 100000;
    DECLARE max_id BIGINT;

    
    SELECT MAX(id) INTO max_id FROM adm_frequencias;

    WHILE done = 0 DO
        
        UPDATE adm_frequencias f
        JOIN adm_aulas a ON f.aula_id = a.id
        SET f.calendario_id = a.calendario_id
        WHERE f.id BETWEEN start_id AND (start_id + batch_size - 1);

        
        SET start_id = start_id + batch_size;

        
        IF start_id > max_id THEN
            SET done = 1;
        END IF;
    END WHILE;
END ;;
DELIMITER ;



-- Ajustar notas de alunos que mudam de série no mesmo ano, por exemplo saem de um 9º para o Eja Etapa V
UPDATE adm_avaliacoes a
JOIN (
    SELECT user_id, unidade_id, disciplina_id,
           MAX(atv1) as atv1,
           MAX(atv2) as atv2,
           MAX(atv3) as atv3,
           COALESCE(MAX(atv1), 0) + COALESCE(MAX(atv2), 0) + COALESCE(MAX(atv3), 0) as media_atividades,
           CASE WHEN (COALESCE(MAX(atv1), 0) + COALESCE(MAX(atv2), 0) + COALESCE(MAX(atv3), 0) < 6) THEN MAX(recuperacao) END as recuperacao,
           MAX(media_unidade) as media_unidade
    FROM adm_avaliacoes
    WHERE calendario_id = 2
    GROUP BY user_id, unidade_id, disciplina_id
    HAVING COUNT(serie_id) >= 2
) b ON a.user_id = b.user_id
    AND a.unidade_id = b.unidade_id
    AND a.disciplina_id = b.disciplina_id
SET a.atv1 = b.atv1,
    a.atv2 = b.atv2,
    a.atv3 = b.atv3,
    a.media = b.media_atividades,
    a.recuperacao = b.recuperacao,
    a.media_unidade = b.media_unidade
WHERE a.calendario_id = 2;

##############################################################################
UPDATE adm_avaliacoes_resultados_finais a
JOIN (
    SELECT user_id, disciplina_id,
           MAX(media_anual) as media_anual,
           CASE WHEN MAX(media_anual) < 6 THEN MAX(recuperacao_final) END as recuperacao_final,
           MAX(media_final) as media_final
    FROM adm_avaliacoes_resultados_finais
    WHERE calendario_id = 2
    GROUP BY user_id, disciplina_id
    HAVING COUNT(serie_id) >= 2
) b ON a.user_id = b.user_id
    AND a.disciplina_id = b.disciplina_id
SET a.media_anual = b.media_anual,
    a.recuperacao_final = b.recuperacao_final,
    a.media_final = b.media_final
WHERE a.calendario_id = 2;

-- Drop foreing Key
ALTER TABLE tabela DROP FOREIGN KEY `chave_estrangeira`;

-- no model
    protected $connection = 'pgsql';
-- Migration
    php artisan migrate --database=mysql

# ####################################################
Insert INTO 0adm_turmas_portal_id_turma_id
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


-- Passo para ajustes serie_id de alunos no resultado final para turmas multi
UPDATE adm_alunosresultadosfinais tdest
JOIN adm_matriculas torig ON tdest.aluno_id = torig.user_id
SET tdest.serie_id = torig.serie_id
where torig.calendario_id = tdest.calendario_id;

-- Atualizar 
SET SQL_BIG_SELECTS=1;
UPDATE
    adm_lb_diagnosticos AS d
JOIN
    (
        -- Subconsulta para obter os dados
        SELECT
            aluno_id,
            item_id,
            adm_lb_hs.unidade_id,
            CONCAT('{', GROUP_CONCAT(DISTINCT CONCAT('\"', item_id, '\": \"', conceito, '\"') SEPARATOR ','), '}') as json_item_id_conceito,
            CONCAT('{', GROUP_CONCAT(DISTINCT CONCAT('\"', item_id, '\": \"', adm_lb_diagnosticos.ator, '\"') SEPARATOR ','), '}') as json_item_id_ator
        FROM
            adm_lb_diagnosticos
        JOIN
            adm_lb_habilidade_serie AS adm_lb_hs ON adm_lb_hs.id = adm_lb_diagnosticos.item_id
        GROUP BY
            aluno_id, adm_lb_hs.unidade_id
    ) AS d_new ON d_new.aluno_id = d.aluno_id AND d_new.item_id = d.item_id
SET
    d.unidade_id = d_new.unidade_id,
    d.json_item_id_conceito = d_new.json_item_id_conceito,
    d.json_item_id_ator = d_new.json_item_id_ator;

-- Alunos que não fazem mais parte da rede
SELECT
    `First_Name_Required` AS 'First Name',
    `Last_Name_Required` AS 'Last Name',
    `Email_Address_Required` AS 'Email Address',
    `Password_Required` AS 'Password',
    'Excluir' AS 'Employee Title'
FROM
    `0adm_workspace` w
JOIN adm_users u ON
    u.idnumber = w.`Employee_ID`
JOIN adm_matriculas m ON
    m.user_id = u.id
WHERE
    u.idnumber IS NOT NULL AND m.statusmatricula_id <> 1 AND Email_Address_Required LIKE 'aluno.%'
GROUP BY
    u.id


-- Atualiza papel_id dos registros diarios de AEE
UPDATE `adm_aee_registros_diarios` rd
join adm_permissoes p on p.user_id = rd.professor_id
SET `papel_id` = p.role_id;


-- Reprovados por frequencia insuficiente
SELECT 
    aluno_id,
    `cha`,
    aulas_registradas,
        `total_faltas`,
        (`cha` * 0.25) AS max_faltas_permitidas,
        (`cha` - aulas_registradas) AS aulas_restantes,
        ((aulas_registradas - total_faltas) / aulas_registradas) * 100 AS percentual_frequencia_atual,
        ((aulas_registradas - total_faltas + (`cha` - aulas_registradas)) / `cha`) * 100 AS percentual_frequencia_final_possivel,
    CASE 
        WHEN `total_faltas` >= (`cha` * 0.25) THEN 'Reprovado'
        -- WHEN (`cha` * 0.25) - faltas < (`cha` - aulas_registradas) THEN 'Aprovado se frequentar todas as aulas restantes'
    END AS status_frequencia
FROM 
    adm_frequencia_consolidado
HAVING status_frequencia = 'Reprovado';

-- Listar último id de aulas para particionamento
SELECT YEAR(data) AS ano, MAX(id)+1 AS ultimo_aula_id
FROM adm_aulas
GROUP BY ano
ORDER BY ano;

-- Conculta com subjoins
SELECT 
    subSelect.aluno_id,
    subSelect.aluno_nome,
    atu.turma_id,
    subSelect.serie_id,
    aulas.disciplina_id,
    subSelect.escola_id,
    -- aulas_sub.cha,
    -- aulas_sub.turma_id,
    SUM(CASE 
        WHEN (f.deleted = 0 AND justificativa_id IS NULL) 
        THEN f.faltas 
        ELSE 0 
    END) AS total_faltas
FROM (
    SELECT 
        u.id AS aluno_id,
        u.name AS aluno_nome,
        m.serie_id,
        m.escola_id
    FROM adm_users u
    JOIN adm_matriculas m ON m.user_id = u.id
    WHERE m.calendario_id = 3
    AND m.serie_id IN (13,14,15,16)
    AND m.statusmatricula_id = 1
) AS subSelect
JOIN adm_frequencias f ON f.aluno_id = subSelect.aluno_id
JOIN adm_aulas aulas ON aulas.id = f.aula_id
JOIN adm_aluno_turma atu ON atu.user_id = subSelect.aluno_id
    AND atu.turma_id = aulas.turma_id
    AND atu.ativo = 1
JOIN (
    
-- calular aulas registradas
    SELECT 
    subSelect.turma_id,
    subSelect.serie_id,
    subSelect.disciplina_id,
    SUM(subSelect.aulas) AS aulas_registradas
    FROM (
        SELECT 
            a.turma_id, 
            a.serie_id, 
            a.disciplina_id, 
            a.aulas
        FROM adm_aulas AS a
        WHERE a.calendario_id = 3
        AND a.serie_id IN (13,14,15,16)
        AND a.deleted = 0
    ) AS subSelect
    GROUP BY 
        subSelect.turma_id, 
        subSelect.serie_id, 
        subSelect.disciplina_id
-- calular aulas registradas

) AS aulas_sub ON aulas_sub.turma_id = atu.turma_id
    AND aulas_sub.serie_id = subSelect.serie_id
    AND aulas_sub.disciplina_id = aulas.disciplina_id
GROUP BY f.aluno_id, serie_id, disciplina_id


-- ADD CONSTRAINT
ALTER TABLE aulas_nova 
    ADD CONSTRAINT fk_professor_id 
    FOREIGN KEY (professor_id) REFERENCES adm_users(id);

ALTER TABLE aulas_nova 
    ADD CONSTRAINT fk_turma_id 
    FOREIGN KEY (turma_id) REFERENCES adm_turmas(id);

ALTER TABLE aulas_nova 
    ADD CONSTRAINT fk_serie_id 
    FOREIGN KEY (serie_id) REFERENCES adm_series(id);

ALTER TABLE aulas_nova 
    ADD CONSTRAINT fk_calendario_id 
    FOREIGN KEY (calendario_id) REFERENCES adm_calendarios(id);

-- Ajuste de frequencia de aluna especifica
UPDATE adm_frequencias f
JOIN adm_aulas a ON a.id = f.aula_id
JOIn adm_turmas t ON t.id = a.turma_id
SET f.deleted = 1
WHERE f.aluno_id = 12851
and a.calendario_id = 3
and t.calendario_id = 3
and escola_id = 79
and a.data >= '2024-05-15'

-- cria função de arredondamento:
DELIMITER $$
CREATE FUNCTION arredondamento(numero DOUBLE)
RETURNS DOUBLE
DETERMINISTIC
BEGIN
    RETURN ROUND(numero * 2) / 2;
END$$
DELIMITER ;

-- ajuste da tabela de conteudos ministrados
ALTER TABLE `adm_conteudosministrados`
UPDATE `adm_conteudosministrados` SET `qtd_aulas` = 0 WHERE `qtd_aulas` IS NULL;
ALTER TABLE `adm_conteudosministrados` CHANGE `qtd_aulas` `qtd_aulas` INT(11) NOT NULL DEFAULT '0';
ADD INDEX `idx_escola_etapa_serie` (`escola_id`, `turma_id`, `serie_id`);


-- exemplo de adicionar partições:
ALTER TABLE sua_tabela ADD PARTITION p11 VALUES IN (11);
ALTER TABLE sua_tabela ADD PARTITION default VALUES (DEFAULT);

-- Verifcar professores de determinada disciplina e série:
SELECT DISTINCT u.id, p.`Matrícula`, name, p.cpf, p.Regime, d.nome FROM `professores_temporarios_9___ano` p
join adm_users u on u.cpf=p.Cpf
join adm_professor_turma pt on pt.user_id = u.id
join adm_disciplinas d on d.id = pt.disciplina_id
join adm_turmas t on t.id=pt.turma_id
join adm_serie_turma st on st.turma_id = t.id
where pt.ativo = 1
and st.serie_id = 15 -- 9º ano
and t.ativo = 1
and t.calendario_id = 4 -- 2025
and (disciplina_id = 1 -- Português
or disciplina_id = 2) -- Matemática
ORDER by d.nome, u.name;


###################################################################################################################
-- Verifica faltas de alunos em adm_frequencias
SELECT * from `adm_frequencias` f
where `aluno_id` = aluno_id
and f.calendario_id = calendario_id
and `aula_id` IN (SELECT id from adm_aulas a where a.id=f.aula_id and a.turma_id = turma_id);

-- linha do tempo de turmas do aluno, (Histórico)
SELECT u.name, t.descricao, `user_id`, `turma_id`, `at_serie_id`, t.`created_at`, t.`updated_at`, atu.`ativo` FROM `adm_aluno_turma` atu
join adm_turmas t on t.id = atu.turma_id
join adm_users u on u.id = atu.user_id
where `user_id` = 76418
order by t.`created_at`;

###################################################################################################################
############## Excluir frequencia de alunos de matriculas duplicadas, excluir apenas para escolas a qual o aluno não pertenceu

UPDATE `adm_frequencias` f
INNER JOIN adm_aulas a ON a.id = f.aula_id
INNER JOIN adm_turmas t ON t.id = a.turma_id
SET f.deleted = 1
WHERE f.aluno_id = aluno_id
AND f.calendario_id = calendario_id
AND f.deleted = 0
AND t.escola_id != escola_id;


################################################################################
######## Ajustar rotas repetidas de alunos

UPDATE `adm_rota_aluno` ra
SET status = 0,
modificado_em = now()
where ra.status = 1
and NOT EXISTS
(
    SELECT 1 from adm_0adm_exportar_rota_aluno 
    where adm_rota_id = ra.rota_id
    and adm_aluno_id = ra.aluno_id
    and adm_escola_id = ra.escola_id
    and adm_turma_id = ra.turma_id
    and status = ra.status
    and criado_em = ra.criado_em
);

################################################################################
-- Microdados - Consulta de escolas sem adequação no fornecimento de aguá
SELECT 
    `CO_ENTIDADE` 'Inep', 
    `NO_ENTIDADE` 'Escola',
    Case when `IN_AGUA_POTAVEL` = 1 then 'SIM' ELSE 'NÃO' end 'Água Potável',
    Case when `IN_AGUA_REDE_PUBLICA` = 1 then 'SIM' ELSE 'NÃO' end 'Água Rede Pública',
    Case when `IN_AGUA_POCO_ARTESIANO` = 1 then 'SIM' ELSE 'NÃO' end 'Água Poço Artesiano',
    Case when `IN_AGUA_CACIMBA` = 1 then 'SIM' ELSE 'NÃO' end 'Água Cacimba',
    Case when `IN_AGUA_FONTE_RIO` = 1 then 'SIM' ELSE 'NÃO' end 'Água Fonte Rio', 
    Case when `IN_AGUA_CARRO_PIPA` = 1 then 'SIM' ELSE 'NÃO' end 'Água Carro Pipa',
    Case when `IN_AGUA_INEXISTENTE` = 1 then 'SIM' ELSE 'NÃO' end 'Água Inexistente'
FROM `adm_microdados_ed_basica_2024`
JOIN `adm_escolas` e ON e.codigo = `CO_ENTIDADE`
WHERE `NO_MUNICIPIO` = 'Juazeiro'
AND (
    `IN_AGUA_POTAVEL` = 0 
    OR (
        `IN_AGUA_POTAVEL` = 1 
        AND `IN_AGUA_REDE_PUBLICA` = 0 
        AND `IN_AGUA_POCO_ARTESIANO` = 0 
        AND `IN_AGUA_CACIMBA` = 0
    )
);
####################################################################
Ajuste de registros de aulas
update `adm_aulas` SET deleted = 1 
where `turma_id` = 10251 
and (`ator` = 1611
OR id_clonado is not null)


-- usuários inativos que conseguiram fazer inscrição em eventos
SELECT u.name  , e.nome
FROM `adm_evento_inscricao` ei 
join adm_users u on u.id = ei.user_id 
left join adm_professor_turma pt on pt.user_id = ei.user_id 
left join adm_turmas t on t.id = pt.turma_id
left join adm_escolas e on e.id = t.escola_id
where ei.evento_id=90
and u.ativo=0
group by u.id  
 order by 
 e.nome, 
 pt.ativo;