<?php
/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\UI\PageNavigation;
use NaturaSiberica\Api\Report\Repositories\OrderProductRepository;
use NaturaSiberica\Api\Report\Services\GridService;

Extension::load(["ui.buttons", "ui.buttons.icons"]);
$APPLICATION->SetTitle('Отчет по купленным товарам');

$tableId = "sale_order_product_list";
$gridOptions = new GridOptions($tableId);
$repository = new OrderProductRepository();

$sort = ['DATE_INSERT' => 'desc'];
if (($_GET['grid_id'] ?? null) === $tableId) {
    if (isset($_GET['grid_action']) and $_GET['grid_action'] === 'sort') {
        $sort = [$_GET['by'] => $_GET['order']];
    }
}

$filterOption = new Bitrix\Main\UI\Filter\Options($tableId);
$filterData = $filterOption->getFilter([]);
$filter = ['!ORDER_ID' => false];
$filterList = [
    [
        "id" => "DATE_INSERT",
        'type' => 'date',
        "name" => 'По дате создания',
        "default" => true,
    ],
    [
        "id" => "ORDER_ID",
        'type' => 'text',
        "name" => 'По номеру заказа',
        "default" => false
    ],
    [
        "id" => "ORDER.STATUS_ID",
        'type' => 'list',
        'items' => OrderProductRepository::getOrderStatus(),
        "name" => 'Статус заказа',
        "default" => false
    ],
    [
        "id" => "NAME",
        'type' => 'text',
        "name" => 'Наименование товара',
        "default" => true
    ]
];

if (key_exists('DATE_INSERT_from', $filterData) && key_exists('DATE_INSERT_to', $filterData)) {
    $dateFrom = $filterData['DATE_INSERT_from'];
    $dateTo = $filterData['DATE_INSERT_to'];
    if($dateFrom && $dateTo) {
        $filter['>=DATE_INSERT'] = (new DateTime($dateFrom))->format('d.m.Y H:i:s');
        $filter['<=DATE_INSERT'] = (new DateTime($dateTo))->format('d.m.Y H:i:s');
    } elseif($dateFrom) {
        $filter['>=DATE_INSERT'] = (new DateTime($dateFrom))->format('d.m.Y H:i:s');
    } elseif($dateTo) {
        $filter['<=DATE_INSERT'] = (new DateTime($dateTo))->format('d.m.Y H:i:s');
    }
}

if (key_exists('ORDER_ID', $filterData)) {
    $filter['ORDER_ID'] = $filterData['ORDER_ID'];
}
if (key_exists('ORDER.STATUS_ID', $filterData)) {
    $filter['ORDER.STATUS_ID'] = $filterData['ORDER.STATUS_ID'];
}
if (key_exists('NAME', $filterData)) {
    $filter['NAME'] = $filterData['NAME'];
}

$arHeaders = OrderProductRepository::getHeaderGrid();
$totalCount = $repository->getTotalCount($filter);

$navParams = $gridOptions->GetNavParams();
$nav = new PageNavigation('request_list');
$nav->allowAllRecords(true)
    ->setRecordCount($totalCount)
    ->setPageSize($navParams['nPageSize'])
    ->initFromUri();

if(isset($_REQUEST['mode']) && ($_REQUEST['mode'] === 'csv')) {
    $repository->exportCSV($filter, $sort, $totalCount);
}

$products = (new OrderProductRepository())->getRows($filter, $nav->getLimit(), $nav->getOffset(), $sort);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
?>
    <div class="adm-toolbar-panel-container">
        <div class="adm-toolbar-panel-flexible-space">
            <?php
            $APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
                'FILTER_ID' => $tableId,
                'GRID_ID' => $tableId,
                'FILTER' => $filterList,
                'ENABLE_LIVE_SEARCH' => true,
                'ENABLE_LABEL' => true
            ]);
            ?>
        </div>
        <div class="adm-toolbar-panel-align-right">
            <button class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-icon-setting" onclick="
				BX.adminList.ShowMenu(this, [
				{
                    'LINK':'/bitrix/admin/ns_api_order_products.php?lang=ru&mode=csv',
                    'GLOBAL_ICON':'adm-menu-download',
                    'TEXT':'CSV',
                    'TITLE':'Выгрузить данные из списка в CSV'
				}
				]);"
            >
            </button>
        </div>
    </div>
<?php

$list = [];

foreach ($products as $product) {
    $urlParams = http_build_query(
        [
            'IBLOCK_ID' => 6,
            'type' => 'catalog',
            'ID' => $product['PRODUCT_ID'],
            'lang' => LANGUAGE_ID,
        ]
    );
    $list[] = [
        'data' => $product,
        'actions' => [
            [
                'text' => 'Просмотр',
                'default' => true,
                'onclick' => "document.location.href='/bitrix/admin/iblock_element_edit.php?{$urlParams}'"
            ]
        ]
    ];
}

$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
    'GRID_ID' => $tableId,
    'COLUMNS' => $arHeaders,
    'ROWS' => $list,
    'SHOW_ROW_CHECKBOXES' => false,
    'NAV_OBJECT' => $nav,
    'AJAX_MODE' => 'Y',
    'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
    'PAGE_SIZES' => GridService::PAGE_SIZES,
    'AJAX_OPTION_JUMP' => 'N',
    'SHOW_CHECK_ALL_CHECKBOXES' => false,
    'SHOW_ROW_ACTIONS_MENU' => true,
    'SHOW_GRID_SETTINGS_MENU' => true,
    'SHOW_NAVIGATION_PANEL' => true,
    'SHOW_PAGINATION' => true,
    'SHOW_SELECTED_COUNTER' => true,
    'SHOW_TOTAL_COUNTER' => true,
    'SHOW_PAGESIZE' => true,
    'SHOW_ACTION_PANEL' => true,
    'ALLOW_COLUMNS_SORT' => true,
    'ALLOW_COLUMNS_RESIZE' => true,
    'ALLOW_HORIZONTAL_SCROLL' => true,
    'ALLOW_SORT' => true,
    'ALLOW_PIN_HEADER' => true,
    'AJAX_OPTION_HISTORY' => 'N',
    'TOTAL_ROWS_COUNT_HTML' => '<span class="main-grid-panel-content-title">Всего:</span> <span class="main-grid-panel-content-text">' . $nav->getRecordCount() . '</span>',
]);
?>


<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
