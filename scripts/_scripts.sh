# abre crontab com nano
export VISUAL=nano; crontab -e


# Obter as colunas da tabela
COLUMNS=$(/opt/lampp/bin/mysql -u root -p'root' $database_tmp -e "$COLUMN_QUERY" | awk '{ print $1 }')

if [[ -n "$COLUMNS" ]]; then
    # Formatar os nomes das colunas
    COLUMN_NAMES=""
    for column in $COLUMNS; do
        COLUMN_NAMES+="`printf "%q" "$column"`, "
    done
    COLUMN_NAMES="${COLUMN_NAMES%, }"

    Montar a declaração INSERT
    INSERT_STATEMENT="USE $database; INSERT INTO $table SELECT ($COLUMN_NAMES) FROM $database_tmp.$table;"
fi

# Salvar credenciais do git
git config --global credential.helper store

# Obter aquivos alterados em uma branch
git diff --name-only $(git merge-base <dir_atual> <dir_comparacao>)..<dir_atual> > lista_de_alteracoes.txt


#instalação do php
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.4)"
# desinstalação do php
/bin/bash -c "$(curl -fsSL https://php.new/uninstall/linux/8.4)"

# Fonte padrão chatwoot:
chatwoot/chatwoot:v4.0.1
#versão atual do chatwoot:
chatwoot/chatwoot:latest
