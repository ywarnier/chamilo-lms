<?php

/* For licensing terms, see /license.txt
 * This script disable users depending on 3 use cases and send an email to the user to inform him
 * The 3 cases for disabling a user are :
 *  * Case 1
 *   If a learner has not validated his terms and conditions and has not connected to the
 *   platform for more than 3 months then deactivate his account and send an email to the user
 *  * Case 2
 *   If a learner has validated his terms and conditions and has not connected to the plateform
 *   for more than 6 months then deactivate his account and send an email to the learner and to
 *   it's superior. The superior of the learner is also removed from this learner (deassignment).
 *  * Case 3
 *   If a learner has completed its courses (a learner is considered to have finished their courses
 *   if he has a generated the general certificate) and has not connected to the platfrom for more
 *   than 6 months then deactivate his account and send an email to the learner.
 * */
die('Remove the "die()" statement on line '.__LINE__.' to execute this script'.PHP_EOL);
require_once __DIR__.'/../../public/main/inc/global.inc.php';

$options = getopt('', ['url:']);
$baseUrl = isset($options['url']) ? rtrim($options['url'], '/') : null;
$senderId = api_get_setting('platform.disable_user_conditions_sender_id');

if (empty($senderId)) {
    exit;
}

$test = true;
$newLine = '<br />';
if (PHP_SAPI == 'cli') {
    $newLine = PHP_EOL;
}
$userReportList = [];
$extraFieldValue = new ExtraField('user');
$extraFieldInfo = $extraFieldValue->get_handler_field_info_by_field_variable('termactivated');
$fieldId = $extraFieldInfo['id'];
$senderInfo = api_get_user_info($senderId);
if (empty($senderInfo)) {
    echo 'Please set the configuration value: "disable_user_conditions_sender_id" for a valid user.';
}

$statusCondition = ' AND u.status = '.STUDENT;

$date = new Datetime();
$now = $date->format('Y-m-d H:i:s');

$date = $date->sub(new DateInterval('P3M'));
$date3Months = $date->format('Y-m-d H:i:s');

$date = new Datetime();
$date = $date->sub(new DateInterval('P6M'));
$date6Months = $date->format('Y-m-d H:i:s');

// 1. Not contract not connected in 3 months
$sql = "SELECT u.id
        FROM user u
        LEFT JOIN extra_field_values ev
        ON u.id = ev.item_id AND field_id = $fieldId
        WHERE
            (ev.field_value IS NULL OR ev.field_value = '') AND
            u.active = 1
            $statusCondition
        ";

$result = Database::query($sql);
$students = Database::store_result($result);
foreach ($students as $student) {
    $studentId = $student['id'];
    $lastDate = Tracking::get_last_connection_date($studentId, false, true);
    // OFAJ : In Chamilo 2 until 2024/12/01 there was no login registered after inscription
    // This is a special fix for those cases
    if ($lastDate === false) {
        $sqlStudentCreationDate = "SELECT created_at
        FROM user
        WHERE id = $studentId
        ";
        $resultStudentCreationDate = Database::query($sqlStudentCreationDate);
        $obj = Database::fetch_object($resultStudentCreationDate);
        $lastDate = $obj->created_at;
    }
    $lastDate = api_get_utc_datetime($lastDate);

    if ($date3Months > $lastDate) {
        $disabledUser = api_get_user_info($studentId);
        if (empty($disabledUser)) {
            continue;
        }
        if (!isset($userReportList[$studentId]['message'])) {
            $userReportList[$studentId]['message'] = '';
        }
        $userReportList[$studentId]['message'] .= $newLine."User# $studentId (".$disabledUser['username'].") to be disabled. Case 1. Last connection: $lastDate - 3 months: $date3Months ";

        $language = $disabledUser['locale'];
        $subject = get_lang('AccountDisabled', $language).': '.$disabledUser['complete_name'];
        $content = get_lang('DisableUserCase1', $language);

        $userReportList[$studentId]['message'] .= $newLine.
            'Mail will be send to: '.$disabledUser['username'].' ('.$disabledUser['email'].')'.$newLine.
            'Subject: '.$subject.$newLine.
            'Content: '.$content.$newLine;

        if (false === $test) {
            MessageManager::send_message(
                $studentId,
                $subject,
                $content,
                [],
                [],
                0,
                0,
                0,
                0,
                $senderId,
                false,
                0,
                false,
                false,
                null,
                $baseUrl
            );
            UserManager::disable($studentId);
        }
    }
}

// 3. Certificate completed not connected 6 months.
$sql = "SELECT c.user_id, MAX(login_date) latest_login_date
        FROM gradebook_certificate c
        INNER JOIN track_e_login l
        ON (l.login_user_id = c.user_id)
        INNER JOIN user u
        ON (l.login_user_id = u.id)
        WHERE 1=1
        $statusCondition
        GROUP BY c.user_id  ";

