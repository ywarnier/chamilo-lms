<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
require_once __DIR__.'/../inc/lib/webservices/xapi.class.php';

$mode = 'debug'; // 'debug' or 'production'
$server = new \Jacwright\RestServer\RestServer($mode);
$server->refreshCache(); // uncomment momentarily to clear the cache if classes change in production mode

$class = 'XAPI';

if (class_exists($class)) {
    $server->addClass($class, '/xapi');
    $server->handle();
} else {
    echo "Class $class does not exist";
}
