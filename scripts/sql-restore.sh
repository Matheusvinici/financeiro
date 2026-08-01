# loop
clear

database="ep"
database_user="root"
user_password='root'

if [ -z "$1" ]
then
    echo "Qual arquivo a restaurar?"
    echo "Exemplo: scripts/sql-restore.sh path_to_scriptSql/dump.sql"
    exit 127;
else
    echo "Ok vou restaurar as tabelas para "$1".";
fi
    # echo $1

## Inicia a restauração com otimizações
/opt/lampp/bin/mysql -u $database_user -p$user_password $database <<EOF
-- Desabilitar verificações de chaves estrangeiras
SET foreign_key_checks = 0;

-- Desabilitar logs binários temporariamente
SET sql_log_bin = 0;

-- Desabilitar criação de índices temporariamente (apenas se a tabela for InnoDB)
SET unique_checks = 0;
SET autocommit = 0;

-- Alterar a frequência de flush do InnoDB para reduzir operações de disco
SET GLOBAL innodb_flush_log_at_trx_commit = 2;

-- Restaurar o dump
SOURCE $1;

-- Habilitar as verificações e logs novamente após a restauração
SET foreign_key_checks = 1;
SET sql_log_bin = 1;
SET unique_checks = 1;
SET autocommit = 1;
SET GLOBAL innodb_flush_log_at_trx_commit = 1;

-- Recriar índices (caso necessário)
-- ALTER TABLE nome_da_tabela ENABLE KEYS;

COMMIT;
EOF

echo "Restauração concluída!"

        hora_atual=$(date +"%H:%M:%S")
        echo "Fim: $hora_atual"
