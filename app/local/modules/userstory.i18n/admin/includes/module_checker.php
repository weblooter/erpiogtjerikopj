<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

global $APPLICATION;
$errors = [];

if (!Loader::includeModule('userstory.i18n')) {
    $errors[] = Loc::getMessage('USERSTORY_I18N__MODULE_NOT_INSTALLED');
} elseif ($APPLICATION::GetGroupRight('userstory.i18n') === 'D') {
    $errors[] = Loc::getMessage('USERSTORY_I18N__ACCESS_DENIED');
}
