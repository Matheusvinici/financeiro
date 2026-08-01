mysql juazeiro33 -u juazeiro33 -pGogo1352 -e "

UPDATE \`adm_users\` u
JOIN \`0adm_created_emails_professores\` cep ON cep.\`Employee ID\` = u.\`cpf\`
SET u.email = cep.\`Email Address\`
WHERE cep.\`Email Address\` NOT IN
(SELECT * FROM (select email from \`adm_users\`) u2);

UPDATE \`adm_users\` u
JOIN \`0adm_created_emails_students\` ces ON ces.\`Employee ID\` = u.\`idnumber\`
SET u.email = ces.\`Email Address\`
WHERE ces.\`Email Address\` NOT IN
(SELECT * FROM (select email from \`adm_users\`) u2);
"
