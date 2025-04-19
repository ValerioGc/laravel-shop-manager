/*Database*/
CREATE DATABASE IF NOT EXISTS db_shop;

/*User*/
CREATE USER 'laravel_user'@'%' IDENTIFIED BY 'laravel_password';

/*Privileges*/
GRANT ALL PRIVILEGES ON db_shop.* TO 'laravel_user'@'%';
FLUSH PRIVILEGES;