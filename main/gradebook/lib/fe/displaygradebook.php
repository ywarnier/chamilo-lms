<?php
/* For licensing terms, see /license.txt */

/**
 * Class DisplayGradebook
 * @package chamilo.gradebook
 */
class DisplayGradebook
{
    /**
     * Displays the header for the result page containing the navigation tree and links
     * @param $evalobj
     * @param $selectcat
     * @param $shownavbar 1=show navigation bar
     * @param $forpdf only output for pdf file
     */
    public static function display_header_result($evalobj, $selectcat, $page)
    {
        $header = null;
        if (api_is_allowed_to_edit(null, true)) {
            $header = '<div class="actions">';
            if ($page != 'statistics') {
                $header .= '<a href="' . Security::remove_XSS($_SESSION['gradebook_dest']) . '?selectcat=' . $selectcat . '&'.api_get_cidreq().'">' .
                    Display::return_icon(('back.png'), get_lang('FolderView'), '', ICON_SIZE_MEDIUM) . '</a>';
                if ($evalobj->get_course_code() == null) {

                } elseif (!$evalobj->has_results()) {
                    $header .= '<a href="gradebook_add_result.php?'.api_get_cidreq().'&selectcat=' . $selectcat . '&selecteval=' . $evalobj->get_id() . '">
    				' . Display::return_icon('evaluation_rate.png', get_lang('AddResult'), '', ICON_SIZE_MEDIUM) . '</a>';
                }

                if (api_is_platform_admin() || $evalobj->is_locked() == false) {
                    $header .= '<a href="' . api_get_self() . '?'.api_get_cidreq().'&selecteval=' . $evalobj->get_id() . '&import=">' .
                        Display::return_icon('import_evaluation.png', get_lang('ImportResult'), '', ICON_SIZE_MEDIUM) . '</a>';
                }

                if ($evalobj->has_results()) {
                    $header .= '<a href="' . api_get_self() . '?'.api_get_cidreq().'&selecteval=' . $evalobj->get_id() . '&export=">' .
                        Display::return_icon('export_evaluation.png', get_lang('ExportResult'), '', ICON_SIZE_MEDIUM) . '</a>';

                    if (api_is_platform_admin() || $evalobj->is_locked() == false) {
                        $header .= '<a href="gradebook_edit_result.php?'.api_get_cidreq().'&selecteval=' . $evalobj->get_id() . '">' .
                            Display::return_icon('edit.png', get_lang('EditResult'), '', ICON_SIZE_MEDIUM) . '</a>';
                        $header .= '<a href="' . api_get_self() . '?'.api_get_cidreq().'&selecteval=' . $evalobj->get_id() . '&deleteall=" onclick="return confirmationall();">' .
                            Display::return_icon('delete.png', get_lang('DeleteResult'), '', ICON_SIZE_MEDIUM) . '</a>';
                    }
                }

                $header .= '<a href="' . api_get_self() . '?'.api_get_cidreq().'&print=&selecteval=' . $evalobj->get_id() . '" target="_blank">' .
                    Display::return_icon('printer.png', get_lang('Print'), '', ICON_SIZE_MEDIUM) . '</a>';
            } else {
                $header .= '<a href="gradebook_view_result.php?'.api_get_cidreq().'&selecteval=' . Security::remove_XSS($_GET['selecteval']) . '"> ' .
                    Display::return_icon(('back.png'), get_lang('FolderView'), '', ICON_SIZE_MEDIUM) . '</a>';
            }
            $header .= '</div>';
        }

        if ($evalobj->is_visible() == '1') {
            $visible = get_lang('Yes');
        } else {
            $visible = get_lang('No');
        }

        $scoredisplay = ScoreDisplay :: instance();

        $student_score = '';
        $average = "";
        if (($evalobj->has_results())) { // TODO this check needed ?
            $score = $evalobj->calc_score();

            if ($score != null) {
                $average = get_lang('Average') . ' :<b> ' . $scoredisplay->display_score($score, SCORE_AVERAGE) . '</b>';
                $student_score = $evalobj->calc_score(api_get_user_id());
                $student_score = Display::tag(
                    'h3',
                    get_lang('Score') . ': ' . $scoredisplay->display_score($student_score, SCORE_DIV_PERCENT)
                );
            }
        }
        $description = "";
        if (!$evalobj->get_description() == '') {
            $description = get_lang('Description') . ' :<b> ' . $evalobj->get_description() . '</b><br>';
        }

        if ($evalobj->get_course_code() == null) {
            $course = get_lang('CourseIndependent');
        } else {
            $course = CourseManager::getCourseNameFromCode($evalobj->get_course_code());
        }

        $evalinfo = '<table width="100%" border="0"><tr><td>';
        $evalinfo .= '<h2>' . $evalobj->get_name() . '</h2><hr>';
        $evalinfo .= $description;
        $evalinfo .= get_lang('Course') . ' :<b> ' . $course . '</b><br />';
        $evalinfo .= get_lang('QualificationNumeric') . ' :<b> ' . $evalobj->get_max() . '</b><br>' . $average;

        if (!api_is_allowed_to_edit()) {
            $evalinfo .= $student_score;
        }

        if (!$evalobj->has_results()) {
            $evalinfo .= '<br /><i>' . get_lang('NoResultsInEvaluation') . '</i>';
        } elseif ($scoredisplay->is_custom() && api_get_self() != '/main/gradebook/gradebook_statistics.php') {
            if (api_is_allowed_to_edit(null, true)) {
                if ($page != 'statistics') {
                    //$evalinfo .= '<br /><br /><a href="gradebook_view_result.php?selecteval='.Security::remove_XSS($_GET['selecteval']).'"> '.Display::return_icon(('evaluation_rate.png'),get_lang('ViewResult'),'',ICON_SIZE_MEDIUM) . '</a>';
                }
            }
        }
        if ($page != 'statistics') {
            if (api_is_allowed_to_edit(null, true)) {
                $evalinfo .= '<br /><a href="gradebook_statistics.php?' . api_get_cidreq() . '&selecteval=' . Security::remove_XSS($_GET['selecteval']) . '"> ' .
                    Display::return_icon('statistics.png', get_lang('ViewStatistics'), '', ICON_SIZE_MEDIUM) . '</a>';
            }
        }
        $evalinfo .= '</td><td>'.Display::return_icon('tutorial.gif', '', ['style' => 'float:right; position:relative;']).'</td></table>';
        echo $evalinfo;
        echo $header;
    }

