<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;

/**
 * Class SurveyManager.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University:
 * cleanup, refactoring and rewriting large parts (if not all) of the code
 * @author Julio Montoya <gugli100@gmail.com>, Personality Test modification
 * and rewriting large parts of the code
 * @author cfasanando
 *
 * @todo move this file to inc/lib
 * @todo use consistent naming for the functions (save vs store for instance)
 */
class SurveyManager
{
    /**
     * @param $code
     *
     * @return string
     */
    public static function generate_unique_code(string $code, ?int $surveyId = null)
    {
        if (empty($code)) {
            return false;
        }
        $course_id = api_get_course_int_id();
        $table = Database::get_course_table(TABLE_SURVEY);
        $code = Database::escape_string($code);
        $num = 0;
        $new_code = $code;
        while (true) {

            if (isset($surveyId)) {
                $sql = "SELECT * FROM $table
                    WHERE code = '$new_code' AND iid = $surveyId";
            } else {
                $sql = "SELECT * FROM $table
                    WHERE code = '$new_code'";
            }

            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                $num++;
                $new_code = $code.$num;
            } else {
                break;
            }
        }

        return $code.$num;
    }

    /**
     * Deletes all survey invitations of a user.
     *
     * @param int $user_id
     *
     * @return bool
     * @assert ('') === false
     */
    public static function delete_all_survey_invitations_by_user($user_id)
    {
        $user_id = (int) $user_id;
        if (empty($user_id)) {
            return false;
        }
        $table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
        $table_survey = Database::get_course_table(TABLE_SURVEY);

        $sql = "SELECT iid, survey_id
                FROM $table_survey_invitation WHERE user_id = '$user_id' AND c_id <> 0 ";
        $result = Database::query($sql);
        while ($row = Database::fetch_assoc($result)) {
            $survey_invitation_id = $row['iid'];
            $surveyId = $row['survey_id'];
            $sql2 = "DELETE FROM $table_survey_invitation
                     WHERE iid = '$survey_invitation_id' AND c_id <> 0";
            if (Database::query($sql2)) {
                $sql3 = "UPDATE $table_survey SET
                            invited = invited - 1
                         WHERE  iid = $surveyId";
                Database::query($sql3);
            }
        }
    }

    /**
     * @param string $course_code
     * @param int    $session_id
     *
     * @return array
     * @assert ('') === false
     */
    public static function get_surveys($courseCode, int $sessionId = 0)
    {
        $courseId = api_get_course_int_id();
        $repo = Container::getSurveyRepository();
        $course = api_get_course_entity($courseId);
        $session = api_get_session_entity($sessionId);

        $qb = $repo->getResourcesByCourse($course, $session, null, null, true, true);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Retrieves all the survey information.
     *
     * @param int $survey_id the id of the survey
     * @param int $shared    this parameter determines if
     *                       we have to get the information of a survey from the central (shared) database or from the
     *                       course database
     * @param string course code optional
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     * @assert ('') === false
     *
     * @return array
     *
     * @todo this is the same function as in create_new_survey.php
     */
    public static function get_survey(
        $survey_id,
        $shared = 0,
        $course_code = '',
        $simple_return = false
    ) {
        $courseInfo = api_get_course_info();
        $survey_id = (int) $survey_id;
        $table_survey = Database::get_course_table(TABLE_SURVEY);

        if (empty($courseInfo)) {
            return [];
        }
        $sql = "SELECT * FROM $table_survey
                WHERE iid = $survey_id";

        $result = Database::query($sql);
        $return = [];

        if (Database::num_rows($result) > 0) {
            $return = Database::fetch_assoc($result);
            if ($simple_return) {
                return $return;
            }
            // We do this (temporarily) to have the array match the quickform elements immediately
            // idealiter the fields in the db match the quickform fields
            $return['survey_code'] = $return['code'];
            $return['survey_title'] = $return['title'];
            $return['survey_subtitle'] = $return['subtitle'];
            $return['survey_language'] = $return['lang'];
            $return['start_date'] = $return['avail_from'];
            $return['end_date'] = $return['avail_till'];
            $return['survey_share'] = $return['is_shared'];
            $return['survey_introduction'] = $return['intro'];
            $return['survey_thanks'] = $return['surveythanks'];
            $return['survey_type'] = $return['survey_type'];
            $return['one_question_per_page'] = $return['one_question_per_page'];
            $return['show_form_profile'] = $return['show_form_profile'];
            $return['input_name_list'] = isset($return['input_name_list']) ? $return['input_name_list'] : null;
            $return['shuffle'] = $return['shuffle'];
            $return['parent_id'] = $return['parent_id'];
            $return['survey_version'] = $return['survey_version'];
            $return['anonymous'] = $return['anonymous'];
            $return['c_id'] = isset($return['c_id']) ? $return['c_id'] : 0;
            $return['session_id'] = isset($return['session_id']) ? $return['session_id'] : 0;
        }

        return $return;
    }

    /**
     * @param string $code
     *
     * @return string
     */
    public static function generateSurveyCode($code)
    {
        return strtolower(CourseManager::generate_course_code($code));
    }

    /**
     * This function stores a survey in the database.
     *
     * @param array $values
     *
     * @return array $return the type of return message that has to be displayed and the message in it
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     */
    public static function store_survey($values)
    {
        $session_id = api_get_session_id();
        $courseCode = api_get_course_id();
        $table_survey = Database::get_course_table(TABLE_SURVEY);
        $shared_survey_id = 0;
        $displayQuestionNumber = isset($values['display_question_number']);
        $repo = Container::getSurveyRepository();

        if (!isset($values['survey_id'])) {
            // Check if the code doesn't soon exists in this language
            $sql = 'SELECT 1 FROM '.$table_survey.'
			        WHERE
			            code = "'.Database::escape_string($values['survey_code']).'" AND
			            lang = "'.Database::escape_string($values['survey_language']).'"';
            $rs = Database::query($sql);
            if (Database::num_rows($rs) > 0) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('This survey code soon exists in this language'),
                        'error'
                    )
                );
                $return['type'] = 'error';
                $return['id'] = isset($values['survey_id']) ? $values['survey_id'] : 0;

                return $return;
            }

            if (!isset($values['anonymous'])) {
                $values['anonymous'] = 0;
            }

            $values['anonymous'] = (int) $values['anonymous'];

            $survey = new CSurvey();
            $extraParams = [];
            if (0 == $values['anonymous']) {
                // Input_name_list
                $values['show_form_profile'] = isset($values['show_form_profile']) ? $values['show_form_profile'] : 0;
                $survey->setShowFormProfile($values['show_form_profile']);

                if (1 == $values['show_form_profile']) {
                    // Input_name_list
                    $fields = explode(',', $values['input_name_list']);
                    $field_values = '';
                    foreach ($fields as &$field) {
                        if ('' != $field) {
                            if ('' == $values[$field]) {
                                $values[$field] = 0;
                            }
                            $field_values .= $field.':'.$values[$field].'@';
                        }
                    }
                    $extraParams['form_fields'] = $field_values;
                } else {
                    $extraParams['form_fields'] = '';
                }
                $survey->setFormFields($extraParams['form_fields']);
            }

            $extraParams['one_question_per_page'] = isset($values['one_question_per_page']) ? $values['one_question_per_page'] : 0;
            $extraParams['shuffle'] = isset($values['shuffle']) ? $values['shuffle'] : 0;

            if (1 == $values['survey_type']) {
                $survey
                    ->setSurveyType(1)
                    ->setShuffle($values['shuffle'])
                    ->setOneQuestionPerPage($values['one_question_per_page'])
                ;
                // Logic for versioning surveys
                if (!empty($values['parent_id'])) {
                    $parentId = (int) $values['parent_id'];
                    $sql = 'SELECT survey_version
                            FROM '.$table_survey.'
					        WHERE
					            parent_id = '.$parentId.'
                            ORDER BY survey_version DESC
                            LIMIT 1';
                    $rs = Database::query($sql);
                    if (0 === Database::num_rows($rs)) {
                        $sql = 'SELECT survey_version FROM '.$table_survey.'
						        WHERE
						            iid = '.$parentId;
                        $rs = Database::query($sql);
                        $getversion = Database::fetch_assoc($rs);
                        if (empty($getversion['survey_version'])) {
                            $versionValue = ++$getversion['survey_version'];
                        } else {
                            $versionValue = $getversion['survey_version'];
                        }
                    } else {
                        $row = Database::fetch_assoc($rs);
                        $pos = api_strpos($row['survey_version'], '.');
                        if (false === $pos) {
                            $row['survey_version'] = $row['survey_version'] + 1;
                            $versionValue = $row['survey_version'];
                        } else {
                            $getlast = explode('\.', $row['survey_version']);
                            $lastversion = array_pop($getlast);
                            $lastversion = $lastversion + 1;
                            $add = implode('.', $getlast);
                            if ('' != $add) {
                                $insertnewversion = $add.'.'.$lastversion;
                            } else {
                                $insertnewversion = $lastversion;
                            }
                            $versionValue = $insertnewversion;
                        }
                    }
                    $survey->setSurveyVersion($versionValue);
                }
            }

            $course = api_get_course_entity();
            $session = api_get_session_entity();

            $survey
                ->setCode(self::generateSurveyCode($values['survey_code']))
                ->setTitle($values['survey_title'])
                ->setSubtitle($values['survey_title'])
                ->setLang($values['survey_language'])
                ->setAvailFrom(new \DateTime($values['start_date']))
                ->setAvailTill(new \DateTime($values['end_date']))
                ->setIsShared($shared_survey_id)
                ->setTemplate('template')
                ->setIntro($values['survey_introduction'])
                ->setSurveyThanks($values['survey_thanks'])
                ->setDisplayQuestionNumber($displayQuestionNumber)
                ->setAnonymous((string) $values['anonymous'])
                ->setVisibleResults((int) $values['visible_results'])
                ->setSurveyType((int) ($values['survey_type'] ?? 1))
                ->setParent($course)
                ->addCourseLink($course, $session)
            ;

            if (isset($values['parent_id']) && !empty($values['parent_id'])) {
                $parent = $repo->find($values['parent_id']);
                $survey->setSurveyParent($parent);
            }

            $repo->create($survey);

            $survey_id = $survey->getIid();
            if ($survey_id > 0) {
                Event::addEvent(
                    LOG_SURVEY_CREATED,
                    LOG_SURVEY_ID,
                    $survey_id,
                    null,
                    api_get_user_id(),
                    api_get_course_int_id(),
                    api_get_session_id()
                );
            }

            if (1 == $values['survey_type'] && !empty($values['parent_id'])) {
                self::copySurvey($values['parent_id'], $survey_id);
            }

            Display::addFlash(
                Display::return_message(
                    get_lang('The survey has been created succesfully'),
                    'success'
                )
            );
            $return['id'] = $survey_id;
        } else {
            // Check whether the code doesn't soon exists in this language
            $sql = 'SELECT 1 FROM '.$table_survey.'
			        WHERE
			            code = "'.Database::escape_string($values['survey_code']).'" AND
			            lang = "'.Database::escape_string($values['survey_language']).'" AND
			            iid !='.intval($values['survey_id']);
            $rs = Database::query($sql);
            if (Database::num_rows($rs) > 0) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('This survey code soon exists in this language'),
                        'error'
                    )
                );
                $return['type'] = 'error';
                $return['id'] = isset($values['survey_id']) ? $values['survey_id'] : 0;

                return $return;
            }

            if (!isset($values['anonymous'])
                || (isset($values['anonymous']) && '' == $values['anonymous'])
            ) {
                $values['anonymous'] = 0;
            }

            /** @var CSurvey $survey */
            $survey = $repo->find($values['survey_id']);

            $survey->setOneQuestionPerPage(isset($values['one_question_per_page']) ? $values['one_question_per_page'] : 0);
            $survey->setShuffle(isset($values['shuffle']) ? $values['shuffle'] : 0);

            if (0 == $values['anonymous']) {
                $survey->setShowFormProfile(isset($values['show_form_profile']) ? $values['show_form_profile'] : 0);
                $isFormProfile = isset($values['show_form_profile']) ? $values['show_form_profile'] : 0;
                if (1 == $isFormProfile) {
                    $fields = explode(',', $values['input_name_list']);
                    $field_values = '';
                    foreach ($fields as &$field) {
                        if ('' != $field) {
                            if (!isset($values[$field]) ||
                                (isset($values[$field]) && '' == $values[$field])
                            ) {
                                $values[$field] = 0;
                            }
                            $field_values .= $field.':'.$values[$field].'@';
                        }
                    }
                    $survey->setFormFields($field_values);
                } else {
                    $survey->setFormFields('');
                }
            } else {
                $survey->setFormFields('');
                $survey->setShowFormProfile(0);
            }

            $survey
                ->setTitle($values['survey_title'])
                ->setSubtitle($values['survey_title'])
                ->setLang($values['survey_language'])
                ->setAvailFrom(new \DateTime($values['start_date']))
                ->setAvailTill(new \DateTime($values['end_date']))
                ->setIsShared($shared_survey_id)
                ->setTemplate('template')
                ->setIntro($values['survey_introduction'])
                ->setSurveyThanks($values['survey_thanks'])
                ->setAnonymous((string) $values['anonymous'])
                ->setVisibleResults((int) $values['visible_results'])
                ->setDisplayQuestionNumber($displayQuestionNumber)
            ;

            $repo->update($survey);
            /*
            // Update into item_property (update)
            api_item_property_update(
                api_get_course_info(),
                TOOL_SURVEY,
                $values['survey_id'],
                'SurveyUpdated',
                api_get_user_id()
            );*/

            Display::addFlash(
                Display::return_message(
                    get_lang('The survey has been updated succesfully'),
                    'confirmation'
                )
            );

            $return['id'] = $values['survey_id'];
        }

        $survey_id = (int) $return['id'];

        // Gradebook
        $gradebook_option = false;
        if (isset($values['survey_qualify_gradebook'])) {
            $gradebook_option = $values['survey_qualify_gradebook'] > 0;
        }

        $gradebook_link_type = 8;
        $link_info = GradebookUtils::isResourceInCourseGradebook(
            api_get_course_int_id(),
            $gradebook_link_type,
            $survey_id,
            $session_id
        );

        $gradebook_link_id = isset($link_info['id']) ? $link_info['id'] : false;

        if ($gradebook_option) {
            if ($survey_id > 0) {
                $title_gradebook = ''; // Not needed here.
                $description_gradebook = ''; // Not needed here.
                $survey_weight = floatval($_POST['survey_weight']);
                $max_score = 1;

                if (!$gradebook_link_id && isset($values['category_id'])) {
                    GradebookUtils::add_resource_to_course_gradebook(
                        $values['category_id'],
                        api_get_course_int_id(),
                        $gradebook_link_type,
                        $survey_id,
                        $title_gradebook,
                        $survey_weight,
                        $max_score,
                        $description_gradebook,
                        1,
                        $session_id
                    );
                } else {
                    GradebookUtils::updateResourceFromCourseGradebook(
                        $gradebook_link_id,
                        api_get_course_int_id(),
                        $survey_weight
                    );
                }
            }
        } else {
            // Delete everything of the gradebook for this $linkId
            GradebookUtils::remove_resource_from_course_gradebook($gradebook_link_id);
        }

        return $return;
    }

    public static function deleteSurvey(CSurvey $survey)
    {
        $repo = Container::getSurveyRepository();
        $surveyId = $survey->getIid();
        $repo->delete($survey);

        $table_survey = Database::get_course_table(TABLE_SURVEY);
        $table_survey_question_group = Database::get_course_table(TABLE_SURVEY_QUESTION_GROUP);

        Event::addEvent(
            LOG_SURVEY_DELETED,
            LOG_SURVEY_ID,
            $surveyId,
            null,
            api_get_user_id(),
            api_get_course_int_id(),
            api_get_session_id()
        );

        /*// Deleting groups of this survey
        $sql = "DELETE FROM $table_survey_question_group
                WHERE c_id = $course_id AND iid='".$survey_id."'";
        Database::query($sql);*/

        // Deleting the questions of the survey
        self::delete_all_survey_questions($surveyId, false);

        // Update into item_property (delete)
        /*api_item_property_update(
            $course_info,
            TOOL_SURVEY,
            $survey_id,
            'SurveyDeleted',
            api_get_user_id()
        );*/

        SkillModel::deleteSkillsFromItem($surveyId, ITEM_TYPE_SURVEY);

        return true;
    }

    /**
     * Copy given survey to a new (optional) given survey ID.
     */
    public static function copySurvey(
        int $surveyId,
        ?int $newSurveyId = null,
        ?int $targetCourseId = null,
        ?int $targetSessionId = null
    ): ?int {
        $originalCourseId = api_get_course_int_id();
        if (!$targetCourseId) {
            $targetCourseId = $originalCourseId;
        }

        $repo = Container::getSurveyRepository();

        $surveyTable = Database::get_course_table(TABLE_SURVEY);
        $surveyQuestionTable = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $surveyOptionsTable = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);

        $surveyData = self::get_survey($surveyId, 0, null, true);
        if (empty($surveyData)) {
            return null;
        }

        if (empty($newSurveyId)) {
            $surveyData['code'] = self::generate_unique_code($surveyData['code']);
            $surveyData['session_id'] = $targetSessionId ?? api_get_session_id();

            if ($targetCourseId === $originalCourseId) {
                $surveyData['title'] .= ' '.get_lang('Copy');
            }

            unset($surveyData['iid'], $surveyData['id']);

            if ($targetSessionId) {
                $newSurveyId = Database::insert($surveyTable, $surveyData);
            } else {
                $course = api_get_course_entity();
                $session = api_get_session_entity();

                $survey = new CSurvey();
                $survey
                    ->setSurveyType($surveyData['survey_type'])
                    ->setSurveyVersion($surveyData['survey_version'])
                    ->setCode($surveyData['code'])
                    ->setTitle($surveyData['title'])
                    ->setSubtitle($surveyData['subtitle'])
                    ->setLang($surveyData['lang'])
                    ->setAvailFrom(new \DateTime($surveyData['avail_from']))
                    ->setAvailTill(new \DateTime($surveyData['avail_till']))
                    ->setIsShared($surveyData['is_shared'])
                    ->setTemplate($surveyData['template'])
                    ->setIntro($surveyData['intro'])
                    ->setSurveyThanks($surveyData['surveythanks'])
                    ->setAnonymous($surveyData['anonymous'])
                    ->setVisibleResults($surveyData['visible_results'])
                    ->setShuffle($surveyData['shuffle'])
                    ->setOneQuestionPerPage($surveyData['one_question_per_page'])
                    ->setShowFormProfile($surveyData['show_form_profile'])
                    ->setFormFields($surveyData['form_fields'])
                    ->setParent($course)
                    ->addCourseLink($course, $session);

                if (!empty($surveyData['parent_id'])) {
                    $parent = $repo->find($surveyData['parent_id']);
                    if ($parent) {
                        $survey->setSurveyParent($parent);
                    }
                }

                $repo->create($survey);
                $newSurveyId = $survey->getIid();
            }
        }

        if (empty($newSurveyId)) {
            return null;
        }

        $sql = "SELECT * FROM $surveyQuestionTable WHERE survey_id = $surveyId";
        $res = Database::query($sql);
        $question_id = [];
        while ($row = Database::fetch_assoc($res)) {
            $params = [
                'survey_id' => $newSurveyId,
                'survey_question' => $row['survey_question'],
                'survey_question_comment' => $row['survey_question_comment'],
                'type' => $row['type'],
                'display' => $row['display'],
                'sort' => $row['sort'],
                'shared_question_id' => $row['shared_question_id'],
                'max_value' => $row['max_value'],
                'survey_group_pri' => $row['survey_group_pri'],
                'survey_group_sec1' => $row['survey_group_sec1'],
                'survey_group_sec2' => $row['survey_group_sec2'],
            ];

            if (isset($row['is_required'])) {
                $params['is_required'] = $row['is_required'];
            }

            $insertId = Database::insert($surveyQuestionTable, $params);
            if ($insertId) {
                $question_id[$row['iid']] = $insertId;
            }
        }

        $sql = "SELECT * FROM $surveyOptionsTable WHERE survey_id = $surveyId";
        $res = Database::query($sql);
        while ($row = Database::fetch_assoc($res)) {
            $params = [
                'question_id' => $question_id[$row['question_id']] ?? 0,
                'survey_id' => $newSurveyId,
                'option_text' => $row['option_text'],
                'sort' => $row['sort'],
                'value' => $row['value'],
            ];
            Database::insert($surveyOptionsTable, $params);
        }

        return $newSurveyId;
    }

    /**
     * This function duplicates a survey (and also all the question in that survey.
     *
     * @param int $surveyId id of the survey that has to be duplicated
     * @param int $courseId id of the course which survey has to be duplicated
     *
     * @return true
     *
     * @author Eric Marguin <e.marguin@elixir-interactive.com>, Elixir Interactive
     *
     * @version October 2007
     */
    public static function empty_survey($surveyId, $courseId = 0)
    {
        // Database table definitions
        $table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
        $table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);
        $table_survey = Database::get_course_table(TABLE_SURVEY);

        $courseId = (int) $courseId;
        $courseId = empty($courseId) ? api_get_course_int_id() : $courseId;
        $surveyId = (int) $surveyId;

        $datas = self::get_survey($surveyId);
        $session_where = '';
        if (0 != api_get_session_id()) {
            $session_where = ' AND session_id = "'.api_get_session_id().'" ';
        }

        $sql = 'DELETE FROM '.$table_survey_invitation.'
		        WHERE
		            survey_id = "'.$surveyId.'" '.$session_where.' ';
        Database::query($sql);

        $sql = 'DELETE FROM '.$table_survey_answer.'
		        WHERE
		        survey_id = "'.$surveyId.'" '.$session_where.' ';
        Database::query($sql);

        $sql = 'UPDATE '.$table_survey.' SET invited=0, answered=0
		        WHERE iid ='.$surveyId;
        Database::query($sql);

        Event::addEvent(
            LOG_SURVEY_CLEAN_RESULTS,
            LOG_SURVEY_ID,
            $surveyId,
            null,
            api_get_user_id(),
            api_get_course_int_id(),
            api_get_session_id()
        );

        return true;
    }

    /**
     * Updates c_survey.answered: number of people who have taken the survey (=filled at least one question).
     */
    public static function updateSurveyAnswered(CSurvey $survey, $user, $lpItemId = 0): void
    {
        $em = Database::getManager();
        $surveyId = $survey->getIid();
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $survey->setAnswered($survey->getAnswered() + 1);
        $em->persist($survey);
        $em->flush();

        $lpItemCondition = '';
        if (!empty($lpItemId)) {
            $lpItemCondition = " AND c_lp_item_id = $lpItemId";
        }
        $sessionCondition = '';
        if (true === api_get_configuration_value('show_surveys_base_in_sessions')) {
            $sessionCondition = api_get_session_condition($sessionId);
        }

        $table = Database::get_course_table(TABLE_SURVEY_INVITATION);
        // Storing that the user has finished the survey.
        $sql = "UPDATE $table
                SET
                    answered_at = '".api_get_utc_datetime()."',
                    answered = 1
                WHERE
                    session_id = $sessionId AND
                    user_id ='".Database::escape_string($user)."' AND
                    survey_id ='".$surveyId."'
                    $sessionCondition
                    $lpItemCondition";
        Database::query($sql);
    }

    /**
     * This function return the "icon" of the question type.
     *
     * @param string $type
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     */
    public static function icon_question($type)
    {
        // the possible question types
        $possible_types = [
            'personality',
            'yesno',
            'multiplechoice',
            'multipleresponse',
            'open',
            'dropdown',
            'comment',
            'pagebreak',
            'percentage',
            'score',
        ];

        // the images array
        $icon_question = [
            'yesno' => 'thumbs-up-down',
            'personality' => 'thumbs-up-down',
            'multiplechoice' => 'format-list-bulleted',
            'multipleresponse' => 'format-list-bulleted-square',
            'open' => 'form-textarea',
            'dropdown' => 'form-dropdown',
            'percentage' => 'percent-box-outline',
            'score' => 'format-annotation-plus',
            'comment' => 'format-align-top',
            'pagebreak' => 'format-page-break',
        ];

        if (in_array($type, $possible_types)) {
            return $icon_question[$type];
        }

        return false;
    }

    /**
     * This function retrieves all the information of a question.
     *
     * @param int  $question_id the id of the question
     * @param bool $shared
     *
     * @return array
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007
     *
     * @todo one sql call should do the trick
     */
    public static function get_question($question_id)
    {
        $tbl_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $course_id = api_get_course_int_id();
        $question_id = (int) $question_id;

        if (empty($question_id)) {
            return [];
        }

        $sql = "SELECT * FROM $tbl_survey_question
                WHERE iid = $question_id
                ORDER BY `sort` ";

        $sqlOption = "  SELECT * FROM $table_survey_question_option
                        WHERE question_id='".$question_id."'
                        ORDER BY `sort` ";
        // Getting the information of the question
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        $return['survey_id'] = $row['survey_id'];
        $return['parent_id'] = isset($row['parent_id']) ? $row['parent_id'] : 0;
        $return['parent_option_id'] = isset($row['parent_option_id']) ? $row['parent_option_id'] : 0;
        $return['question_id'] = $row['iid'];
        $return['type'] = $row['type'];
        $return['question'] = $row['survey_question'];
        $return['horizontalvertical'] = $row['display'];
        $return['shared_question_id'] = $row['shared_question_id'];
        $return['maximum_score'] = $row['max_value'];
        $return['is_required'] = $row['is_required'];

        if (0 != $row['survey_group_pri']) {
            $return['assigned'] = $row['survey_group_pri'];
            $return['choose'] = 1;
        } else {
            $return['assigned1'] = $row['survey_group_sec1'];
            $return['assigned2'] = $row['survey_group_sec2'];
            $return['choose'] = 2;
        }

        // Getting the information of the question options
        $result = Database::query($sqlOption);
        $counter = 0;
        while ($row = Database::fetch_assoc($result)) {
            /** @todo this should be renamed to options instead of answers */
            $return['answers'][] = $row['option_text'];
            $return['values'][] = $row['value'];
            $return['answer_data'][$counter]['data'] = $row['option_text'];
            $return['answer_data'][$counter]['iid'] = $row['iid'];
            /** @todo this can be done more elegantly (used in reporting) */
            $return['answersid'][] = $row['iid'];
            $counter++;
        }

        return $return;
    }

    /**
     * This function gets all the question of any given survey.
     *
     * @param int $surveyId the id of the survey
     * @param int $courseId
     *
     * @return array containing all the questions of the survey
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     *
     * @todo one sql call should do the trick
     */
    public static function get_questions($surveyId, $courseId = 0)
    {
        $tbl_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);

        $courseId = (int) $courseId;
        $surveyId = (int) $surveyId;

        // Getting the information of the question
        $sql = "SELECT * FROM $tbl_survey_question
		        WHERE survey_id= $surveyId ";
        $result = Database::query($sql);
        $questions = [];
        while ($row = Database::fetch_assoc($result)) {
            $questionId = $row['iid'];
            $questions[$questionId]['survey_id'] = $surveyId;
            $questions[$questionId]['question_id'] = $questionId;
            $questions[$questionId]['type'] = $row['type'];
            $questions[$questionId]['question'] = $row['survey_question'];
            $questions[$questionId]['horizontalvertical'] = $row['display'];
            $questions[$questionId]['maximum_score'] = $row['max_value'];
            $questions[$questionId]['sort'] = $row['sort'];
            $questions[$questionId]['survey_question_comment'] = $row['survey_question_comment'];

            // Getting the information of the question options
            $sql = "SELECT * FROM $table_survey_question_option
		             WHERE survey_id= $surveyId  AND question_id = $questionId";
            $resultOptions = Database::query($sql);
            while ($rowOption = Database::fetch_assoc($resultOptions)) {
                $questions[$questionId]['answers'][] = $rowOption['option_text'];
            }
        }

        return $questions;
    }

    public static function saveQuestion(CSurvey $survey, array $form_content, bool $showMessage = true, array $dataFromDatabase = [])
    {
        $surveyId = $survey->getIid();

        $error = false;
        $message = '';
        if (strlen($form_content['question']) > 1) {
            // Checks length of the question
            $empty_answer = false;
            if (1 == $survey->getSurveyType()) {
                if (empty($form_content['choose'])) {
                    return 'PleaseChooseACondition';
                }

                if ((2 == $form_content['choose']) &&
                    ($form_content['assigned1'] == $form_content['assigned2'])
                ) {
                    return 'ChooseDifferentCategories';
                }
            }

            if ('percentage' !== $form_content['type']) {
                if (isset($form_content['answers'])) {
                    for ($i = 0; $i < count($form_content['answers']); $i++) {
                        if (strlen($form_content['answers'][$i]) < 1) {
                            $empty_answer = true;
                            break;
                        }
                    }
                }
            }

            if ('score' === $form_content['type']) {
                if (strlen($form_content['maximum_score']) < 1) {
                    $empty_answer = true;
                }
            }

            $em = Database::getManager();
            $course_id = api_get_course_int_id();
            if (!$empty_answer) {
                // Table definitions
                $tbl_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);

                // Getting all the information of the survey
                $survey_data = self::get_survey($surveyId);

                // Storing a new question
                if (empty($form_content['question_id']) || !is_numeric($form_content['question_id'])) {
                    // Finding the max sort order of the questions in the given survey
                    $sql = "SELECT max(sort) AS max_sort
					        FROM $tbl_survey_question
                            WHERE survey_id = $surveyId ";
                    $result = Database::query($sql);
                    $row = Database::fetch_assoc($result);
                    $max_sort = $row['max_sort'];

                    $question = new CSurveyQuestion();

                    // Some variables defined for survey-test type
                    if (isset($_POST['choose'])) {
                        if (1 == $_POST['choose']) {
                            $question->setSurveyGroupPri($_POST['assigned']);
                        } elseif (2 == $_POST['choose']) {
                            $question->setSurveyGroupSec1($_POST['assigned1']);
                            $question->setSurveyGroupSec2($_POST['assigned2']);
                        }
                    }

                    $question
                        ->setSurveyQuestionComment($form_content['question_comment'] ?? '')
                        ->setMaxValue($form_content['maximum_score'] ?? 0)
                        ->setDisplay($form_content['horizontalvertical'] ?? '')
                        //->setCId($course_id)
                        ->setSurvey($survey)
                        ->setSurveyQuestion($form_content['question'])
                        ->setType($form_content['type'])
                        ->setSort($max_sort + 1)
                        ->setSharedQuestionId((int) $form_content['shared_question_id'])
                    ;

                    $question->setIsMandatory(isset($form_content['is_required']));

                    $params['parent_id'] = 0;
                    $params['parent_option_id'] = 0;
                    if (isset($form_content['parent_id']) &&
                        isset($form_content['parent_option_id']) &&
                        !empty($form_content['parent_id']) &&
                        !empty($form_content['parent_option_id'])
                    ) {
                        $params['parent_id'] = $form_content['parent_id'];
                        $params['parent_option_id'] = $form_content['parent_option_id'];
                    }

                    $em->persist($question);
                    $em->flush();

                    $question_id = $question->getIid();
                    if ($question_id) {
                        /*$sql = "UPDATE $tbl_survey_question SET question_id = $question_id
                                WHERE iid = $question_id";
                        Database::query($sql);*/
                        $form_content['question_id'] = $question_id;
                        $message = 'The question has been added.';
                        $error = false;
                    }
                } else {
                    $repo = $em->getRepository(CSurveyQuestion::class);
                    $repoOption = $em->getRepository(CSurveyQuestionOption::class);
                    /** @var CSurveyQuestion $question */
                    $question = $repo->find($form_content['question_id']);

                    if (isset($_POST['choose'])) {
                        if (1 == $_POST['choose']) {
                            $question->setSurveyGroupPri($_POST['assigned']);
                            $question->setSurveyGroupSec1(0);
                            $question->setSurveyGroupSec2(0);
                        } elseif (2 == $_POST['choose']) {
                            $question->setSurveyGroupPri(0);
                            $question->setSurveyGroupSec1($_POST['assigned1']);
                            $question->setSurveyGroupSec2($_POST['assigned2']);
                        }
                    }

                    $maxScore = isset($form_content['maximum_score']) ? (int) $form_content['maximum_score'] : 0;
                    $questionComment = $form_content['question_comment'] ?? '';
                    $question
                        ->setSurveyQuestionComment($questionComment)
                        ->setSurveyQuestion($form_content['question'])
                        ->setDisplay($form_content['horizontalvertical'])
                        ->setMaxValue($maxScore)
                    ;

                    $question->isMandatory(isset($form_content['is_required']));
                    $params['parent_id'] = 0;
                    $params['parent_option_id'] = 0;
                    if (isset($form_content['parent_id']) &&
                        isset($form_content['parent_option_id']) &&
                        !empty($form_content['parent_id']) &&
                        !empty($form_content['parent_option_id'])
                    ) {
                        $question->setParent($repo->find($form_content['parent_id']));
                        $question->setParentOption($repoOption->find($form_content['parent_option_id']));
                    }

                    $em->persist($question);
                    $em->flush();
                    $message = 'QuestionUpdated';
                    $error = false;
                }
                // Storing the options of the question
                self::saveQuestionOptions($survey, $question, $form_content, $dataFromDatabase);
            } else {
                $message = 'PleasFillAllAnswer';
                $error = true;
            }
        } else {
            $message = 'PleaseEnterAQuestion';
            $error = true;
        }

        if ($showMessage) {
            if (!empty($message)) {
                Display::addFlash(Display::return_message(get_lang($message)));
            }
        }
        $result = [
            'error' => $error,
            'message' => $message,
        ];

        return $result;
    }

    /**
     * Moves a survey question within the ordered list.
     *
     * @param string $direction The direction to move the question ('moveup' or 'movedown').
     * @param int    $surveyQuestionId The ID of the survey question to move.
     * @param int    $surveyId The ID of the survey to which the question belongs.
     */
    public static function moveSurveyQuestion(string $direction, int $surveyQuestionId, int $surveyId): void
    {
        $em = Database::getManager();
        $repo = $em->getRepository(CSurveyQuestion::class);
        $sortDirection = $direction === 'moveup' ? 'DESC' : 'ASC';
        $questions = $repo->findBy(['survey' => $surveyId], ['sort' => $sortDirection]);

        $found = false;
        foreach ($questions as $question) {
            if ($found) {
                $secondQuestion = $question;
                $found = false;
                break;
            }
            if ($question->getIid() == $surveyQuestionId) {
                $found = true;
                $firstQuestion = $question;
            }
        }

        if (isset($firstQuestion) && isset($secondQuestion)) {
            $tempSort = $firstQuestion->getSort();
            $firstQuestion->setSort($secondQuestion->getSort());
            $secondQuestion->setSort($tempSort);

            $em->persist($firstQuestion);
            $em->persist($secondQuestion);
            $em->flush();
        }
    }

    /**
     * This function deletes all the questions of a given survey
     * This function is normally only called when a survey is deleted.
     *
     * @param int $survey_id the id of the survey that has to be deleted
     *
     * @return bool
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007
     */
    public static function delete_all_survey_questions($survey_id, $shared = false)
    {
        $course_id = api_get_course_int_id();
        $survey_id = (int) $survey_id;

        /*$sql = "DELETE FROM $table_survey_question
                WHERE $course_condition survey_id = '".$survey_id."'";

        // Deleting the survey questions
        Database::query($sql);*/

        // Deleting all the options of the questions of the survey
        //self::delete_all_survey_questions_options($survey_id, $shared);

        // Deleting all the answers on this survey
        //self::delete_all_survey_answers($survey_id);

        return true;
    }

    public static function deleteQuestion($questionId)
    {
        $questionId = (int) $questionId;
        if (empty($questionId)) {
            return false;
        }

        $em = Database::getManager();
        $repo = Container::getSurveyQuestionRepository();
        $question = $repo->find($questionId);
        if ($question) {
            $em->remove($question);
            $em->flush();

            return true;
        }

        return false;
    }

    public static function saveQuestionOptions(CSurvey $survey, CSurveyQuestion $question, $form_content, $dataFromDatabase = [])
    {
        $course_id = api_get_course_int_id();
        $type = $form_content['type'];

        // A percentage question type has options 1 -> 100
        if ('percentage' === $type) {
            for ($i = 1; $i < 101; $i++) {
                $form_content['answers'][] = $i;
            }
        }
        $em = Database::getManager();

        // Table definition
        $table = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);

        // We are editing a question so we first have to remove all the existing options from the database
        $optionsToDelete = [];
        if (isset($dataFromDatabase['answer_data'])) {
            foreach ($dataFromDatabase['answer_data'] as $data) {
                if ('other' === $data['data'] && 'multiplechoiceother' === $type) {
                    continue;
                }

                if (!in_array($data['iid'], $form_content['answersid'])) {
                    $optionsToDelete[] = $data['iid'];
                }
            }
        }

        if (!empty($optionsToDelete)) {
            foreach ($optionsToDelete as $iid) {
                $iid = (int) $iid;
                $sql = "DELETE FROM $table
			            WHERE
			                iid = $iid AND
                            question_id = '".intval($form_content['question_id'])."'
                            ";
                Database::query($sql);
            }
        }

        $counter = 1;
        if (isset($form_content['answers']) && is_array($form_content['answers'])) {
            for ($i = 0; $i < count($form_content['answers']); $i++) {
                $values = isset($form_content['values']) ? (int) $form_content['values'][$i] : 0;
                $answerId = 0;
                if (isset($form_content['answersid']) && isset($form_content['answersid'][$i])) {
                    $answerId = $form_content['answersid'][$i];
                }
                if (empty($answerId)) {
                    $option = new CSurveyQuestionOption();
                    $option
                        ->setQuestion($question)
                        ->setOptionText($form_content['answers'][$i])
                        ->setSurvey($survey)
                        ->setValue($values)
                        ->setSort($counter)
                    ;
                    $em->persist($option);
                    $em->flush();
                    $insertId = $option->getIid();
                    if ($insertId) {
                        $counter++;
                    }
                } else {
                    $repo = $em->getRepository(CSurveyQuestionOption::class);
                    /** @var CSurveyQuestionOption $option */
                    $option = $repo->find($answerId);
                    if ($option) {
                        $option
                            ->setOptionText($form_content['answers'][$i])
                            ->setValue($values)
                            ->setSort($counter)
                        ;
                        $em->persist($option);
                        $em->flush();
                    }
                    $counter++;
                }
            }
        }

        if ('multiplechoiceother' === $type) {
            if (empty($dataFromDatabase['answer_data'])) {
                $params = [
                    'question_id' => $form_content['question_id'],
                    'survey_id' => $form_content['survey_id'],
                    'option_text' => 'other',
                    'value' => 0,
                    'sort' => $counter,
                ];
                Database::insert($table, $params);
            } else {
                $params = [
                    'option_text' => 'other',
                    'value' => 0,
                    'sort' => $counter,
                ];
                Database::update(
                    $table,
                    $params,
                    [
                        'question_id = ? AND survey_id = ? AND option_text = ?' => [
                            $form_content['question_id'],
                            $form_content['survey_id'],
                            'other',
                        ],
                    ]
                );
            }
        }
    }

    /**
     * This function deletes all the options of the questions of a given survey
     * This function is normally only called when a survey is deleted.
     *
     * @param int $survey_id the id of the survey that has to be deleted
     *
     * @return true
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007
     */
    public static function delete_all_survey_questions_options($survey_id, $shared = false)
    {
        // Table definitions
        $table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $course_id = api_get_course_int_id();
        $course_condition = " c_id = $course_id AND ";
        $sql = "DELETE FROM $table_survey_question_option
                WHERE $course_condition survey_id='".intval($survey_id)."'";

        // Deleting the options of the survey questions
        Database::query($sql);

        return true;
    }

    /**
     * SURVEY ANSWERS FUNCTIONS.
     */

    /**
     * This function deletes all the answers anyone has given on this survey
     * This function is normally only called when a survey is deleted.
     *
     * @param $survey_id the id of the survey that has to be deleted
     *
     * @return true
     *
     * @todo write the function
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007,december 2008
     */
    public static function delete_all_survey_answers($survey_id)
    {
        $course_id = api_get_course_int_id();
        $table = Database::get_course_table(TABLE_SURVEY_ANSWER);
        $survey_id = (int) $survey_id;
        $sql = "DELETE FROM $table
                WHERE c_id = $course_id AND survey_id = $survey_id";
        Database::query($sql);

        return true;
    }

    /**
     * This function gets all the persons who have filled the survey.
     *
     * @param int $survey_id
     *
     * @return array
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     */
    public static function get_people_who_filled_survey(
        $survey_id,
        $all_user_info = false,
        $course_id = null
    ) {
        // Database table definition
        $table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);
        $table_user = Database::get_main_table(TABLE_MAIN_USER);

        // Variable initialisation
        $return = [];

        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        } else {
            $course_id = (int) $course_id;
        }

        $survey_id = (int) $survey_id;

        if ($all_user_info) {
            $order_clause = api_sort_by_first_name()
                ? ' ORDER BY user.firstname, user.lastname'
                : ' ORDER BY user.lastname, user.firstname';
            $sql = "SELECT DISTINCT
			            answered_user.user as invited_user,
			            user.firstname,
			            user.lastname,
			            user.id as user_id
                    FROM $table_survey_answer answered_user
                    LEFT JOIN $table_user as user
                    ON answered_user.user = user.id
                    WHERE
                        survey_id= '".$survey_id."' ".
                $order_clause;
        } else {
            $sql = "SELECT DISTINCT user FROM $table_survey_answer
			        WHERE survey_id= '".$survey_id."'  ";

            if ('true' === api_get_setting('survey.survey_anonymous_show_answered')) {
                $tblInvitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
                $tblSurvey = Database::get_course_table(TABLE_SURVEY);

                $sql = "SELECT i.user_id FROM $tblInvitation i
                    INNER JOIN $tblSurvey s
                    ON i.survey_code = s.code
                    WHERE i.answered IS TRUE AND s.iid = $survey_id";
            }
        }

        $res = Database::query($sql);
        while ($row = Database::fetch_assoc($res)) {
            if ($all_user_info) {
                $userInfo = api_get_user_info($row['user_id']);
                $row['user_info'] = $userInfo;
                $return[] = $row;
            } else {
                $return[] = $row['user'];
            }
        }

        return $return;
    }

    /**
     * @return bool
     */
    public static function survey_generation_hash_available()
    {
        if (extension_loaded('mcrypt')) {
            return true;
        }

        return false;
    }

    /**
     * @param int $survey_id
     * @param int $course_id
     * @param int $session_id
     * @param int $group_id
     *
     * @return string
     */
    public static function generate_survey_hash(int $survey_id, int $course_id, int $session_id, int $group_id): string
    {
        $securityKey = api_get_env_variable('kernel.secret');

        return hash(
            'sha512',
            $securityKey.'_'.$course_id.'_'.$session_id.'_'.$group_id.'_'.$survey_id
        );
    }

    /**
     * @param int    $survey_id
     * @param int    $course_id
     * @param int    $session_id
     * @param int    $group_id
     * @param string $hash
     *
     * @return bool
     */
    public static function validate_survey_hash($survey_id, $course_id, $session_id, $group_id, $hash)
    {
        $generatedHash = self::generate_survey_hash($survey_id, $course_id, $session_id, $group_id);
        if ($generatedHash == $hash) {
            return true;
        }

        return false;
    }

    /**
     * @param int $survey_id
     * @param int $course_id
     * @param int $session_id
     * @param int $group_id
     *
     * @return string
     */
    public static function generate_survey_link(
        $survey_id,
        $course_id,
        $session_id,
        $group_id
    ) {
        $code = self::generate_survey_hash(
            $survey_id,
            $course_id,
            $session_id,
            $group_id
        );

        return api_get_path(WEB_CODE_PATH).'survey/link.php?h='.$code.'&i='.$survey_id.'&c='.intval($course_id).'&s='
            .intval($session_id).'&g='.$group_id;
    }

    /**
     * Check if the current user has mandatory surveys no-answered
     * and redirect to fill the first found survey.
     */
    public static function protectByMandatory()
    {
        if (false !== strpos($_SERVER['SCRIPT_NAME'], 'fillsurvey.php')) {
            return;
        }

        $userId = api_get_user_id();
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        if (!$userId) {
            return;
        }

        if (!$courseId) {
            return;
        }

        try {
            /** @var CSurveyInvitation $invitation */
            $invitation = Database::getManager()
                ->createQuery("
                    SELECT i FROM ChamiloCourseBundle:CSurveyInvitation i
                    INNER JOIN ChamiloCourseBundle:CSurvey s
                        WITH (s.code = i.surveyCode AND s.cId = i.cId AND s.sessionId = i.sessionId)
                    INNER JOIN ChamiloCoreBundle:ExtraFieldValues efv WITH efv.itemId = s.iid
                    INNER JOIN ChamiloCoreBundle:ExtraField ef WITH efv.field = ef.id
                    WHERE
                        i.answered = 0 AND
                        i.cId = :course AND
                        i.user = :user AND
                        i.sessionId = :session AND
                        :now BETWEEN s.availFrom AND s.availTill AND
                        ef.variable = :variable AND
                        efv.field_value = 1 AND
                        s.surveyType != 3
                    ORDER BY s.availTill ASC
                ")
                ->setMaxResults(1)
                ->setParameters([
                    'course' => $courseId,
                    'user' => $userId,
                    'session' => $sessionId,
                    'now' => new DateTime('UTC', new DateTimeZone('UTC')),
                    'variable' => 'is_mandatory',
                ])
                ->getSingleResult();
        } catch (Exception $e) {
            $invitation = null;
        }

        if (!$invitation) {
            return;
        }

        Display::addFlash(
            Display::return_message(
                get_lang(
                    'A mandatory survey is waiting your answer. To enter the course, you must first complete the survey.'
                ),
                'warning'
            )
        );

        $url = SurveyUtil::generateFillSurveyLink(
            $invitation->getInvitationCode(),
            api_get_course_info(),
            api_get_session_id()
        );

        header('Location: '.$url);
        exit;
    }

    /**
     * This function empty surveys (invitations and answers).
     *
     * @param int $surveyId id of the survey to empty
     *
     * @return bool
     */
    public static function emptySurveyFromId($surveyId)
    {
        // Database table definitions
        $surveyInvitationTable = Database:: get_course_table(TABLE_SURVEY_INVITATION);
        $surveyAnswerTable = Database:: get_course_table(TABLE_SURVEY_ANSWER);
        $surveyTable = Database:: get_course_table(TABLE_SURVEY);
        $surveyId = (int) $surveyId;
        $surveyData = self::get_survey($surveyId);
        if (empty($surveyData)) {
            return false;
        }

        $surveyCode = $surveyData['survey_code'];
        $courseId = (int) $surveyData['c_id'];
        $sessionId = (int) $surveyData['session_id'];

        $sql = "DELETE FROM $surveyInvitationTable
                WHERE session_id = $sessionId AND c_id = $courseId AND survey_code = '$surveyCode' ";
        Database::query($sql);

        $sql = "DELETE FROM $surveyAnswerTable
                WHERE survey_id = $surveyId AND c_id = $courseId ";
        Database::query($sql);

        $sql = "UPDATE $surveyTable
                SET invited = 0, answered = 0
                WHERE iid = $surveyId AND c_id = $courseId AND session_id = $sessionId ";
        Database::query($sql);

        return true;
    }

    /**
     * Copy/duplicate one question (into the same survey).
     * Note: Relies on the question iid to find all necessary info.
     *
     * @param int $questionId
     *
     * @return int The new question's iid, or 0 on error
     */
    public static function copyQuestion($questionId)
    {
        if (empty($questionId)) {
            return 0;
        }
        $questionId = (int) $questionId;
        $questionTable = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $optionsTable = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);

        // Get questions
        $sql = "SELECT * FROM $questionTable WHERE iid = $questionId";
        $res = Database::query($sql);
        if (false == $res) {
            // Could not find this question
            return 0;
        }
        $row = Database::fetch_assoc($res);
        $params = [
            //'c_id' => $row['c_id'],
            'survey_id' => $row['survey_id'],
            'survey_question' => trim($row['survey_question']),
            'survey_question_comment' => $row['survey_question_comment'],
            'type' => $row['type'],
            'display' => $row['display'],
            'shared_question_id' => $row['shared_question_id'],
            'max_value' => $row['max_value'],
            'survey_group_pri' => $row['survey_group_pri'],
            'survey_group_sec1' => $row['survey_group_sec1'],
            'survey_group_sec2' => $row['survey_group_sec2'],
        ];
        if (isset($row['is_required'])) {
            $params['is_required'] = $row['is_required'];
        }
        // Get question position
        $sqlSort = "SELECT max(sort) as sort FROM $questionTable
                    WHERE survey_id = ".$row['survey_id'];
        $resSort = Database::query($sqlSort);
        $rowSort = Database::fetch_assoc($resSort);
        $params['sort'] = $rowSort['sort'] + 1;
        // Insert the new question
        $insertId = Database::insert($questionTable, $params);
        if (false == $insertId) {
            return 0;
        }

        // Get questions options
        $sql = "SELECT * FROM $optionsTable WHERE question_id = $questionId";
        $res = Database::query($sql);
        while ($row = Database::fetch_assoc($res)) {
            $params = [
                //'c_id' => $row['c_id'],
                'question_id' => $insertId,
                'survey_id' => $row['survey_id'],
                'option_text' => $row['option_text'],
                'sort' => $row['sort'],
                'value' => $row['value'],
            ];
            Database::insert($optionsTable, $params);
        }

        return $insertId;
    }

    /**
     * @param array $surveyData
     *
     * @return bool
     */
    public static function removeMultiplicateQuestions(CSurvey $survey, $courseId)
    {
        $surveyId = $survey->getIid();
        $courseId = (int) $courseId;

        if (empty($surveyId) || empty($courseId)) {
            return false;
        }

        $questions = $survey->getQuestions();
        foreach ($questions as $question) {
            // Questions marked with "geneated" were created using the "multiplicate" feature.
            if ('generated' === $question->getSurveyQuestionComment()) {
                self::deleteQuestion($question->getIid());
            }
        }
    }

    /**
     * @return bool
     */
    public static function multiplicateQuestions(CSurvey $survey, $courseId)
    {
        $surveyId = $survey->getIid();

        if (empty($surveyId)) {
            return false;
        }

        $questions = self::get_questions($surveyId);

        if (empty($questions)) {
            return false;
        }

        $extraFieldValue = new ExtraFieldValue('survey');
        $groupData = $extraFieldValue->get_values_by_handler_and_field_variable($surveyId, 'group_id');
        $groupId = null;
        if ($groupData && !empty($groupData['value'])) {
            $groupId = (int) $groupData['value'];
        }

        if (null === $groupId) {
            $obj = new UserGroupModel();
            $options['where'] = [' usergroup.course_id = ? ' => $courseId];
            $classList = $obj->getUserGroupInCourse($options);

            $classToParse = [];
            foreach ($classList as $class) {
                $users = $obj->get_users_by_usergroup($class['id']);
                if (empty($users)) {
                    continue;
                }
                $classToParse[] = [
                    'name' => $class['name'],
                    'users' => $users,
                ];
            }
            self::parseMultiplicateUserList($classToParse, $questions, $courseId, $survey, true);
        } else {
            $groupInfo = GroupManager::get_group_properties($groupId);
            if (!empty($groupInfo)) {
                $users = GroupManager::getStudents($groupInfo['iid'], true);
                if (!empty($users)) {
                    $users = array_column($users, 'id');
                    self::parseMultiplicateUserList(
                        [
                            [
                                'name' => $groupInfo['name'],
                                'users' => $users,
                            ],
                        ],
                        $questions,
                        $courseId,
                        $survey,
                        false
                    );
                }
            }
        }

        return true;
    }

    public static function parseMultiplicateUserList($itemList, $questions, $courseId, CSurvey $survey, $addClassNewPage = false)
    {
        if (empty($itemList) || empty($questions)) {
            return false;
        }

        $surveyId = $survey->getIid();
        $classTag = '{{class_name}}';
        $studentTag = '{{student_full_name}}';
        $classCounter = 0;

        $newQuestionList = [];
        foreach ($questions as $question) {
            $newQuestionList[$question['sort']] = $question;
        }
        ksort($newQuestionList);

        $order = ('true' === api_get_setting('survey.survey_duplicate_order_by_name'));
        foreach ($itemList as $class) {
            $className = $class['name'];
            $users = $class['users'];
            $userInfoList = [];
            foreach ($users as $userId) {
                $userInfoList[] = api_get_user_info($userId);
            }

            if ($order) {
                usort(
                    $userInfoList,
                    function ($a, $b) {
                        return $a['lastname'] > $b['lastname'];
                    }
                );
            }

            foreach ($newQuestionList as $question) {
                $text = $question['question'];
                if (false !== strpos($text, $classTag)) {
                    $replacedText = str_replace($classTag, $className, $text);
                    $values = [
                        'c_id' => $courseId,
                        'question_comment' => 'generated',
                        'type' => $question['type'],
                        'display' => $question['horizontalvertical'],
                        'horizontalvertical' => $question['horizontalvertical'],
                        'question' => $replacedText,
                        'survey_id' => $surveyId,
                        'question_id' => 0,
                        'shared_question_id' => 0,
                        'answers' => $question['answers'] ?? null,
                    ];
                    self::saveQuestion($survey, $values, false);
                    $classCounter++;
                    continue;
                }

                foreach ($userInfoList as $userInfo) {
                    if (false !== strpos($text, $studentTag)) {
                        $replacedText = str_replace($studentTag, $userInfo['complete_name'], $text);
                        $values = [
                            'c_id' => $courseId,
                            'question_comment' => 'generated',
                            'type' => $question['type'],
                            'display' => $question['horizontalvertical'],
                            'maximum_score' => $question['maximum_score'],
                            'question' => $replacedText,
                            'survey_id' => $surveyId,
                            'question_id' => 0,
                            'shared_question_id' => 0,
                        ];

                        $answers = [];
                        if (!empty($question['answers'])) {
                            foreach ($question['answers'] as $answer) {
                                $replacedText = str_replace($studentTag, $userInfo['complete_name'], $answer);
                                $answers[] = $replacedText;
                            }
                        }
                        $values['answers'] = $answers;
                        self::saveQuestion($survey, $values, false);
                    }
                }

                if ($addClassNewPage && $classCounter < count($itemList)) {
                    // Add end page
                    $values = [
                        'c_id' => $courseId,
                        'question_comment' => 'generated',
                        'type' => 'pagebreak',
                        'display' => 'horizontal',
                        'question' => get_lang('Question for next class'),
                        'survey_id' => $surveyId,
                        'question_id' => 0,
                        'shared_question_id' => 0,
                    ];
                    self::saveQuestion($survey, $values, false);
                }
            }
        }

        return true;
    }

    public static function hasDependency(CSurvey $survey)
    {
        $surveyId = $survey->getIid();

        $table = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $sql = "SELECT COUNT(iid) count FROM $table
                WHERE
                    survey_id = $surveyId AND
                    parent_option_id <> 0
                LIMIT 1
                ";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        if ($row) {
            return $row['count'] > 0;
        }

        return false;
    }

    /**
     * @return int
     */
    public static function getCountPages(CSurvey $survey)
    {
        $surveyId = $survey->getIid();

        $table = Database::get_course_table(TABLE_SURVEY_QUESTION);

        // pagebreak
        $sql = "SELECT COUNT(iid) FROM $table
                WHERE
                    survey_question NOT LIKE '%{{%' AND
                    type = 'pagebreak' AND
                    survey_id = $surveyId";
        $result = Database::query($sql);
        $numberPageBreaks = Database::result($result, 0, 0);

        // No pagebreak
        $sql = "SELECT COUNT(iid) FROM $table
                WHERE
                    survey_question NOT LIKE '%{{%' AND
                    type != 'pagebreak' AND
                    survey_id = $surveyId";
        $result = Database::query($sql);
        $countOfQuestions = Database::result($result, 0, 0);

        if (1 == $survey->getOneQuestionPerPage()) {
            if (!empty($countOfQuestions)) {
                return $countOfQuestions;
            }

            return 1;
        }

        if (empty($numberPageBreaks)) {
            return 1;
        }

        return $numberPageBreaks + 1;
    }

    /**
     * Check whether this survey has ended. If so, display message and exit this script.
     */
    public static function checkTimeAvailability(?CSurvey $survey): void
    {
        if (null === $survey) {
            api_not_allowed(true);
        }

        $utcZone = new DateTimeZone('UTC');
        $startDate = $survey->getAvailFrom();
        $endDate = $survey->getAvailTill();
        $currentDate = new DateTime('now', $utcZone);
        $currentDate->modify('today');

        $returnMessage = false;
        if ($currentDate < $startDate) {
            $returnMessage =  Display:: return_message(
                get_lang('This survey is not yet available. Please try again later. Thank you.'),
                'warning',
                false
            );
        }

        if ($currentDate > $endDate) {
            $returnMessage = Display:: return_message(
                get_lang('Sorry, this survey is not available anymore. Thank you for trying.'),
                'warning',
                false
            );
        }

        if (false !== $returnMessage) {
            $content = Display::page_header($survey->getTitle());
            $content .= $returnMessage;
            $template = new Template();
            $template->assign('actions', Display::toolbarAction('toolbar', []));
            $template->assign('content', $content);
            $template->display_one_col_template();
            exit;
        }
    }

    public static function getUserInvitationsForSurveyInCourse(
        int    $userId,
        string $surveyCode,
        int    $courseId,
        int    $sessionId = 0,
        int    $groupId = 0,
        int    $lpItemId = 0
    ): array {

        $em = Database::getManager();
        $invitationRepo = $em->getRepository(CSurveyInvitation::class);
        $surveyRepo = $em->getRepository(CSurvey::class);
        $survey = $surveyRepo->findBy(['code' => $surveyCode]);

        $criteria = [
            'user' => api_get_user_entity($userId),
            'course' => api_get_course_entity($courseId),
            'session' => api_get_session_entity($sessionId),
            'group' => api_get_group_entity($groupId),
            'survey' => $survey,
        ];

        if (is_int($lpItemId) && $lpItemId > 0) {
            $criteria['lpItemId'] = $lpItemId;
        }

        return $invitationRepo->findBy(
            $criteria,
            ['invitationDate' => 'DESC']
        );
    }


    /**
     * @param array $userInfo
     * @param int   $answered (1 = answered 0 = not answered)
     *
     * @return string
     */
    public static function surveyReport($userInfo, $answered = 0)
    {
        $userId = isset($userInfo['user_id']) ? (int) $userInfo['user_id'] : 0;
        $answered = (int) $answered;

        if (empty($userId)) {
            return '';
        }

        $em = Database::getManager();
        $repo = $em->getRepository(CSurveyInvitation::class);
        $repoSurvey = $em->getRepository(CSurvey::class);
        $invitations = $repo->findBy(['user' => $userId, 'answered' => $answered]);
        $mainUrl = api_get_path(WEB_CODE_PATH).'survey/survey.php?';
        $content = '';

        if (empty($answered)) {
            $content .= Display::page_subheader(get_lang('Unanswered'));
        } else {
            $content .= Display::page_subheader(get_lang('Answered'));
        }

        if (!empty($invitations)) {
            $table = new HTML_Table(['class' => 'table']);
            $table->setHeaderContents(0, 0, get_lang('Survey name'));
            $table->setHeaderContents(0, 1, get_lang('Course'));

            if (empty($answered)) {
                $table->setHeaderContents(0, 2, get_lang('Survey').' - '.get_lang('End Date'));
            }

            // Not answered
            /** @var CSurveyInvitation $invitation */
            $row = 1;
            foreach ($invitations as $invitation) {
                $course = $invitation->getCourse();
                $courseId = $course->getId();
                $courseCode = $course->getCode();
                $sessionId = $invitation->getSession() ? $invitation->getSession()->getId() : 0;

                if (!empty($answered)) {
                    // check if user is subscribed to the course/session
                    if (empty($sessionId)) {
                        $subscribe = CourseManager::is_user_subscribed_in_course($userId, $courseCode);
                    } else {
                        $subscribe = CourseManager::is_user_subscribed_in_course(
                            $userId,
                            $courseCode,
                            true,
                            $sessionId
                        );
                    }

                    // User is not subscribe skip!
                    if (empty($subscribe)) {
                        continue;
                    }
                }

                $survey = $invitation->getSurvey();
                if (null === $survey) {
                    continue;
                }

                $url = $mainUrl.'survey_id='.$survey->getIid().'&cid='.$courseId.'&sid='.$sessionId;
                $title = $survey->getTitle();
                $title = Display::url($title, $url);
                $courseTitle = $course->getTitle();
                if (!empty($sessionId)) {
                    $courseTitle .= ' ('.$invitation->getSession()->getTitle().')';
                }

                $surveyData = self::get_survey($survey->getIid(), 0, $courseCode);
                $table->setCellContents($row, 0, $title);
                $table->setCellContents($row, 1, $courseTitle);

                if (empty($answered)) {
                    $table->setHeaderContents(
                        $row,
                        2,
                        api_get_local_time(
                            $survey->getAvailTill(),
                            null,
                            null,
                            true,
                            false
                        )
                    );
                }

                if (!empty($answered) && 0 == $survey->getAnonymous()) {
                    $answers = SurveyUtil::displayCompleteReport(
                        $surveyData,
                        $userId,
                        false,
                        false,
                        false
                    );
                    $table->setCellContents(++$row, 0, $answers);
                    $table->setCellContents(++$row, 1, '');
                }

                $row++;
            }
            $content .= $table->toHtml();
        } else {
            $content .= Display::return_message(get_lang('No data available'));
        }

        return $content;
    }

    public static function sendToTutors(CSurvey $survey)
    {
        $surveyId = $survey->getIid();

        $extraFieldValue = new ExtraFieldValue('survey');
        $groupData = $extraFieldValue->get_values_by_handler_and_field_variable($surveyId, 'group_id');
        if ($groupData && !empty($groupData['value'])) {
            $groupInfo = GroupManager::get_group_properties($groupData['value']);
            if ($groupInfo) {
                $tutors = GroupManager::getTutors($groupInfo);
                if (!empty($tutors)) {
                    SurveyUtil::saveInviteMail(
                        $survey,
                        ' ',
                        ' ',
                        false
                    );

                    foreach ($tutors as $tutor) {
                        $subject = sprintf(get_lang('GroupSurveyX'), $groupInfo['name']);
                        $content = sprintf(
                            get_lang('HelloXGroupX'),
                            $tutor['complete_name'],
                            $groupInfo['name']
                        );

                        SurveyUtil::saveInvitations(
                            $survey,
                            ['users' => $tutor['user_id']],
                            $subject,
                            $content,
                            false,
                            true,
                            false,
                            true
                        );
                    }
                    Display::addFlash(Display::return_message(get_lang('Updated'), 'confirmation', false));
                }
                SurveyUtil::updateInvitedCount($survey);

                return true;
            }
        }

        return false;
    }

    public static function getInvitationsAnswered(
        $surveyCode,
        $courseId,
        $sessionId = 0
    ): array
    {
        $invitationRepo = Database::getManager()->getRepository(CSurveyInvitation::class);

        return $invitationRepo->findBy(
            [
                'cId' => $courseId,
                'sessionId' => $sessionId,
                'answered' => true,
                'surveyCode' => $surveyCode,
            ],
            ['invitationDate' => 'DESC']
        );
    }
}
