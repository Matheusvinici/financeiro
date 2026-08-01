mysql juazeiro33 -u juazeiro33 -pGogo1352 -e "
UPDATE adm_lb_diagnosticos adm_lbd
inner join adm_lb_habilidade_serie as adm_hs on adm_hs.habilidade_id = adm_lbd.habilidade_id
SET adm_lbd.item_id = adm_hs.id
where adm_lbd.linhadebase_id = adm_hs.linhadebase_id
and adm_lbd.serie_id = adm_hs.serie_id
and adm_lbd.unidade_id = adm_hs.unidade_id;
"