    /**
     * Displays the header for the flatview page containing filters
     * @param $catobj
     * @param $showeval
     * @param $showlink
     */
    public function display_header_flatview($catobj, $showeval, $showlink, $simple_search_form)
    {
        $header = '<table border="0" cellpadding="5">';
        $header .= '<td style="vertical-align: top;"><a href="' . Security::remove_XSS($_SESSION['gradebook_dest']) . '?selectcat=' . Security::remove_XSS($_GET['selectcat']) . '">' . Display::return_icon('gradebook.gif') . get_lang('Gradebook') . '</a></td>';
        $header .= '<td style="vertical-align: top;">' . get_lang('FilterCategory') . '</td><td style="vertical-align: top;"><form name="selector"><select name="selectcat" onchange="document.selector.submit()">';
        $cats = Category :: load();
        $tree = $cats[0]->get_tree();
        unset($cats);
        foreach ($tree as $cat) {
            for ($i = 0; $i < $cat[2]; $i++) {
                $line .= '&mdash;';
            }
            if ($_GET['selectcat'] == $cat[0]) {
                $header .= '<option selected="selected" value=' . $cat[0] . '>' . $line . ' ' . $cat[1] . '</option>';
            } else {
                $header .= '<option value=' . $cat[0] . '>' . $line . ' ' . $cat[1] . '</option>';
            }
            $line = '';
        }
        $header .= '</td></select></form>';
        if (!$catobj->get_id() == '0') {
            $header .= '<td style="vertical-align: top;"><a href="' . api_get_self() . '?selectcat=' . $catobj->get_parent_id() . '">';
            $header .= Display::return_icon('gradebook.gif', get_lang('Up'));
            $header .= '</a></td>';
        }
        $header .= '<td style="vertical-align: top;">' . $simple_search_form->toHtml() . '</td>';
        $header .= '<td style="vertical-align: top;">
                    <a href="' . api_get_self() . '?exportpdf=&offset=' . Security::remove_XSS($_GET['offset']) . '&search=' . Security::remove_XSS($_GET['search']) . '&selectcat=' . $catobj->get_id() . '">
                     '.Display::return_icon('pdf.png', get_lang('ExportPDF'), [], ICON_SIZE_MEDIUM).'
                    ' . get_lang('ExportPDF') . '</a>';
        $header .= '<td style="vertical-align: top;">
                    <a href="' . api_get_self() . '?print=&selectcat=' . $catobj->get_id() . '" target="_blank">
                     '.Display::return_icon('printer.png', get_lang('Print'), [], ICON_SIZE_MEDIUM).'
                    ' . get_lang('Print') . '</a>';
        $header .= '</td></tr></table>';
        if (!$catobj->get_id() == '0') {
            $header .= '<table border="0" cellpadding="5"><tr><td><form name="itemfilter" method="post" action="' . api_get_self() . '?selectcat=' . $catobj->get_id() . '">
            <input type="checkbox" name="showeval" onclick="document.itemfilter.submit()" ' . (($showeval == '1') ? 'checked' : '') . '>Show Evaluations &nbsp;';
            $header .= '<input type="checkbox" name="showlink" onclick="document.itemfilter.submit()" ' . (($showlink == '1') ? 'checked' : '') . '>' . get_lang('ShowLinks') . '</form></td></tr></table>';
        }
        if (isset($_GET['search'])) {
            $header .= '<b>' . get_lang('SearchResults') . ' :</b>';
        }
        echo $header;
    }

