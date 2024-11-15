<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

global $APPLICATION;

if ($APPLICATION::GetGroupRight('userstory.i18n') === 'D') {
    return false;
}

if (!Loader::includeModule('userstory.i18n')) {
    return false;
}

$aMenu[] = [
    'parent_menu' => 'global_menu_settings',
    'section'     => 'i18n',
    'sort'        => 50,
    'text'        => Loc::getMessage('USERSTORY_I18N__MENU'),
    'title'       => Loc::getMessage('USERSTORY_I18N__MENU_ALT'),
    'icon'        => 'translate_menu_icon',
	'page_icon'   => 'translate_page_icon',
    'items_id'    => 'menu_i18n',
    'items'       => [
        [
            'text'  => Loc::getMessage('USERSTORY_I18N__MENU_ITEM_NEW_VERSION'),
	        'url'   => 'us_i18n_new_version.php?lang=' . LANGUAGE_ID,
	        'title' => Loc::getMessage('USERSTORY_I18N__MENU_ITEM_NEW_VERSION_ALT')
        ],
        [
            'text'  => Loc::getMessage('USERSTORY_I18N__MENU_ITEM_VERSIONS'),
	        'url'   => 'us_i18n_versions.php?lang=' . LANGUAGE_ID,
	        'title' => Loc::getMessage('USERSTORY_I18N__MENU_ITEM_VERSIONS_ALT')
        ]
    ]
];

return $aMenu;
