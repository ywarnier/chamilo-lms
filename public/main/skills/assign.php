<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Skill;

/**
 * Page for assign skills to a user.
 *
 * @author: Jose Loguercio <jose.loguercio@beeznest.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

$userId = isset($_REQUEST['user']) ? (int) $_REQUEST['user'] : 0;

if (empty($userId)) {
    api_not_allowed(true);
}

SkillModel::isAllowed($userId);

$user = api_get_user_entity($userId);

if (!$user) {
    api_not_allowed(true);
}

$entityManager = Database::getManager();
$skillManager = new SkillModel();
$skillRepo = $entityManager->getRepository(Skill::class);
$skillRelSkill = $entityManager->getRepository(\Chamilo\CoreBundle\Entity\SkillRelSkill::class);
$skillLevelRepo = $entityManager->getRepository(\Chamilo\CoreBundle\Entity\Level::class);
$skillUserRepo = $entityManager->getRepository(\Chamilo\CoreBundle\Entity\SkillRelUser::class);

$skillLevels = api_get_setting('skill.skill_levels_names', true);
$autoloadSubskills = api_get_setting('skill.manual_assignment_subskill_autoload');

$skillsOptions = ['' => get_lang('Select')];
$acquiredLevel = ['' => get_lang('none')];
$formDefaultValues = [];

if (empty($skillLevels)) {
    $skills = $skillRepo->findBy([
        'status' => Skill::STATUS_ENABLED,
    ]);
    /** @var Skill $skill */
    foreach ($skills as $skill) {
        $skillsOptions[$skill->getId()] = $skill->getTitle();
    }
} else {
    // Get only root elements
    $skills = $skillManager->getChildren(1);
    foreach ($skills as $skill) {
        $skillsOptions[$skill['data']['id']] = $skill['data']['title'];
    }
}
$skillIdFromGet = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$currentValue = isset($_REQUEST['current_value']) ? (int) $_REQUEST['current_value'] : 0;
$currentLevel = isset($_REQUEST['current']) ? (int) str_replace('sub_skill_id_', '', $_REQUEST['current']) : 0;

// Handle skill and subskill selection
$skillId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : key($skillsOptions);
$subSkillList = isset($_REQUEST['sub_skill_list']) ? explode(',', $_REQUEST['sub_skill_list']) : [];
$subSkillList = array_unique($subSkillList);

if (empty($subSkillList) && $skillId) {
    if ('true' === $autoloadSubskills) {
        $skillRelSkill = new SkillRelSkillModel();
        $parents = $skillRelSkill->getSkillParents($skillId);
        ksort($parents);

        $subSkillList = [];
        foreach ($parents as $parent) {
            if ($parent['skill_id'] != 1) {
                $subSkillList[] = $parent['skill_id'];
            }
        }
        $subSkillList[] = $skillId;
        $subSkillList = array_unique($subSkillList);

        $firstParentId = $subSkillList[0];
        $subSkillListToString = implode(',', array_slice($subSkillList, 0, -1)) . ',' . $skillId;
        $currentLevel = 'sub_skill_id_' . count($subSkillList) - 1;

        $currentUrl = api_get_path(WEB_CODE_PATH).'skills/assign.php?user='.$userId.'&id='.$firstParentId.'&current_value='.$skillId.'&current='.$currentLevel.'&sub_skill_list='.$subSkillListToString;
        header('Location: '.$currentUrl);
        exit;
    }
}

if (!empty($subSkillList)) {
    // Compare asked skill with current level
    $correctLevel = false;
    if (isset($subSkillList[$currentLevel]) && $subSkillList[$currentLevel] == $currentValue) {
        $correctLevel = true;
    }

    // Level is wrong probably user change the level. Fix the subSkillList array
    if (!$correctLevel) {
        $newSubSkillList = [];
        $counter = 0;
        foreach ($subSkillList as $subSkillId) {
            if ($counter == $currentLevel) {
                $subSkillId = $currentValue;
            }
            $newSubSkillList[$counter] = $subSkillId;
            if ($counter == $currentLevel) {
                break;
            }
            $counter++;
        }
        $subSkillList = $newSubSkillList;
    }
}

