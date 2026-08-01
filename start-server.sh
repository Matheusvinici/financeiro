#!/bin/bash

# Caminho para o arquivo composer.json
composer_file="composer.json"

# Verifica se o jq está instalado
if ! command -v jq &> /dev/null
then
    echo "jq não está instalado. Por favor, instale o jq para continuar. \n
        sudo apt install jq
    "
    exit 1
fi

# Verifica se o arquivo composer.json existe
if [ ! -f "$composer_file" ]; then
    echo "Arquivo composer.json não encontrado!"
    exit 1
fi

# Obtém a versão do PHP do composer.json
versaoPHP=php$(jq -r '.require.php' "$composer_file")
latest_version=$(echo $versaoPHP | grep -oP '[0-9]+\.[0-9]+' | sort -r | head -n 1)

echo "Versão do PHP: $latest_version"
sudo update-alternatives --set php /usr/bin/php$latest_version
sudo a2enmod php$latest_version
sudo /etc/init.d/apache2 stop
sudo /opt/lampp/lampp restart

# npm run dev
php artisan serve