    /**
     * Displays the header for the flatview page containing filters
     * @param $catobj
     * @param $showeval
     * @param $showlink
     */
    public static function display_header_reduce_flatview($catobj, $showeval, $showlink, $simple_search_form)
    {
        $header = '<div class="actions">';
        if ($catobj->get_parent_id() == 0) {
            $select_cat = $catobj->get_id();
            $url = Security::remove_XSS($_SESSION['gradebook_dest']);
        } else {
            $select_cat = $catobj->get_parent_id();
            $url = 'gradebook_flatview.php';
        }
        $header .= '<a href="' . $url . '?' . api_get_cidreq() . '&selectcat=' . $select_cat . '">' .
            Display::return_icon('back.png', get_lang('FolderView'), '', ICON_SIZE_MEDIUM) . '</a>';

        $pageNum = isset($_GET['flatviewlist_page_nr']) ? intval($_GET['flatviewlist_page_nr']) : '';
        $perPage = isset($_GET['flatviewlist_per_page']) ? intval($_GET['flatviewlist_per_page']) : '';
        $offset = isset($_GET['offset']) ? $_GET['offset'] : '0';

        $header .= '<a href="' . api_get_self() . '?'.api_get_cidreq().'&export_format=csv&export_report=export_report&selectcat=' . $catobj->get_id() . '">' .
            Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), '', ICON_SIZE_MEDIUM) . '</a>';
        $header .= '<a href="' . api_get_self() . '?'.api_get_cidreq().'&export_format=xls&export_report=export_report&selectcat=' . $catobj->get_id() . '">' .
            Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), '', ICON_SIZE_MEDIUM) . '</a>';
        $header .= '<a href="' . api_get_self() . '?'.api_get_cidreq().'&export_format=doc&export_report=export_report&selectcat=' . $catobj->get_id() . '">' .
            Display::return_icon('export_doc.png', get_lang('ExportAsDOC'), '', ICON_SIZE_MEDIUM) . '</a>';
        $header .= '<a href="' . api_get_self() . '?'.api_get_cidreq().'&print=&selectcat=' . $catobj->get_id() . '" target="_blank">' .
            Display::return_icon('printer.png', get_lang('Print'), '', ICON_SIZE_MEDIUM) . '</a>';
        $header .= '<a href="' . api_get_self() . '?'.api_get_cidreq().'&exportpdf=&selectcat=' . $catobj->get_id().'&offset='.$offset.'&flatviewlist_page_nr='.$pageNum.'&flatviewlist_per_page='.$perPage.'" >' .
            Display::return_icon('pdf.png', get_lang('ExportToPDF'), '', ICON_SIZE_MEDIUM) . '</a>';
        $header .= '</div>';
        echo $header;
    }

    /**
     * @param Category $catobj
     * @param $showtree
     * @param $selectcat
     * @param $is_course_admin
     * @param $is_platform_admin
     * @param $simple_search_form
     * @param bool $show_add_qualification
     * @param bool $show_add_link
     */
    public function display_header_gradebook_per_gradebook($catobj, $showtree, $selectcat, $is_course_admin, $is_platform_admin, $simple_search_form, $show_add_qualification = true, $show_add_link = true)
    {
        // Student
        $status = CourseManager::get_user_in_course_status(api_get_user_id(), api_get_course_id());
        $objcat = new Category();
        $course_id = CourseManager::get_course_by_category($selectcat);
        $message_resource = $objcat->show_message_resource_delete($course_id);

        if (!$is_course_admin && $status <> 1 && $selectcat <> 0) {
            $user_id = api_get_user_id();
            $user = api_get_user_info($user_id);

            $catcourse = Category :: load($catobj->get_id());
            $scoredisplay = ScoreDisplay :: instance();
            $scorecourse = $catcourse[0]->calc_score($user_id);

            // generating the total score for a course
            $allevals = $catcourse[0]->get_evaluations($user_id, true);
            $alllinks = $catcourse[0]->get_links($user_id, true);
            $evals_links = array_merge($allevals, $alllinks);
            $item_value = 0;
            $item_total = 0;
            for ($count = 0; $count < count($evals_links); $count++) {
                $item = $evals_links[$count];
                $score = $item->calc_score($user_id);
                $my_score_denom = ($score[1] == 0) ? 1 : $score[1];
                $item_value+=$score[0] / $my_score_denom * $item->get_weight();
                $item_total+=$item->get_weight();
            }
            $item_value = number_format($item_value, 2, '.', ' ');
            $total_score = array($item_value, $item_total);
            $scorecourse_display = $scoredisplay->display_score($total_score, SCORE_DIV_PERCENT);

            $cattotal = Category :: load(0);
            $scoretotal = $cattotal[0]->calc_score(api_get_user_id());
            $scoretotal_display = (isset($scoretotal) ? $scoredisplay->display_score($scoretotal, SCORE_PERCENT) : get_lang('NoResultsAvailable'));
            $scoreinfo = get_lang('StatsStudent') . ' :<b> ' . api_get_person_name($user['firstname'], $user['lastname']) . '</b><br />';
            if ((!$catobj->get_id() == '0') && (!isset($_GET['studentoverview'])) && (!isset($_GET['search']))) {
                $scoreinfo.= '<h2>' . get_lang('Total') . ' : ' . $scorecourse_display . '</h2>';
            }
            Display :: display_normal_message($scoreinfo, false);
        }

        // show navigation tree and buttons?
        $header = '<div class="actions"><table border=0>';
        if (($showtree == '1') || (isset($_GET['studentoverview']))) {
            $header .= '<tr>';
            if (!$selectcat == '0') {
                $header .= '<td style=" "><a href="' . api_get_self() . '?selectcat=' . $catobj->get_parent_id() . '">' . Display::return_icon('back.png', get_lang('BackTo') . ' ' . get_lang('RootCat'), '', ICON_SIZE_MEDIUM) . '</a></td>';
            }
            $header .= '<td style=" ">' . get_lang('CurrentCategory') . '</td>' .
                    '<td style=" "><form name="selector"><select name="selectcat" onchange="document.selector.submit()">';
            $cats = Category :: load();

            $tree = $cats[0]->get_tree();
            unset($cats);

            foreach ($tree as $cat) {
                for ($i = 0; $i < $cat[2]; $i++) {
                    $line .= '&mdash;';
                }
                $line = isset($line) ? $line : '';
                if (isset($_GET['selectcat']) && $_GET['selectcat'] == $cat[0]) {
                    $header .= '<option selected value=' . $cat[0] . '>' . $line . ' ' . $cat[1] . '</option>';
                } else {
                    $header .= '<option value=' . $cat[0] . '>' . $line . ' ' . $cat[1] . '</option>';
                }
                $line = '';
            }
            $header .= '</select></form></td>';
            if (!empty($simple_search_form) && $message_resource === false) {
                $header .= '<td style="vertical-align: top;">' . $simple_search_form->toHtml() . '</td>';
            } else {
                $header .= '<td></td>';
            }

            if ($is_course_admin && $message_resource === false && $_GET['selectcat'] != 0) {

            } elseif (!(isset($_GET['studentoverview']))) {

            } else {
                $header .= '<td style="vertical-align: top;"><a href="' . api_get_self() . '?' . api_get_cidreq() . '&studentoverview=&exportpdf=&selectcat=' . $catobj->get_id() . '" target="_blank">
                '.Display::return_icon('pdf.png', get_lang('ExportPDF'), [], ICON_SIZE_MEDIUM).'
                ' . get_lang('ExportPDF') . '</a>';
            }
            $header .= '</td></tr>';
        }
        $header.='</table></div>';

        // for course admin & platform admin add item buttons are added to the header
        $header .= '<div class="actions">';

        $my_category = $catobj->shows_all_information_an_category($catobj->get_id());
        $user_id = api_get_user_id();
        $course_code = $my_category->getCourse()->getCode();
        $courseInfo = api_get_course_info($course_code);
        $courseId = $courseInfo['real_id'];

        $status_user = api_get_status_of_user_in_course($user_id, $courseId);
        if (api_is_allowed_to_edit(null, true)) {
            if ($selectcat == '0') {

            } else {

                $my_category = $catobj->shows_all_information_an_category($catobj->get_id());
                $my_api_cidreq = api_get_cidreq();
                if ($my_api_cidreq == '') {
                    $my_api_cidreq = 'cidReq=' . $my_category->getCourse()->getcode();
                }

                if (!$message_resource) {
                    $myname = $catobj->shows_all_information_an_category($catobj->get_id());

                    $my_course_id = api_get_course_id();
                    $my_file = substr($_SESSION['gradebook_dest'], 0, 5);

                    $header .= '<td style="vertical-align: top;"><a href="gradebook_flatview.php?' . $my_api_cidreq . '&selectcat=' . $catobj->get_id() . '">' .
                        Display::return_icon('stats.png', get_lang('FlatView'), '', ICON_SIZE_MEDIUM) . '</a>';
                    $header .= '<td style="vertical-align: top;"><a href="gradebook_display_certificate.php?' . $my_api_cidreq . '&amp;cat_id=' . (int) $_GET['selectcat'] . '">' .
                        Display::return_icon('certificate_list.png', get_lang('GradebookSeeListOfStudentsCertificates'), '', ICON_SIZE_MEDIUM) . '</a>';

                    $visibility_icon = ($catobj->is_visible() == 0) ? 'invisible' : 'visible';
                    $visibility_command = ($catobj->is_visible() == 0) ? 'set_visible' : 'set_invisible';

                    //Right icons
                    $modify_icons = '<a href="gradebook_edit_cat.php?editcat=' . $catobj->get_id() . '&cidReq=' . $catobj->get_course_code() . '&id_session='.$catobj->get_session_id(). '">' . Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_MEDIUM) . '</a>';
                    if ($catobj->get_name() != api_get_course_id()) {
                        $modify_icons .= '&nbsp;<a  href="' . api_get_self() . '?deletecat=' . $catobj->get_id() . '&amp;selectcat=0&amp;cidReq=' . $catobj->get_course_code() . '" onclick="return confirmation();">' . Display::return_icon('delete.png', get_lang('DeleteAll'), '', ICON_SIZE_MEDIUM) . '</a>';
                    }
                    $header .= Display::div($modify_icons, array('class' => 'right'));
                }
            }
        } elseif (isset($_GET['search'])) {
            $header .= '<b>' . get_lang('SearchResults') . ' :</b>';
        }
        $header .= '</div>';
        echo $header;
    }

    /**
     * Displays the header for the gradebook containing the navigation tree and links
     * @param Category $catobj
     * @param int $showtree '1' will show the browse tree and naviation buttons
     * @param boolean $is_course_admin
     * @param boolean $is_platform_admin
     * @param boolean Whether to show or not the link to add a new qualification
     * (we hide it in case of the course-embedded tool where we have only one
     * calification per course or session)
     * @param boolean Whether to show or not the link to add a new item inside
     * the qualification (we hide it in case of the course-embedded tool
     * where we have only one calification per course or session)
     * @return void Everything is printed on screen upon closing
     */
    static function header(
        $catobj,
        $showtree,
        $selectcat,
        $is_course_admin,
        $is_platform_admin,
        $simple_search_form,
        $show_add_qualification = true,
        $show_add_link = true,
        $certificateLinkInfo = null
    ) {

        $userId = api_get_user_id();
        $courseCode = api_get_course_id();
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        // Student.
        $status = CourseManager::get_user_in_course_status($userId, $courseCode);

        if (!empty($sessionId)) {
            $sessionStatus = SessionManager::get_user_status_in_course_session(
                $userId,
                $courseId,
                $sessionId
            );
        }

        $objcat = new Category();
        $course_id = CourseManager::get_course_by_category($selectcat);
        $message_resource = $objcat->show_message_resource_delete($course_id);
        $grade_model_id = $catobj->get_grade_model_id();
        $header = null;

        //@todo move these in a function
        $sum_categories_weight_array = array();

        if (isset($catobj) && !empty($catobj)) {
            $categories = Category::load(
                null,
                null,
                null,
                $catobj->get_id(),
                null,
                $sessionId
            );

            if (!empty($categories)) {
                foreach ($categories as $category) {
                    $sum_categories_weight_array[$category->get_id()] = $category->get_weight();
                }
            } else {
                $sum_categories_weight_array[$catobj->get_id()] = $catobj->get_weight();
            }
        }

        if (!$is_course_admin && ($status <> 1 || $sessionStatus == 0) && $selectcat <> 0) {

            $catcourse = Category::load($catobj->get_id());
            /** @var Category $category */
            $category = $catcourse[0];
            $main_weight = $category->get_weight();
            $scoredisplay = ScoreDisplay :: instance();
            $allevals = $category->get_evaluations($userId, true);
            $alllinks = $category->get_links($userId, true);

            $allEvalsLinks = array_merge($allevals, $alllinks);

            $item_value_total = 0;
            $scoreinfo = null;

            for ($count = 0; $count < count($allEvalsLinks); $count++) {
                $item = $allEvalsLinks[$count];
                $score = $item->calc_score($userId);
                if (!empty($score)) {
                    $divide = $score[1] == 0 ? 1 : $score[1];
                    $item_value = $score[0] / $divide * $item->get_weight();
                    $item_value_total += $item_value;
                }
            }

            $item_total = $main_weight;
            $total_score = array($item_value_total, $item_total);
            $scorecourse_display = $scoredisplay->display_score($total_score, SCORE_DIV_PERCENT);
            if ((!$catobj->get_id() == '0') && (!isset($_GET['studentoverview'])) && (!isset($_GET['search']))) {
                $aditionalButtons = null;
                if (!empty($certificateLinkInfo)) {
                    $aditionalButtons .= '<div class="btn-group pull-right">';
                    $aditionalButtons .= isset($certificateLinkInfo['certificate_link']) ? $certificateLinkInfo['certificate_link'] : '';
                    $aditionalButtons .= isset($certificateLinkInfo['badge_link']) ? $certificateLinkInfo['badge_link'] : '';
                    $aditionalButtons .= '</div>';
                }
                $scoreinfo .= '<strong>' . sprintf(get_lang('TotalX'), $scorecourse_display . $aditionalButtons). '</strong>';

            }
            Display :: display_normal_message($scoreinfo, false);
        }

        // show navigation tree and buttons?
        if (($showtree == '1') || (isset($_GET['studentoverview']))) {
            $header = '<div class="actions"><table>';
            $header .= '<tr>';
            if (!$selectcat == '0') {
                $header .= '<td><a href="' . api_get_self() . '?selectcat=' . $catobj->get_parent_id() . '">' .
                    Display::return_icon('back.png', get_lang('BackTo') . ' ' . get_lang('RootCat'), '', ICON_SIZE_MEDIUM) . '</a></td>';
            }
            $header .= '<td>' . get_lang('CurrentCategory') . '</td>' .
                    '<td><form name="selector"><select name="selectcat" onchange="document.selector.submit()">';
            $cats = Category :: load();

            $tree = $cats[0]->get_tree();
            unset($cats);
            $line = null;
            foreach ($tree as $cat) {
                for ($i = 0; $i < $cat[2]; $i++) {
                    $line .= '&mdash;';
                }
                $line = isset($line) ? $line : '';
                if (isset($_GET['selectcat']) && $_GET['selectcat'] == $cat[0]) {
                    $header .= '<option selected value=' . $cat[0] . '>' . $line . ' ' . $cat[1] . '</option>';
                } else {
                    $header .= '<option value=' . $cat[0] . '>' . $line . ' ' . $cat[1] . '</option>';
                }
                $line = '';
            }
            $header .= '</select></form></td>';
            if (!empty($simple_search_form) && $message_resource === false) {
                $header .= '<td style="vertical-align: top;">' . $simple_search_form->toHtml() . '</td>';
            } else {
                $header .= '<td></td>';
            }
            if ($is_course_admin &&
                $message_resource === false &&
                isset($_GET['selectcat']) && $_GET['selectcat'] != 0
            ) {
            } elseif (!(isset($_GET['studentoverview']))) {

            } else {
                $header .= '<td style="vertical-align: top;"><a href="' . api_get_self() . '?' . api_get_cidreq() . '&studentoverview=&exportpdf=&selectcat=' . $catobj->get_id() . '" target="_blank">
							 '.Display::return_icon('pdf.png', get_lang('ExportPDF'), [], ICON_SIZE_MEDIUM).'
							' . get_lang('ExportPDF') . '</a>';
            }
            $header .= '</td></tr>';
            $header .= '</table></div>';
        }

        // for course admin & platform admin add item buttons are added to the header

        $actionsLeft = '';
        $my_category = $catobj->shows_all_information_an_category($catobj->get_id());
        $user_id = api_get_user_id();
        $my_api_cidreq = api_get_cidreq();

        if (api_is_allowed_to_edit(null, true)) {
            if (empty($grade_model_id) || $grade_model_id == -1) {
                $actionsLeft .= '<a href="gradebook_add_cat.php?' . api_get_cidreq() . '&selectcat=' . $catobj->get_id() . '">' .
                    Display::return_icon('new_folder.png', get_lang('AddGradebook'), array(), ICON_SIZE_MEDIUM) . '</a></td>';
            }
            if ($selectcat == '0') {

            } else {
                $my_category = $catobj->shows_all_information_an_category($catobj->get_id());
                if ($my_api_cidreq == '') {
                    $my_api_cidreq = 'cidReq=' . $my_category->getCourse()->getCode();
                }
                if ($show_add_link && !$message_resource) {
                   $actionsLeft .= '<a href="gradebook_add_eval.php?' . $my_api_cidreq . '&selectcat=' . $catobj->get_id() . '" >' .
                        Display::return_icon('new_evaluation.png', get_lang('NewEvaluation'), '', ICON_SIZE_MEDIUM) . '</a>';
                    $cats = Category :: load($selectcat);

                    if ($cats[0]->get_course_code() != null && !$message_resource) {
                        $actionsLeft .= '<a href="gradebook_add_link.php?' . $my_api_cidreq . '&selectcat=' . $catobj->get_id() . '">' .
                            Display::return_icon('new_online_evaluation.png', get_lang('MakeLink'), '', ICON_SIZE_MEDIUM) . '</a>';
                    } else {
                        $actionsLeft .= '<a href="gradebook_add_link_select_course.php?' . $my_api_cidreq . '&selectcat=' . $catobj->get_id() . '">' .
                            Display::return_icon('new_online_evaluation.png', get_lang('MakeLink'), '', ICON_SIZE_MEDIUM) . '</a>';
                    }
                }

                if (!$message_resource) {
                    $actionsLeft .= '<a href="gradebook_flatview.php?' . $my_api_cidreq . '&selectcat=' . $catobj->get_id() . '">' .
                        Display::return_icon('stats.png', get_lang('FlatView'), '', ICON_SIZE_MEDIUM) . '</a>';

                    if ($my_category->getGenerateCertificates() == 1) {
                        $actionsLeft .= Display::url(
                                Display::return_icon(
                                        'certificate_list.png',
                                        get_lang('GradebookSeeListOfStudentsCertificates'),
                                        '',
                                        ICON_SIZE_MEDIUM
                                        ),
                                "gradebook_display_certificate.php?$my_api_cidreq&cat_id=" . intval($_GET['selectcat'])
                            );
                    }

                    $actionsLeft .= Display::url(
                            Display::return_icon(
                                    'user.png',
                                    get_lang('GradebookListOfStudentsReports'),
                                    '',
                                    ICON_SIZE_MEDIUM
                                    ),
                            "gradebook_display_summary.php?$my_api_cidreq&selectcat=" . intval($_GET['selectcat'])
                        );


                    // Right icons
                    $actionsRight = '<a href="gradebook_edit_cat.php?editcat=' . $catobj->get_id() . '&amp;cidReq=' . $catobj->get_course_code() . '&id_session='.$catobj->get_session_id(). '">' .
                        Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_MEDIUM) . '</a>';
                    $actionsRight .= '<a href="../document/document.php?curdirpath=/certificates&' . $my_api_cidreq . '&origin=gradebook&selectcat=' . $catobj->get_id() . '">' .
                            Display::return_icon('certificate.png', get_lang('AttachCertificate'), '', ICON_SIZE_MEDIUM) . '</a>';

                    if (empty($categories)) {
                        $actionsRight .= '<a href="gradebook_edit_all.php?id_session=' . api_get_session_id() . '&amp;' . $my_api_cidreq . '&selectcat=' . $catobj->get_id() . '">' .
                            Display::return_icon('percentage.png', get_lang('EditAllWeights'), '', ICON_SIZE_MEDIUM) . '</a>';
                    }
                    $score_display_custom = api_get_setting(
                        'gradebook.gradebook_score_display_custom'
                    );
                    if (api_get_setting(
                            'gradebook.teachers_can_change_score_settings'
                        ) == 'true' && $score_display_custom !== 'false' && $score_display_custom['my_display_custom'] == 'true'
                    ) {
                        $actionsRight .= '<a href="gradebook_scoring_system.php?' . $my_api_cidreq . '&selectcat=' . $catobj->get_id() . '">' .
                            Display::return_icon('ranking.png', get_lang('ScoreEdit'), '', ICON_SIZE_MEDIUM) . '</a>';
                    }

                }
            }
        } elseif (isset($_GET['search'])) {
            echo $header = '<b>' . get_lang('SearchResults') . ' :</b>';
        }

        $isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
            api_get_user_id(),
            api_get_course_info()
        );

        if ($isDrhOfCourse) {
            $$actionsLeft .= '<a href="gradebook_flatview.php?' . $my_api_cidreq . '&selectcat=' . $catobj->get_id() . '">' .
                Display::return_icon('stats.png', get_lang('FlatView'), '', ICON_SIZE_MEDIUM) . '</a>';
        }

        if (api_is_allowed_to_edit(null, true)){
            echo $toolbar = Display::toolbarAction('gradebook-actions', array(0 => $actionsLeft, 1 => $actionsRight ));
        }

        if (api_is_allowed_to_edit(null, true)) {
            $weight = intval($catobj->get_weight()) > 0 ? $catobj->get_weight() : 0;
            $weight = '<strong>' . get_lang('TotalWeight') . ' : </strong>' . $weight;

            $min_certification = (intval($catobj->get_certificate_min_score() > 0) ? $catobj->get_certificate_min_score() : 0);
            $min_certification = get_lang('CertificateMinScore') . ' : ' . $min_certification;
            $edit_icon = '<a href="gradebook_edit_cat.php?editcat=' . $catobj->get_id() . '&amp;cidReq=' . $catobj->get_course_code() . '&id_session='.$catobj->get_session_id(). '">' .
                Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL) . '</a>';
            //$msg = Display::tag('h3', $weight.' - '.$min_certification);
            $msg = $weight . ' - ' . $min_certification . $edit_icon;
            //@todo show description
            $description = (($catobj->get_description() == "" || is_null($catobj->get_description())) ? '' : '<strong>' . get_lang('GradebookDescriptionLog') . '</strong>' . ': ' . $catobj->get_description());
            Display::display_normal_message($msg, false);
            if (!empty($description)) {
                echo Display::div($description, array());
            }
        }
    }

    /**
     * @param Category $catobj
     * @param $is_course_admin
     * @param $is_platform_admin
     * @param $simple_search_form
     * @param bool $show_add_qualification
     * @param bool $show_add_link
     */
    public function display_reduce_header_gradebook($catobj, $is_course_admin, $is_platform_admin, $simple_search_form, $show_add_qualification = true, $show_add_link = true)
    {
        //student
        if (!$is_course_admin) {
            $user = api_get_user_info(api_get_user_id());
            $catcourse = Category :: load($catobj->get_id());
            $scoredisplay = ScoreDisplay :: instance();
            $scorecourse = $catcourse[0]->calc_score(api_get_user_id());
            $scorecourse_display = (isset($scorecourse) ? $scoredisplay->display_score($scorecourse, SCORE_AVERAGE) : get_lang('NoResultsAvailable'));
            $cattotal = Category :: load(0);
            $scoretotal = $cattotal[0]->calc_score(api_get_user_id());
            $scoretotal_display = (isset($scoretotal) ? $scoredisplay->display_score($scoretotal, SCORE_PERCENT) : get_lang('NoResultsAvailable'));
            $scoreinfo = get_lang('StatsStudent') . ' :<b> ' . $user['complete_name']. '</b><br />';
            if ((!$catobj->get_id() == '0') && (!isset($_GET['studentoverview'])) && (!isset($_GET['search'])))
                $scoreinfo.= '<br />' . get_lang('TotalForThisCategory') . ' : <b>' . $scorecourse_display . '</b>';
            $scoreinfo.= '<br />' . get_lang('Total') . ' : <b>' . $scoretotal_display . '</b>';
            Display :: display_normal_message($scoreinfo, false);
        }
        // show navigation tree and buttons?
        $header = '<div class="actions">';

        if ($is_course_admin) {
            $header .= '<a href="gradebook_flatview.php?' . api_get_cidreq() . '&selectcat=' . $catobj->get_id() . '">' . Display::return_icon('stats.png', get_lang('FlatView'), '', ICON_SIZE_MEDIUM) . '</a>';
            $header .= '<a href="gradebook_scoring_system.php?' . api_get_cidreq() . '&selectcat=' . $catobj->get_id() . '">' . Display::return_icon('settings.png', get_lang('ScoreEdit'), '', ICON_SIZE_MEDIUM) . '</a>';
        } elseif (!(isset($_GET['studentoverview']))) {
            $header .= '<a href="' . api_get_self() . '?' . api_get_cidreq() . '&studentoverview=&selectcat=' . $catobj->get_id() . '">' . Display::return_icon('view_list.gif', get_lang('FlatView')) . ' ' . get_lang('FlatView') . '</a>';
        } else {
            $header .= '<a href="' . api_get_self() . '?' . api_get_cidreq() . '&studentoverview=&exportpdf=&selectcat=' . $catobj->get_id() . '" target="_blank">' . Display::return_icon('pdf.png', get_lang('ExportPDF'), '', ICON_SIZE_MEDIUM) . '</a>';
        }
        $header.='</div>';
        echo $header;
    }

    /**
     * @param int $userid
     */
    public static function display_header_user($userid)
    {
        $select_cat = intval($_GET['selectcat']);
        $user_id = $userid;
        $user = api_get_user_info($user_id);

        $catcourse = Category :: load($select_cat);
        $scoredisplay = ScoreDisplay :: instance();
        $scorecourse = $catcourse[0]->calc_score($user_id);

        // generating the total score for a course
        $allevals = $catcourse[0]->get_evaluations($user_id, true);
        $alllinks = $catcourse[0]->get_links($user_id, true);
        $evals_links = array_merge($allevals, $alllinks);
        $item_value = 0;
        $item_total = 0;
        for ($count = 0; $count < count($evals_links); $count++) {
            $item = $evals_links[$count];
            $score = $item->calc_score($user_id);
            $my_score_denom = ($score[1] == 0) ? 1 : $score[1];
            $item_value+=$score[0] / $my_score_denom * $item->get_weight();
            $item_total+=$item->get_weight();
            //$row[] = $scoredisplay->display_score($score,SCORE_DIV_PERCENT);
        }
        $item_value = number_format($item_value, 2, '.', ' ');
        $total_score = array($item_value, $item_total);
        $scorecourse_display = $scoredisplay->display_score($total_score, SCORE_DIV_PERCENT);

        //$scorecourse_display = (isset($scorecourse) ? $scoredisplay->display_score($scorecourse,SCORE_AVERAGE) : get_lang('NoResultsAvailable'));
        $cattotal = Category :: load(0);
        $scoretotal = $cattotal[0]->calc_score($user_id);
        $scoretotal_display = (isset($scoretotal) ? $scoredisplay->display_score($scoretotal, SCORE_PERCENT) : get_lang('NoResultsAvailable'));

        $imageUrl = UserManager::getUserPicture($userid);

        $info = '<div class="row"><div class="col-md-3">';
        $info .= '<div class="thumbnail"><img src="' . $imageUrl . '" /></div>';
        $info .= '</div>';
        $info .= '<div class="col-md-6">';
        $info .= get_lang('Name') . ' :  <a target="_blank" href="' . api_get_path(WEB_CODE_PATH) . 'social/profile.php?u=' . $userid . '"> ' .
            $user['complete_name'] . '</a><br />';

        if (api_get_setting('display.show_email_addresses') == 'true') {
            $info .= get_lang('Email') . ' : <a href="mailto:' . $user['email'] . '">' . $user['email'] . '</a><br />';
        }

        $info .= get_lang('TotalUser') . ' : <b>' . $scorecourse_display . '</b>';
        $info .= '</div>';
        $info .= '</div>';

        echo $info;
    }

}
