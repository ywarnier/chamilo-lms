<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
require_once __DIR__.'/../inc/lib/webservices/xapi.class.php';

use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();

error_log(__FILE__.' called');
$resources = $request->query->get('resources');

if (class_exists('XAPI')) {
    $xapi = new XAPI();
    switch ($resources) {
        case 'statements':
            $xapi->statements($request);
            break;
        default:
            break;
    }
    error_log(__FILE__.' executed');
} else {
    echo "Class $class does not exist";
}
