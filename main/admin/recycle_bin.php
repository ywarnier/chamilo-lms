<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;
use Doctrine\Common\Collections\Criteria;
use Knp\Component\Pager\Paginator;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CItemProperty;

/**
 * This script allows a user to search specific documents and to restore them
 * This requires an action to remove the _DELETED_ marker on the document
 * path as well as to update c_item_property by changing visibility to 1 and
 * the lastedit_type to DocumentRestored.
 * The report text should give a path to the restored file's directory.
 *  @package chamilo.admin
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

$form = new FormValidator('deleted_resource', 'get');
$form->addHeader(get_lang('DeletedResource'));
$form->addText('id', get_lang('Id'), false);
$form->addText('title', get_lang('Title'), false);
$form->addText('path', get_lang('Path'), false);
$form->addHidden('form_sent', 1);
$form->addButtonSearch(get_lang('Search'));

$questions = [];
$pagination = '';
$formSent = isset($_REQUEST['form_sent']) ? (int) $_REQUEST['form_sent'] : 0;
$length = 20;
$questionCount = 0;

if ($formSent) {
    $id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : '';
    $path = isset($_REQUEST['path']) ? $_REQUEST['path'] : '';
    $title = isset($_REQUEST['title']) ? $_REQUEST['title'] : '';
    $page = isset($_GET['page']) && !empty($_GET['page']) ? (int) $_GET['page'] : 1;

    $em = Database::getManager();
    $repo = $em->getRepository('ChamiloCourseBundle:CDocument');
    // Search a document with those properties
    $criteria = new Criteria();
    if (!empty($id)) {
        $criteria->where($criteria->expr()->eq('iid', $id));
    }

    if (!empty($path)) {
        $criteria->andWhere($criteria->expr()->contains('path', "%$path%_DELETED_%"));
    } else {
        $criteria->andWhere($criteria->expr()->contains('path', "%_DELETED_%"));
    }
    if (!empty($title)) {
        $criteria->orWhere($criteria->expr()->contains('title', "%$title%"));
    }
    $deletedResources = $repo->matching($criteria);

    /*
    // To restore it, we will also need to find its properties in the c_item_property table
    $repo = $em->getRepository('ChamiloCourseBundle:CItemProperty');
    $criteria = new Criteria();
    $criteria->where($criteria->expr()->eq('tool', TOOL_DOCUMENT));
    $criteria->andWhere($criteria->expr()->eq('visibility', 2));
    $criteria->andWhere($criteria->expr()->eq('', 2));
    if (!empty($id)) {
        $criteria->andWhere($criteria->expr()->eq('ref', $id));
    }
    $deletedResourcesItem = $repo->matching($criteria);
    */

    if (empty($id)) {
        $id = '';
    }
    $params = [
        'id' => $id,
        'title' => Security::remove_XSS($title),
        'path' => Security::remove_XSS($path),
        'form_sent' => 1,
    ];
    $url = api_get_self().'?'.http_build_query($params);

    $form->setDefaults($params);

    $resourcesCount = count($deletedResources);

    $paginator = new Paginator();
    $pagination = $paginator->paginate($deletedResources, $page, $length);
    $pagination->setItemNumberPerPage($length);
    $pagination->setCurrentPageNumber($page);
    $pagination->renderer = function ($data) use ($url) {
        $render = '<ul class="pagination">';
        for ($i = 1; $i <= $data['pageCount']; $i++) {
            $page = (int) $i;
            $pageContent = '<li><a href="'.$url.'&page='.$page.'">'.$page.'</a></li>';
            if ($data['current'] == $page) {
                $pageContent = '<li class="active"><a href="#" >'.$page.'</a></li>';
            }
            $render .= $pageContent;
        }
        $render .= '</ul>';

        return $render;
    };

    if ($pagination) {
        $urlExercise = api_get_path(WEB_CODE_PATH).'exercise/admin.php?';
        $exerciseUrl = api_get_path(WEB_CODE_PATH).'exercise/exercise.php?';

        /** @var CDocument $resource */
        foreach ($pagination as $resource) {
            $courseId = $resource->getCId();
            $courseInfo = api_get_course_info_by_id($courseId);
            $courseCode = $courseInfo['code'];
            $resource->courseCode = $courseCode;
            // Creating empty exercise
            $document = new CDocument();
            $resource->resourceData = 'test data<br />';
            $resource->resourceData .= Display::url(
                Display::return_icon('recycle_bin.png', get_lang('Restore')),
                $urlExercise.http_build_query([
                    'cidReq' => $courseCode,
                    'id_session' => $resource->getSessionId(),
                    'documentId' => $id,
                    'action' => 'restore',
                ])
            );
            $documentId = 0;
        }
    }
}

$formContent = $form->returnForm();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
switch ($action) {
    case 'delete':
        $resourceId = isset($_REQUEST['resourceId']) ? $_REQUEST['resourceId'] : '';
        $courseId = isset($_REQUEST['courseId']) ? $_REQUEST['courseId'] : '';
        $courseInfo = api_get_course_info_by_id($courseId);

        $document = new CDocument($resourceId);
        if (!empty($document)) {
            $result = $document->delete();
            if ($result) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('Deleted').' #'.$resourceId.' - "'.$document->getTitle().'"'
                    )
                );
            }
        }

        header("Location: $url");
        exit;
        break;
}

$tpl = new Template(get_lang('Questions'));
$tpl->assign('form', $formContent);
$tpl->assign('pagination', $pagination);
$tpl->assign('pagination_length', $length);
$tpl->assign('resource_count', $resourcesCount);

$layout = $tpl->get_template('admin/recycle_bin.tpl');
$tpl->display($layout);
