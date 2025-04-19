<?php
$host = getenv('DB_HOST') ?: 'mysql';
$port = getenv('DB_PORT') ?: 3306;
$timeout = 30;

echo "Waiting for MySQL database...\n";
$start = time();
while (true) {
    $connection = @fsockopen($host, $port);
    if ($connection) {
        fclose($connection);
        echo "Database MySQL ready.\n";
        break;
    }
    if (time() - $start > $timeout) {
        echo "Timeout expired. Impossible to connect to the MySQL database.\n";
        exit(1);
    }
    sleep(2);
}
