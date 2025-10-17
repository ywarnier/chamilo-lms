<?php

/* For licensing terms, see /license.txt */

/**
 * Makes sure all students are subscribed to all URLs except the URL ID 1
 */

// Ensure the script is run from the command line
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

require __DIR__.'/../../inc/global.inc.php';

$urls = api_get_access_urls();
$destinationUrls = [];
foreach ($urls as $url) {
    // We assume the administration URL is id=1
    if (1 !== (int) $url['id']) {
        $destinationUrls[] = $url['id'];
    }
}

$userTable = Database::get_main_table(TABLE_MAIN_USER);

$sql = <<<SQL
            SELECT id
            FROM $userTable
            WHERE
                active = 1 AND
                status = 5
SQL;
$result = Database::query($sql);
$users = [];
while ($user = Database::fetch_assoc($result)) {
    $users[] = $user['id'];
}

UrlManager::add_users_to_urls($users, $destinationUrls);
