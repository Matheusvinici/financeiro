pass="root"
database="ep"
/opt/lampp/bin/mysql $database -u root -p$pass -e "

# SELECT '' as 'Atualizando Emails dos funcionarios';
# UPDATE adm_users u , adm_mdl0_workspace w
# SET
# u.email=w.Email_Address_Required
# WHERE u.cpf = replace(replace(w.Employee_ID, '.', ''), '-', '')
# and Employee_ID <> '';

# SELECT '' as 'Atualizando idnumber dos alunos';
# UPDATE adm_users u , adm_exportar_alunos a
# SET
# u.idnumber=a.aluno__id
# WHERE u.name = aluno__nome
# and u.data_nascimento = aluno__data_nascimento
# and u.nome_responsavel = responsavel__nome;

# SELECT '' as 'Atualizando idnumber da equipe escolar';
# UPDATE adm_users u , adm_exportar_equipe_escolar ee
# SET
# u.idnumber=ee.funcionario__id
# WHERE u.cpf = replace(replace(ee.funcionario__cpf, ',', ''), '-', '');

SELECT '' as 'Atualizando idnumber dos professores';
UPDATE adm_users u , adm_exportar_professores p
SET u.idnumber=p.professor__id
WHERE u.cpf = p.professor__cpf
and u.cpf <> ''
"
