##update composer in ubuntu
#update packages
sudo apt-get update
#if you don't have curl install it
sudo apt-get install curl
#download installer
sudo curl -s https://getcomposer.org/installer | php
#move composer.phar file
sudo mv composer.phar /usr/local/bin/composer
#check composer version
composer -v
# activate Curl
sudo apt-get install php-curl