$result = Database::query($sql);
$students = Database::store_result($result);
foreach ($students as $student) {
    $studentId = $student['user_id'];
    $lastDate = $student['latest_login_date'];

    if ($date6Months > $lastDate) {
        $disabledUser = api_get_user_info($studentId);
        if (empty($disabledUser)) {
            continue;
        }

        if (1 != $disabledUser['active']) {
            continue;
        }

        $language = $disabledUser['locale'];
        $subject = get_lang('AccountDisabled', $language).': '.$disabledUser['complete_name'];
        $content = get_lang('DisableUserCase3Student', $language);

        if (!isset($userReportList[$studentId]['message'])) {
            $userReportList[$studentId]['message'] = '';
        }

        $userReportList[$studentId]['message'] .= $newLine."User# $studentId (".$disabledUser['username'].") to be disabled. Case 3. Last connection: $lastDate - 6 months: $date6Months ";
        $userReportList[$studentId]['message'] .= $newLine.
            'Mail will be send to: '.$disabledUser['username'].$newLine.
            'Subject: '.$subject.$newLine.
            'Content: '.$content;

        if (false === $test) {
            MessageManager::send_message(
                $studentId,
                $subject,
                $content,
                [],
                [],
                0,
                0,
                0,
                0,
                $senderId,
                false,
                0,
                false,
                false,
                null,
                $baseUrl
            );
            UserManager::disable($studentId);
        }
    }
}

// 2. Validated contract, not connected + 6 months
$sql = "SELECT u.id
        FROM user u
        INNER JOIN extra_field_values ev
        ON u.id = ev.item_id AND field_id = $fieldId
        WHERE
            ev.field_value = 1 AND
            u.active = 1
            $statusCondition
        ";

$result = Database::query($sql);
$students = Database::store_result($result);
foreach ($students as $student) {
    $studentId = $student['id'];
    $lastDate = Tracking::get_last_connection_date($studentId, false, true);
    $lastDate = api_get_utc_datetime($lastDate);

    if ($date6Months > $lastDate) {
        $disabledUser = api_get_user_info($studentId);
        if (empty($disabledUser)) {
            continue;
        }

        if (!isset($userReportList[$studentId]['message'])) {
            $userReportList[$studentId]['message'] = '';
        }

        $userReportList[$studentId]['message'] .= $newLine."User# $studentId (".$disabledUser['username'].") to be disabled. Case 2 . Last connection: $lastDate - 6 months: $date6Months ";

        $subject = get_lang('AccountDisabled', $disabledUser['locale']).': '.$disabledUser['complete_name'];
        $content = get_lang('DisableUserCase2', $disabledUser['locale']);

        $userReportList[$studentId]['message'] .= $newLine.'Mail will be send to: '.$disabledUser['username'].$newLine.'Subject: '.$subject.$newLine.'Content: '.$content.$newLine;

        $subjectBoss = '';
        $contentBoss = '';
        $studentBoss = UserManager::getFirstStudentBoss($studentId);
        $bossInfo = [];
        if (!empty($studentBoss)) {
            $bossInfo = api_get_user_info($studentBoss);
            if ($bossInfo) {
                $subjectBoss = get_lang('AccountDisabled', $bossInfo['locale']).': '.$disabledUser['complete_name'];
                $contentBoss = sprintf(get_lang('DisableUserCase2StudentX', $bossInfo['locale']), $disabledUser['complete_name']);
                $userReportList[$studentId]['message'] .= $newLine.'Mail will be send to: '.$bossInfo['username'].$newLine.'Subject: '.$subjectBoss.$newLine.'Content: '.$contentBoss.$newLine;
            }
        }

        if (false === $test) {
            MessageManager::send_message(
                $studentId,
                $subject,
                $content,
                [],
                [],
                0,
                0,
                0,
                0,
                $senderId,
                false,
                0,
                false,
                false,
                null,
                $baseUrl
            );
            UserManager::disable($studentId);
            if (!empty($bossInfo) && !empty($subjectBoss)) {
                MessageManager::send_message(
                    $studentBoss,
                    $subjectBoss,
                    $contentBoss,
                    [],
                    [],
                    0,
                    0,
                    0,
                    0,
                    $senderId,
                    false,
                    0,
                    false,
                    false,
                    null,
                    $baseUrl
                );
            }
            UserManager::removeAllBossFromStudent($studentId);
        }
    }
}

if ($test) {
    echo '<h3>No changes have been made.</h3>'.$newLine;
}

echo 'Sender user: '.$senderInfo['complete_name'].' ('.$senderInfo['email'].') '.$newLine;
echo "Now: $now".$newLine;
echo "3 Months old: $date3Months".$newLine;
echo "6 Months old: $date6Months".$newLine;
echo $newLine;

ksort($userReportList);

foreach ($userReportList as $data) {
    echo $data['message'].$newLine;
}