if (!empty($currentLevel)) {
    $level = $currentLevel + 1;
    if ($level < count($subSkillList)) {
        $remove = count($subSkillList) - $currentLevel;
        $newSubSkillList = array_slice($subSkillList, 0, count($subSkillList) - $level);
        $subSkillList = $newSubSkillList;
    }
}

$skillId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : key($skillsOptions);
$skill = $skillRepo->find($skillId);
$profile = false;
if ($skill) {
    $profile = $skill->getLevelProfile();
}

if (!empty($subSkillList)) {
    $skillFromLastSkill = $skillRepo->find(end($subSkillList));
    if ($skillFromLastSkill) {
        $profile = $skillFromLastSkill->getLevelProfile();
    }
}

if (!$profile) {
    $skillRelSkill = new SkillRelSkillModel();
    $parents = $skillRelSkill->getSkillParents($skillId);
    krsort($parents);
    foreach ($parents as $parent) {
        $skillParentId = $parent['skill_id'];
        $profile = $skillRepo->find($skillParentId)->getLevelProfile();

        if ($profile) {
            break;
        }

        if (!$profile && 0 == $parent['parent_id']) {
            $profile = $skillLevelRepo->findAll();
            $profile = isset($profile[0]) ? $profile[0] : false;
        }
    }
}

if ($profile) {
    $profileId = $profile->getId();
    $levels = $skillLevelRepo->findBy([
        'profile' => $profileId,
    ]);
    $profileLevels = [];
    foreach ($levels as $level) {
        $profileLevels[$level->getPosition()][$level->getId()] = $level->getTitle();
    }

    ksort($profileLevels); // Sort the array by Position.

    foreach ($profileLevels as $profileLevel) {
        $profileId = key($profileLevel);
        $acquiredLevel[$profileId] = $profileLevel[$profileId];
    }
}

$formDefaultValues = ['skill' => $skillId];
$newSubSkillList = [];
$disableList = [];

$currentUrl = api_get_self().'?user='.$userId.'&current='.$currentLevel;

$form = new FormValidator('assign_skill', 'POST', $currentUrl);
$form->addHeader(get_lang('Assign skill'));
$form->addText('user_name', '', false);

$levelName = get_lang('Skill');
if (!empty($skillLevels)) {
    if (isset($skillLevels['levels'][1])) {
        $levelName = get_lang($skillLevels['levels'][1]);
    }
}

$form->addSelect('skill', $levelName, $skillsOptions, ['id' => 'skill']);

if (!empty($skillIdFromGet)) {
    if (empty($subSkillList)) {
        $subSkillList[] = $skillIdFromGet;
    }
    $oldSkill = $skillRepo->find($skillIdFromGet);
    $counter = 0;
    foreach ($subSkillList as $subSkillId) {
        $children = $skillManager->getChildren($subSkillId);

        if (isset($subSkillList[$counter - 1]) && isset($subSkillList[$counter])) {
            $oldSkill = $skillRepo->find($subSkillList[$counter]);
        }
        $skillsOptions = [];
        if ($oldSkill) {
            $skillsOptions = [$oldSkill->getId() => ' -- '.$oldSkill->getTitle()];
        }

        if ($counter < count($subSkillList) - 1) {
            $disableList[] = 'sub_skill_id_'.($counter + 1);
        }

        foreach ($children as $child) {
            $skillsOptions[$child['id']] = $child['data']['title'];
        }

        $levelName = get_lang('Sub-skill');
        if (!empty($skillLevels)) {
            if (isset($skillLevels['levels'][$counter + 2])) {
                $levelName = get_lang($skillLevels['levels'][$counter + 2]);
            }
        }

        if ('true' === $autoloadSubskills) {
            $form->addSelect(
                'sub_skill_id_'.($counter + 1),
                $levelName,
                $skillsOptions,
                [
                    'id' => 'sub_skill_id_'.($counter + 1),
                    'class' => 'sub_skill ',
                ]
            );
        }

        if (isset($subSkillList[$counter + 1])) {
            $nextSkill = $skillRepo->find($subSkillList[$counter + 1]);
            if ($nextSkill) {
                $formDefaultValues['sub_skill_id_'.($counter + 1)] = $nextSkill->getId();
            }
        }
        $newSubSkillList[] = $subSkillId;
        $counter++;
    }
    $subSkillList = $newSubSkillList;
}

