pass="root"
database="ep"
/opt/lampp/bin/mysql $database -u root -p$pass -e "
UPDATE adm_avaliacoes
SET
  atv1 = CASE WHEN atv1 > 2 THEN 2 ELSE atv1 END,
  atv2 = CASE WHEN atv2 > 3 THEN 3 ELSE atv2 END,
  atv3 = CASE WHEN atv3 > 5 THEN 5 ELSE atv3 END,
  media = CASE WHEN media > 10 THEN 10 ELSE media END,
  recuperacao = CASE WHEN recuperacao > 10 THEN 10 ELSE recuperacao END,
  media_unidade = CASE WHEN media_unidade > 10 THEN 10 ELSE media_unidade END;
"