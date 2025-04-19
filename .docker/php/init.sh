#!/bin/bash

echo "Attesa del database MySQL..."
php /usr/local/bin/wait-for-mysql.php

if [ ! -d "vendor" ]; then
    echo "The 'vendor' directory does not exist. Executing 'composer install'..."
    composer install --no-interaction --optimize-autoloader
else
    echo "Dependecies already installed. Skipping 'composer install'..."
fi

php artisan key:generate
php artisan config:clear
php artisan config:cache

mkdir -p /var/www/html/storage/framework/cache \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views
chmod -R 777 /var/www/html/storage /var/www/html/public

echo "Checking DB..."
DB_EMPTY=$(php -r "
\$host = getenv('DB_HOST') ?: 'mysql';
\$port = getenv('DB_PORT') ?: '3306';
\$database = getenv('DB_DATABASE') ?: 'db_shop';
\$username = getenv('DB_USERNAME') ?: 'laravel_user';
\$password = getenv('DB_PASSWORD') ?: 'laravel_password';

try {
    \$pdo = new PDO(\"mysql:host=\$host;port=\$port;dbname=\$database\", \$username, \$password);
    \$tables = \$pdo->query(\"SHOW TABLES\")->fetchAll(PDO::FETCH_COLUMN);
    echo count(\$tables) > 0 ? '0' : '1';
} catch (Exception \$e) {
    echo '1'; 
}
")

if [ "$DB_EMPTY" -eq "1" ]; then
    echo "DB Empty. Executing migrations..."
    php artisan migrate --force
else
    echo "DB Not empty, skipping migrations..."
fi

echo "Starting PHP server..."

cat "./.docker/php/logo.txt"

php artisan serve --host=0.0.0.0 --port=8000


echo"
               ####                                                                         
               ####                                                                         
       ############                                                                         
       ############      ###                                                                
   ###################  #####             ###                    ##         ###                
   ###################  ##########        ###                    ###      ##                   
 ################################     ### ###    ####       ###   ###    ##   ####    ######## 
##############################     ##########  ########  ######## ###  ##  ########## ########
############################      ###    #### ###    ### ##       ### ###  ###    ### ###    
 ##########################       ###     ### ##     ### ##       #######  ########## ##      
 #########################         #### ####  ####  #### #######  ###  ###  ###       ##      
  ######################            #######     ######      ###   ###    ### #######  ##    
    #################                                                                       
         ##########                                                                            
"