$subSkillListToString = implode(',', $subSkillList);

$currentUrl = api_get_self().'?user='.$userId.'&current='.$currentLevel.'&sub_skill_list='.$subSkillListToString;

$form->addHidden('sub_skill_list', $subSkillListToString);
$form->addHidden('user', $user->getId());
$form->addHidden('id', $skillId);
$form->addRule('skill', get_lang('Required field'), 'required');

$showLevels = ('false' === api_get_setting('skill.hide_skill_levels'));

if ($showLevels) {
    $form->addSelect('acquired_level', get_lang('Level acquired'), $acquiredLevel);
    //$form->addRule('acquired_level', get_lang('Required field'), 'required');
}

$form->addTextarea('argumentation', get_lang('Argumentation'), ['rows' => 6]);
$form->addRule('argumentation', get_lang('Required field'), 'required');
$form->addRule(
    'argumentation',
    sprintf(get_lang('This text should be at least %s characters long'), 10),
    'mintext',
    10
);
$form->applyFilter('argumentation', 'trim');
$form->addHtml('<div class="flex space-x-4">');
$form->addButton('save', get_lang('Save'), 'check', 'primary');
$form->addButton('save_and_add_more', get_lang('Save and add new item'), 'check', 'secondary');
$form->addHtml('</div>');
$form->setDefaults($formDefaultValues);

if ($form->validate()) {
    $values = $form->exportValues();
    $skillToProcess = $values['id'];
    if (!empty($subSkillList)) {
        $counter = 1;
        foreach ($subSkillList as $subSkill) {
            if (isset($values["sub_skill_id_$counter"])) {
                $skillToProcess = $values["sub_skill_id_$counter"];
            }
            $counter++;
        }
    }
    $skill = $skillRepo->find($skillToProcess);

    if (!$skill) {
        Display::addFlash(
            Display::return_message(get_lang('Skill not found'), 'error')
        );

        header('Location: '.api_get_self().'?'.$currentUrl);
        exit;
    }

    if ($user->hasSkill($skill)) {
        $_SESSION['flash_message'] = [
            'type' => 'warning',
            'message' => sprintf(
                get_lang('The user %s has already achieved the skill %s'),
                UserManager::formatUserFullName($user),
                $skill->getTitle()
            )
        ];

        header('Location: '.$currentUrl);
        exit;
    }

    $skillUser = $skillManager->addSkillToUserBadge(
        $user,
        $skill,
        isset($values['acquired_level']) ? (int) $values['acquired_level'] : 0,
        $values['argumentation'],
        api_get_user_id()
    );

    // Send email depending on children_auto_threshold
    $skillRelSkill = new SkillRelSkillModel();
    $skillModel = new SkillModel();
    $parents = $skillModel->getDirectParents($skillToProcess);

    $extraFieldValue = new ExtraFieldValue('skill');
    foreach ($parents as $parentInfo) {
        $parentId = $parentInfo['skill_id'];
        $parentData = $skillModel->get($parentId);

        $data = $extraFieldValue->get_values_by_handler_and_field_variable($parentId, 'children_auto_threshold');
        // Search X children
        $requiredSkills = isset($data['value']) ? (int) $data['value'] : 0;
        if ($requiredSkills > 0) {
            $children = $skillRelSkill->getChildren($parentId);
            $counter = 0;
            foreach ($children as $child) {
                if ($skillModel->userHasSkill($userId, $child['id'])) {
                    $counter++;
                }
            }

            if ($counter >= $requiredSkills) {
                $bossList = UserManager::getStudentBossList($userId);
                if (!empty($bossList)) {
                    Display::addFlash(Display::return_message(get_lang('Message Sent')));
                    $url = api_get_path(WEB_CODE_PATH).'skills/assign.php?user='.$userId.'&id='.$parentId;
                    $link = Display::url($url, $url);
                    $userFullName = UserManager::formatUserFullName($user);

                    foreach ($bossList as $boss) {
                        $bossInfo = api_get_user_info($boss['boss_id']);
                        $subject = get_lang('A student has obtained the number of sub-skills needed to validate the mother skill.', $bossInfo['locale']);
                        $message = sprintf(
                            get_lang('Learner %s has enough sub-skill to get skill %s. To assign this skill it is possible to go here : %s', $bossInfo['locale']),
                            $userFullName,
                            $parentData['title'],
                            $link
                        );
                        MessageManager::send_message_simple(
                            $boss['boss_id'],
                            $subject,
                            $message
                        );
                    }
                }
                break;
            }
        }
    }

    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => sprintf(
            get_lang('The skill %s has been assigned to user %s'),
            $skill->getTitle(),
            UserManager::formatUserFullName($user)
        )
    ];

    if (isset($_POST['save_and_add_more'])) {
        header('Location: '.api_get_path(WEB_CODE_PATH)."skills/assign.php?user={$userId}");
    } else {
        $secToken = Security::get_token();
        header('Location: '.api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.$userId.'&sec_token='.$secToken);
    }
    exit;
}

