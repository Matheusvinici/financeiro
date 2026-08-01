#!/bin/bash

# carregar .env
source .env

# verificar o ambiente
if [ "$APP_ENV" == "local" ]; then
    echo "Você está em um ambiente local, se desejar realmente realizar um reset digite 'reset' e pressione Enter. \n
    Isso irá sobrescrever todas as alterações locais e restaurar o repositório para o estado remoto."
    read -p "Digite 'reset' para continuar: " user_input
    if [ "$user_input" != "reset" ]; then
        echo "Operação cancelada."
        exit 1
    fi
fi

# obter nome da branch atual
current_branch=$(git rev-parse --abbrev-ref HEAD)
git config --global credential.helper store
hora_inicial=$(date +"%Y-%m-%d %H:%M:%S")

echo "Hora inicial: $hora_inicial"

echo "Resetando repositório para o estado remoto"
git fetch origin
git reset --hard origin/$current_branch

php artisan Storage:link

# read -p "Deseja rodar as migrations? (y/n) " -n 1 -r
# if [[ $REPLY =~ ^[Yy]$ ]]
# then
php artisan migrate --force
# fi

bash clearCaches.sh

php artisan log-viewer:publish

hora_atual=$(date +"%Y-%m-%d %H:%M:%S")
echo "Hora final: $hora_atual"