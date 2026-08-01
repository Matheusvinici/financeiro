pass="root"
database="ep"
/opt/lampp/bin/mysql $database -u root -p$pass -e "

# Atualiza nomes de salas
Update adm_exportar_alunos
SET sala__nome = REPLACE(sala__nome, ' INTEGRAL ', ' 0')
where sala__nome like 'SALA INTEGRAL%';

Update adm_exportar_alunos
SET sala__nome = REPLACE(sala__nome, 'ESTACAO', 'SALA')
where sala__nome like '%ESTACAO%';

Update adm_exportar_alunos
SET sala__nome = 'SALA 16'
where sala__nome = 'SALA 16 (LAB INF)';

Update adm_exportar_alunos
SET sala__nome = 'SALA 11'
where sala__nome = 'SALA 11  (MULTIMIDIA)';

Update adm_exportar_alunos
SET sala__nome = 'SALA 10'
where sala__nome = 'SALA 10 (ALMOXARIFADO)';

Update adm_exportar_alunos
SET sala__nome = 'SALA 09'
where sala__nome = 'SALA 09 (AUDITORIO)';

Update adm_exportar_alunos
SET sala__nome = 'SALA 07'
where sala__nome = 'SALA 07 - CORREÇÃO';

Update adm_exportar_alunos
SET sala__nome = REPLACE(sala__nome, ' ANEXO', '')
where sala__nome like '%ANEXO%';

Update adm_exportar_alunos
SET sala__nome = 'SALA 03'
where sala__nome = 'SALA 03 CONJ PENAL';

Update adm_exportar_alunos
SET sala__nome = 'SALA 02'
where sala__nome = 'SALA 02 ANEXO';

Update adm_exportar_alunos
SET sala__nome = 'SALA 02'
where sala__nome = 'SALA 02 - TERREO';

Update adm_exportar_alunos
SET sala__nome = 'SALA 01'
where sala__nome = 'SALA 01 EX';

Update adm_exportar_alunos
SET sala__nome = 'SALA 01'
where sala__nome = 'SALA 01 CONJ PENAL';

Update adm_exportar_alunos
SET sala__nome = 'SALA 01'
where sala__nome = 'SALA 01 ANEXO';

Update adm_exportar_alunos
SET sala__nome = 'SALA 01'
where sala__nome = 'SALA 01 - TERREO';

Update adm_exportar_alunos
SET sala__nome = 'SALA 01'
where sala__nome = 'SALA 01 - TERREO';

Update adm_exportar_alunos
SET sala__nome = 'SALA 11'
where sala__nome = 'SALA 011';

Update adm_exportar_alunos
SET sala__nome = 'SALA 10'
where sala__nome = 'SALA 010';

# Atualiza idnumber salas
UPDATE adm_salas s
    JOIN adm_escolas e ON s.escola_id = e.id
    JOIN adm_exportar_alunos a ON e.idnumber = a.escola__id AND s.nome = a.sala__nome
SET s.idnumber = a.sala__id;

# retira quebra de linhas
UPDATE adm_exportar_professores  SET disciplina__nome = TRIM(REPLACE(REPLACE(disciplina__nome, CHAR(13), ''), CHAR(10),''));

SELECT '' as 'Atualizando Idnumbers Disciplinas';
UPDATE adm_disciplinas d , adm_exportar_professores p
SET
d.idnumber = p.disciplina__id
WHERE d.nome = p.disciplina__nome;

SELECT '' as 'Atualizando Idnumbers funcoes';
UPDATE adm_cargos c , adm_exportar_equipe_escolar eq
SET
c.idnumber = eq.funcao__id
WHERE c.nome = eq.funcao__nome;

"
