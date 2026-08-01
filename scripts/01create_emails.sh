#!/bin/bash

. 2024-adm_populate.sh local yes


# Início de script de criação de emails
$mysql_path $database -u $user -p$pass -e "
SELECT '' as 'Início do Script de Criação de Emails';
# cria tabela de novos emails
Drop TABLE IF EXISTS adm_mdl0_new_emails;
SELECT '' as 'Criando tabela adm_mdl0_new_emails';
CREATE TABLE adm_mdl0_new_emails (id char(20), email char(255), \`exists\` int(1), duplicated char(5));
alter table adm_mdl0_new_emails convert to character set utf8mb4 collate utf8mb4_unicode_ci;
INSERT INTO adm_mdl0_new_emails (id, email, \`exists\`) SELECT '' as id, \`Email_Address_Required\`, 1 as 'exists'
from 0adm_workspace;

"

$mysql_path $database -u $user -p$pass -e "

Drop View IF EXISTS cs_emails_suggestions;

SELECT '' as 'Criando VIEW cs_emails_suggestions';
Create View cs_emails_suggestions as
##### ALUNOS #############################################################################################
SELECT aluno__id as id, concat('aluno.',lower(SUBSTRING_INDEX(TRIM(aluno__nome), ' ', 1)),'.',lower(SUBSTRING_INDEX(TRIM(aluno__nome), ' ', -1)),'@juazeiro.ba.gov.br') email
FROM 0adm_exportar_alunos ex
where NOT EXISTS (
select 1 from 0adm_workspace w where w.Employee_ID = concat('a', ex.aluno__id))
group by aluno__id

UNION

##### Professor #############################################################################################
    SELECT
    professor__cpf as id, concat(lower(SUBSTRING_INDEX(TRIM(professor__nome), ' ', 1)),'.',lower(SUBSTRING_INDEX(TRIM(professor__nome), ' ', -1)),'@juazeiro.ba.gov.br') email
    FROM 0adm_exportar_professores s
    where NOT EXISTS (select 1 from 0adm_workspace w where w.Employee_ID = professor__cpf)
    group by professor__cpf;

SELECT '' as 'Inserindo novos emails em adm_mdl0_new_emails';
"

#Exportar CSV de Sugestões de Emails
$mysql_path $database -u $user -p$pass -e "
SELECT id, email from cs_emails_suggestions
order by email
;" | tr '\t' ',' > cs_emails_suggestions.csv

#verificar e ajustar emails duplicados
fname="cs_emails_suggestions.csv"
table="adm_mdl0_new_emails"
opfile="adm_mdl0_new_emails.sql"
linePart1="Set @email = replace('"
linePart2="','\t','') COLLATE utf8mb4_unicode_ci;

INSERT INTO adm_mdl0_new_emails (id, email, \`exists\`)
SELECT
replace(substring_index("@email", ',', 1),'\t','') as id,
if(count(email)=0,replace(substring_index("@email", ',', -1),'\t',''),concat(substring_index(replace(substring_index("@email", ',', -1),'\t',''),'@',1),count(email), '@juazeiro.ba.gov.br')) as email,
0 as 'exists' from adm_mdl0_new_emails
where email like concat(substring_index(replace(substring_index("@email", ',', -1),'\t',''),'@',1),'%');

"
tail --lines=+2 "$fname" | while read l ;
do
values=$(echo "$l" | sed "s/'//g")
echo "$linePart1$values$linePart2"
done > "$opfile"

$mysql_path -u $user -p$pass -D $database < "$opfile"

$mysql_path $database -u $user -p$pass -e "
SELECT '' as 'Criando tabela temporária duplicated_emails';
CREATE TEMPORARY TABLE duplicated_emails
SELECT id, COUNT(*) as duplicated
FROM adm_mdl0_new_emails
where id <> ''
GROUP BY email
HAVING duplicated > 1;

SELECT '' as 'Atualizando tabela adm_mdl0_new_emails';

UPDATE
adm_mdl0_new_emails t1,
duplicated_emails t2
SET t1.duplicated = t2.duplicated
WHERE t1.id = t2.id;

"

# limpa emails com acentos
$mysql_path $database -u $user -p$pass -e "
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Š','S');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'š','s');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ð','Dj');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ž','Z');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ž','z');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'À','A');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Á','A');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Â','A');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ã','A');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ä','A');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Å','A');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Æ','A');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ç','C');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'È','E');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'É','E');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ê','E');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ë','E');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ì','I');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Í','I');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Î','I');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ï','I');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ñ','N');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ò','O');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ó','O');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ô','O');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Õ','O');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ö','O');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ø','O');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ù','U');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ú','U');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Û','U');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ü','U');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Ý','Y');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'Þ','B');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ß','Ss');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'à','a');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'á','a');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'â','a');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ã','a');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ä','a');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'å','a');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'æ','a');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ç','c');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'è','e');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'é','e');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ê','e');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ë','e');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ì','i');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'í','i');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'î','i');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ï','i');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ð','o');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ñ','n');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ò','o');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ó','o');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ô','o');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'õ','o');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ö','o');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ø','o');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ù','u');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ú','u');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'û','u');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ý','y');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ý','y');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'þ','b');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ÿ','y');
UPDATE adm_mdl0_new_emails SET email = REPLACE(email,'ƒ','f');

"

#  gerar csv para upload workspace (Painel de administração do Google Workspace)
####### Alunos
$mysql_path $database -u $user -p$pass -e "
SELECT
SUBSTRING_INDEX(TRIM(aluno__nome), ' ', 1) AS 'First Name',
TRIM(SUBSTRING(TRIM(aluno__nome), INSTR(TRIM(aluno__nome), substring_index(TRIM(aluno__nome), ' ', 1))+LENGTH(substring_index(TRIM(aluno__nome), ' ', 1)))) AS 'Last Name',
ne.email AS 'Email Address',
'educajua' as 'Password',
'/SEDUC/Alunos' as 'Org Unit Path',
CONCAT('a', sa.aluno__id)  as 'Employee ID'
# ne.duplicated as 'Duplicated'

FROM 0adm_exportar_alunos sa
join adm_mdl0_new_emails ne on ne.id = sa.aluno__id
where ne.exists = 0
group by aluno__id
# order by Duplicated, 'Email Address';

" | tr '\t' ',' > created_emails_students.csv


###### Professores
$mysql_path $database -u $user -p$pass -e "
SELECT
SUBSTRING_INDEX(TRIM(professor__nome), ' ', 1) AS 'First Name',
TRIM(SUBSTRING(TRIM(professor__nome), INSTR(TRIM(professor__nome), substring_index(TRIM(professor__nome), ' ', 1))+LENGTH(substring_index(TRIM(professor__nome), ' ', 1)))) AS 'Last Name',
email AS 'Email Address',
professor__cpf 'Password',
'/SEDUC' as 'Org Unit Path',
professor__cpf 'Employee ID'
# ne.duplicated as 'Duplicated'

FROM 0adm_exportar_professores s
join adm_mdl0_new_emails ne on ne.id = s.professor__cpf
where ne.exists = 0
group by professor__cpf
# order by Duplicated, \`Email Address\`;
" | tr '\t' ',' > created_emails_professores.csv


# Cria tabela com os emails de estudantes
$mysql_path $database -u $user -p$pass -e "
Drop TABLE IF EXISTS 0adm_created_emails_students;
Drop TABLE IF EXISTS 0adm_created_emails_professores;

CREATE TABLE 0adm_created_emails_students AS
SELECT
SUBSTRING_INDEX(TRIM(aluno__nome), ' ', 1) AS 'First Name',
TRIM(SUBSTRING(TRIM(aluno__nome), INSTR(TRIM(aluno__nome), substring_index(TRIM(aluno__nome), ' ', 1))+LENGTH(substring_index(TRIM(aluno__nome), ' ', 1)))) AS 'Last Name',
ne.email AS 'Email Address',
'educajua' as 'Password',
'/SEDUC/Alunos' as 'Org Unit Path',
CONCAT('a', sa.aluno__id)  as 'Employee ID'

FROM 0adm_exportar_alunos sa
join adm_mdl0_new_emails ne on ne.id = sa.aluno__id
where ne.exists = 0
group by aluno__id
order by 'Email Address';

CREATE TABLE 0adm_created_emails_professores AS
SELECT
SUBSTRING_INDEX(TRIM(professor__nome), ' ', 1) AS 'First Name',
TRIM(SUBSTRING(TRIM(professor__nome), INSTR(TRIM(professor__nome), substring_index(TRIM(professor__nome), ' ', 1))+LENGTH(substring_index(TRIM(professor__nome), ' ', 1)))) AS 'Last Name',
email 'Email Address',
professor__cpf 'Password',
'/SEDUC' as 'Org Unit Path',
professor__cpf 'Employee ID'

FROM 0adm_exportar_professores s
join adm_mdl0_new_emails ne on ne.id = s.professor__cpf
where ne.exists = 0
group by professor__cpf;
"

rm cs_emails_suggestions.csv
rm adm_mdl0_new_emails.sql


# # atualizar novos emails na table users
# UPDATE `adm_users` u
# join 0adm_created_emails_professores cep on cep.`Employee ID` = u.`cpf`
# SET email = `Email Address`
# where `Email Address` NOT IN (select email from adm_users);

# UPDATE `adm_users` u
# join 0adm_created_emails_students cea on cea.`Employee ID` = u.`idnumber`
# SET email = `Email Address`
# where `Email Address` NOT IN (select email from adm_users);



# /opt/lampp/bin/mysqldump ep -u $user -p$pass adm_mdl0_servidores > ../mdl0_sql/adm_mdl0_servidores.sql
# /opt/lampp/bin/mysqldump ep -u $user -p$pass adm_mdl0_sigeduc_alunos > ../mdl0_sql/adm_mdl0_sigeduc_alunos.sql
# /opt/lampp/bin/mysqldump ep -u $user -p$pass 0adm_workspace > ../mdl0_sql/0adm_workspace.sql
# /opt/lampp/bin/mysqldump ep -u $user -p$pass adm_mdl0_relatorio_geral_servidores > ../mdl0_sql/adm_mdl0_relatorio_geral_servidores.sql

