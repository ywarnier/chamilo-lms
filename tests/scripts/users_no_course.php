<?php
/* For licensing terms, see /license.txt */

/**
 * This script select all users with no course subscriptions and
 * add it to a selected course.
 */
die('Remove the "die()" statement on line '.__LINE__.' to execute this script'.PHP_EOL);
require_once __DIR__.'/../../public/main/inc/global.inc.php';

api_protect_admin_script();

// Course that users with no course will be registered:
$courseId = '';

$user = Database::get_main_table(TABLE_MAIN_USER);
$userCourse = Database::get_main_table(TABLE_MAIN_COURSE_USER);

$sql = "SELECT * FROM $user WHERE user_id NOT IN (
            SELECT user_id FROM $userCourse
        ) AND status <> ".ANONYMOUS."
        ";
$result = Database::query($sql);
$students = Database::store_result($result);

if (!empty($students)) {
    foreach ($students as $student) {
        var_dump($student['username'].'- '.$student['user_id']);
        $result = CourseManager::subscribeUser($student['user_id'], $courseId);
        var_dump($result);
        echo '<br />';
    }
}
