clear
dir="$PWD"

#!/bin/bash
set -e

if [[ $(pwd) == */scripts ]]; then
    echo "✅ Você está na pasta script"
    source ../.env
else
    # Carrega o arquivo .env
    if [ -f "$dir/.env" ]; then
      source $dir/.env
    else
      echo "Arquivo .env não encontrado!"
      exit 1
    fi
fi


# hash="8973a6ee5d70eee47ab8"
# DbUser="mariadb"

if [ "$APP_ENV" == "homo" ] || [ "$APP_ENV" == "production" ]; then
    echo "🌐 Ambiente de produção detectado. Usando variáveis de ambiente do VPS."
DB_PASSWORD=$DB_PASSWORD_rt
DB_USERNAME=root
DB_SERVER=VPS-Hostinger
else
echo "💻 Ambiente local detectado. Carregando variáveis do .env."
fi

# Verifica se as variáveis necessárias estão definidas
if [ -z "$DB_HOST" ] || [ -z "$DB_DATABASE" ] || [ -z "$DB_USERNAME" ] || [ -z "$DB_PASSWORD" ] || [ -z "$APP_ENV" ]; then
  echo "Uma ou mais variáveis de ambiente não estão definidas!"
  exit 1
fi

# Executa os comandos SQL
$URL_MYSQL -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" -D "$DB_DATABASE" -e "
SET @env = '$APP_ENV';

-- Ajusta o tamanho do buffer de chave para 256MB
# SET GLOBAL key_buffer_size = 268435456;

-- Ajusta o tamanho máximo de pacote permitido para 1GB
SET GLOBAL max_allowed_packet = 1073741824;

-- Ajusta o cache de tabelas abertas para 2000
SET GLOBAL table_open_cache = 2000;

-- Ajusta o tamanho do buffer de ordenação para 2MB
SET GLOBAL sort_buffer_size = 2097152;

-- Ajusta o comprimento do buffer de rede para 16KB
SET GLOBAL net_buffer_length = 16384;

-- Ajusta o tamanho do buffer de leitura para 2MB
SET GLOBAL read_buffer_size = 2097152;

-- Ajusta o tamanho do buffer de leitura aleatória para 1MB
SET GLOBAL read_rnd_buffer_size = 1048576;

-- Ajusta o tamanho do buffer de classificação MyISAM para 128MB
SET GLOBAL myisam_sort_buffer_size = 134217728;

SET @buffer_pool_size = CASE 
    WHEN @env = 'local' THEN 4474836480  -- 4 GB
    WHEN @env = 'production' THEN 21474836480  -- 20 GB
END;

SET GLOBAL innodb_buffer_pool_size = @buffer_pool_size;

# exibir mesnagem de configuração do buffer pool atual
SELECT CONCAT('Configuração do innodb_buffer_pool_size: ', @@innodb_buffer_pool_size) AS BufferPoolSize;

-- Ajusta o tamanho do arquivo de log InnoDB para 256MB
-- SET GLOBAL innodb_log_file_size = 268435456;

-- Ajusta o tamanho do buffer de log InnoDB para 16MB
-- SET GLOBAL innodb_log_buffer_size = 16777216;

-- Mantém a configuração de flush do log no commit da transação em 1 (durabilidade ACID)
SET GLOBAL innodb_flush_log_at_trx_commit = 1;

-- Ajusta o tempo limite de espera de bloqueio InnoDB para 50 segundos
SET GLOBAL innodb_lock_wait_timeout = 50;

SET GLOBAL tmp_table_size = 67108864; -- 64MB
SET GLOBAL max_heap_table_size = 67108864; -- 64MB
SET join_buffer_size = 67108864; -- 64MB
SET GLOBAL max_connections = 80;

" || {
  echo "Erro ao executar os comandos MySQL!"
  exit 1
}

echo "Configurações do MySQL aplicadas com sucesso!"