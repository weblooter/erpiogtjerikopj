<?php

use Bitrix\Main\Localization\Loc;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

global $APPLICATION;
$APPLICATION->SetTitle(Loc::getMessage('USERSTORY_I18N__TITLE'));

include __DIR__ . '/includes/module_checker.php';

if (!empty($errors)) {
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

    foreach ($errors as $error) {
        echo CAdminMessage::ShowMessage($error);
    }
    
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
} else {
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
    CUtil::InitJSCore(['jquery']);

    // TODO реализовать возможность дальнейшего управления переносом и синхронизацией сущностей 

    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
}
