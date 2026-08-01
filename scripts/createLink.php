<?php

// specifying target
$target_pointer = "/home/u361322959/domains/escolapresente.juazeiro.ba.gov.br/juazeiroba/storage/app/public";
// specifying link  name
$link_name = '/home/u361322959/domains/escolapresente.juazeiro.ba.gov.br/public_html/storage';
// creating alink using symlink() function
$test = symlink($target_pointer, $link_name);


?>