$form->setDefaults(['user_name' => get_lang('Username').': '.UserManager::formatUserFullName($user, true)]);
$form->freeze(['user_name']);

if (api_is_drh()) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'my_space/index.php',
        "name" => get_lang('Reporting'),
    ];
    if (COURSEMANAGER == $user->getStatus()) {
        $interbreadcrumb[] = [
            "url" => api_get_path(WEB_CODE_PATH).'my_space/teachers.php',
            'name' => get_lang('Trainers'),
        ];
    } else {
        $interbreadcrumb[] = [
            "url" => api_get_path(WEB_CODE_PATH).'my_space/student.php',
            'name' => get_lang('My learners'),
        ];
    }
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'my_space/myStudents.php?student='.$userId,
        'name' => UserManager::formatUserFullName($user),
    ];
} else {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
        'name' => get_lang('Administration'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'admin/user_list.php',
        'name' => get_lang('User list'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.$userId,
        'name' => UserManager::formatUserFullName($user),
    ];
}

$url = api_get_path(WEB_CODE_PATH).'skills/assign.php?user='.$userId;

$disableSelect = '';
if ($disableList) {
    foreach ($disableList as $name) {
        //$disableSelect .= "$('#".$name."').prop('disabled', true);";
        //$disableSelect .= "$('#".$name."').selectpicker('refresh');";
    }
}

$htmlHeadXtra[] = '<script>
$(function() {
    $("#skill").on("change", function() {
        $(location).attr("href", "'.$url.'&id="+$(this).val());
    });
    $(".sub_skill").on("change", function() {
        $(location).attr("href", "'.$url.'&id='.$skillIdFromGet.'&current_value="+$(this).val()+"&current="+$(this).attr("id")+"&sub_skill_list='.$subSkillListToString.',"+$(this).val());
    });
    '.$disableSelect.'
});
</script>';

$flashMessage = '';
if (isset($_SESSION['flash_message'])) {
    $messageType = isset($_SESSION['flash_message']['type']) ? $_SESSION['flash_message']['type'] : 'warning';
    $messageText = isset($_SESSION['flash_message']['message']) ? $_SESSION['flash_message']['message'] : '';

    $flashMessage = Display::return_message($messageText, $messageType);
    unset($_SESSION['flash_message']);
}

$template = new Template(get_lang('Add skill'));
$template->assign('content', $flashMessage.$form->returnForm());
$template->display_one_col_template();
