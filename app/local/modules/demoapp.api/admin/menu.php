<?php

use Bitrix\Main\Localization\Loc;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;

Loc::loadMessages(__DIR__ . '/products.php');

return [
    'parent_menu' => 'global_menu_content',
    'sort'        => 1,
    'url'         => '',
    'text'        => Loc::getMessage('parent_menu_text'),
    'module_id'   => 'demoapp.api',
    'icon'        => 'sale_menu_icon_statistic',
    'items_id'    => 'naturasiberica_report_list',
    'items'       => [
        [
            'parent_menu' => 'naturasiberica_report_list',
            'sort'        => 1,
            'url'         => 'ns_api_products.php?lang=' . LANGUAGE_ID . '&table_id=' . ConstantEntityInterface::IBLOCK_CATALOG,
            'text'        => Loc::getMessage('products_report_page_title'),
            'module_id'   => 'demoapp.api',
            'icon'        => 'sale_menu_icon_statistic',
        ],
        [
            'parent_menu' => 'naturasiberica_report_list',
            'sort'        => 2,
            'url'         => 'ns_api_order_products.php?lang=' . LANGUAGE_ID,
            'text'        => 'Отчёт по заказанным товарам',
            'module_id'   => 'demoapp.api',
            'icon'        => 'sale_menu_icon_crm',
        ],
    ],
];
