<?php
/* For license terms, see /license.txt */

/**
 * User Panel.
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$includeSessions = 'true' === $plugin->get('include_sessions');
$includeServices = 'true' === $plugin->get('include_services');

$userInfo = api_get_user_info();

$productTypes = $plugin->getProductTypes();
$saleStatuses = $plugin->getSaleStatuses();
$paymentTypes = $plugin->getPaymentTypes();

$sales = $plugin->getSaleListByUserId($userInfo['id']);

$saleList = [];

foreach ($sales as $sale) {
    if (1 == $sale['product_type']) {
        $saleList[] = [
            'id' => $sale['id'],
            'reference' => $sale['reference'],
            'date' => api_format_date($sale['date'], DATE_TIME_FORMAT_LONG_24H),
            'currency' => $sale['iso_code'],
            'price' => $sale['price'],
            'product_name' => $sale['product_name'],
            'product_type' => $productTypes[$sale['product_type']],
            'payment_type' => $paymentTypes[$sale['payment_type']],
        ];
    }
}

$toolbar = Display::toolbarButton(
    $plugin->get_lang('CourseListOnSale'),
    'course_catalog.php',
    'search-plus',
    'primary',
    ['title' => $plugin->get_lang('CourseListOnSale')]
);

$templateName = get_lang('TabsDashboard');
$tpl = new Template($templateName);
$tpl->assign('showing_courses', true);
$tpl->assign('sessions_are_included', $includeSessions);
$tpl->assign('services_are_included', $includeServices);
$tpl->assign('sale_list', $saleList);

$content = $tpl->fetch('BuyCourses/view/course_panel.tpl');

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$toolbar])
);
$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
