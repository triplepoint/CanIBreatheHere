<?php
chdir(__DIR__ . '/../');

ini_set('error_log', 'logs/site_error.log');
ini_set('date.timezone', 'UTC');
ini_set('upload_max_filesize', '10M');
ini_set('log_errors', 'On');
ini_set('display_errors', 'Off');
ini_set('display_startup_errors', 'Off');
ini_set('error_reporting', E_ALL);
mb_internal_encoding('UTF-8');

require 'vendor/autoload.php';
?>
<!DOCTYPE html>
<html>
    <head>
    </head>
    <body>
        <p>Cattywompus!</p>
    </body>
</html>
