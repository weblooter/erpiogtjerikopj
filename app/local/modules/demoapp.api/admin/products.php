<?php

use Bitrix\Main\Context;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\UI\Filter\Options as Filter;
use Bitrix\Main\Grid\Options as Grid;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Report\Repositories\ProductsRepository;
use NaturaSiberica\Api\Report\Services\ExportService;
use NaturaSiberica\Api\Report\Services\FilterService;
use NaturaSiberica\Api\Report\Services\GridService;

/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

$APPLICATION->SetTitle(Loc::getMessage('products_report_page_title'));

Extension::load(["ui.buttons", "ui.buttons.icons", 'catalog.iblock-product-list', /*'catalog.product-selector',*/ 'ui.forms']);

$request    = Context::getCurrent()->getRequest();
$tableId    = $request->get('table_id') ?? ConstantEntityInterface::IBLOCK_CATALOG;
$repository = new ProductsRepository($tableId);

$repository->init();

$filterService = new FilterService($repository, $tableId);
$gridService   = new GridService($repository, $tableId);
$filter        = new Filter($tableId);
$grid          = new Grid($tableId);
$sort          = $grid->GetSorting(['sort' => ['ID' => 'DESC'], 'vars' => ['by' => 'by', 'order' => 'order']]);
$navParams     = $grid->GetNavParams();
$navObject     = new PageNavigation('page');
$navObject->allowAllRecords(true);
$navObject->setPageSize($navParams['nPageSize']);
$navObject->initFromUri();

$filterData     = $filter->getFilter([]);
$preparedFilter = $filterService->prepareParamsFromFilter($filterData);

if ($request->get('mode') === 'excel') {
    $csvExportService = new ExportService($gridService);
    $csvExportService->export($preparedFilter);
}

$rows = $gridService->prepareRows($preparedFilter, $sort['sort'], (int)$navObject->getLimit(), (int)$navObject->getOffset());

$navObject->setRecordCount($rows['pagination']['total']);
$gridService->setNavObject($navObject);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
?>
    <div class="adm-toolbar-panel-container">
        <div class="adm-toolbar-panel-flexible-space">
            <?php
                $APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', $filterService->getFilterParams());
            ?>
        </div>
        <div class="adm-toolbar-panel-align-right">
            <button class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-icon-setting" onclick="
				BX.adminList.ShowMenu(this, [
				{
                    'LINK':'/bitrix/admin/ns_api_products.php?lang=<?=LANGUAGE_ID?>&table_id=<?=ConstantEntityInterface::IBLOCK_CATALOG?>&mode=excel',
                    'GLOBAL_ICON':'adm-menu-download',
                    'TEXT':'EXCEL',
                    'TITLE':'Выгрузить данные из списка в Excel',
                    'DEFAULT': false
				}
				]);return false;"
            >
            </button>
        </div>
    </div>
<?php

$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', $gridService->getGridOptions());

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
