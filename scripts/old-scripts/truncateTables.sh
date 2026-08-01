fname="$1"

/opt/lampp/bin/mysql -u root -proot -e "
USE ep;
SET FOREIGN_KEY_CHECKS=0;
TRUNCATE TABLE $fname;
SET FOREIGN_KEY_CHECKS=1;
"