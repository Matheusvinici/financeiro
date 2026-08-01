sudo /opt/lampp/lampp stop
sudo /etc/init.d/apache2 stop
sudo /opt/lampp/lampp start
sudo service mysql stop

sudo /etc/init.d/apache2 start
sudo service mysql start

clear

/opt/lampp/bin/php artisan serve