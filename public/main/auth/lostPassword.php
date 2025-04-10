<?php

/* For licensing terms, see /license.txt */

/**
 * SCRIPT PURPOSE :.
 *
 * This script allows users to retrieve the password of their profile(s)
 * on the basis of their e-mail address. The password is send via email
 * to the user.
 *
 * Special case : If the password are encrypted in the database, we have
 * to generate a new one.
 *
 * @todo refactor, move relevant functions to code libraries
 */

use Chamilo\CoreBundle\Entity\UserAuthSource;

require_once __DIR__.'/../inc/global.inc.php';

// Custom pages
// Had to move the form handling in here, because otherwise there would
// already be some display output.

// Forbidden to retrieve the lost password
if ('false' === api_get_setting('allow_lostpassword')) {
    api_not_allowed(true);
}

if (!api_is_anonymous()) {
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$reset = $_REQUEST['reset'] ?? '';
$userId = $_REQUEST['id'] ?? '';

$this_section = SECTION_CAMPUS;

$tool_name = get_lang('I lost my password');

if ($reset && $userId) {
    $messageText = Login::reset_password($reset, $userId, true);

    Display::addFlash(
        Display::return_message($messageText, 'info', false)
    );
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$form = new FormValidator('lost_password');
$form->addHeader($tool_name);
$form->addText(
    'user',
    [
        get_lang('Username or e-mail address'),
        get_lang('Enter the username or the e-mail address with which you registered and we will send your password.'),
    ],
    true
);

$captcha = api_get_setting('allow_captcha');
$allowCaptcha = 'true' === $captcha;

if ($allowCaptcha) {
    $ajax = api_get_path(WEB_AJAX_PATH).'form.ajax.php?a=get_captcha';
    $options = [
        'width' => 220,
        'height' => 90,
        'callback' => $ajax.'&var='.basename(__FILE__, '.php'),
        'sessionVar' => basename(__FILE__, '.php'),
        'imageOptions' => [
            'font_size' => 20,
            'font_path' => api_get_path(SYS_FONTS_PATH).'opensans/',
            'font_file' => 'OpenSans-Regular.ttf',
            //'output' => 'gif'
        ],
    ];

    $captcha_question = $form->addElement(
        'CAPTCHA_Image',
        'captcha_question',
        '',
        $options
    );
    $form->addElement('static', null, null, get_lang('Click on the image to load a new one.'));

    $form->addElement('text', 'captcha', get_lang('Enter the letters you see.'), ['size' => 40]);
    $form->addRule('captcha', get_lang('Enter the characters you see on the image'), 'required', null, 'client');

    $form->addRule('captcha', get_lang('The text you entered doesn\'t match the picture.'), 'CAPTCHA', $captcha_question);
}

$form->addButtonSend(get_lang('Send message'));

if ($form->validate()) {
    $values = $form->exportValues();
    $user = Login::get_user_accounts_by_username($values['user']);

    if (!$user) {
        $messageText = get_lang('There is no account with this user and/or e-mail address');

        Display::addFlash(
            Display::return_message($messageText, 'error', false)
        );
        header('Location: '.api_get_self());
        exit;
    }

    $passwordEncryption = api_get_configuration_value('password_encryption');

    if ('none' === $passwordEncryption) {
        $messageText = Login::send_password_to_user($user, true);

        Display::addFlash(
            Display::return_message($messageText, 'info', false)
        );
        header('Location: '.api_get_path(WEB_PATH));
        exit;
    }

    if (in_array(UserAuthSource::CAS, $user['auth_sources'])) {
        Display::addFlash(
            Display::return_message(get_lang('Could not reset password, contact your helpdesk.'), 'info', false)
        );
        header('Location: '.api_get_path(WEB_PATH));
        exit;
    }

    $userResetPasswordSetting = api_get_setting('user_reset_password');

    if ('true' === $userResetPasswordSetting) {
        $userObj = api_get_user_entity($user['id']);
        Login::sendResetEmail($userObj);

        header('Location: '.api_get_path(WEB_PATH));
        exit;
    }

    $messageText = Login::handle_encrypted_password($user, true);

    Display::addFlash(
        Display::return_message($messageText, 'info', false)
    );
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$tpl = new Template(null);
$tpl->assign('content', $form->toHtml());
$tpl->display_one_col_template();
