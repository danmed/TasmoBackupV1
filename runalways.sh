sed -i "s/mysqlserver/$MYSQL_SERVER/g" /var/www/html/data/config.inc.php
sed -i "s/mysqlusername/$MYSQL_USERNAME/g" /var/www/html/data/config.inc.php
sed -i "s/mysqlpassword/$MYSQL_PASSWORD/g" /var/www/html/data/config.inc.php
sed -i "s/dbtype/$DBTYPE/g" /var/www/html/data/config.inc.php
sed -i "s#dbname#$DBNAME#g" /var/www/html/data/config.inc.php
