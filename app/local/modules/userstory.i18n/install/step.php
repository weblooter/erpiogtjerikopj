<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * @var $APPLICATION
 */

if (!check_bitrix_sessid()) {
    return;
}

if ($exception = $APPLICATION->GetException()) {
    echo CAdminMessage::ShowMessage($exception->GetString());
} else {
    echo CAdminMessage::ShowNote(Loc::getMessage('USERSTORY_I18N__MODULE_INSTALL'));
}

?>

<form action="<?= $APPLICATION->GetCurPage() ?>">
    <input type="hidden" name="lang" value="<?= LANG ?>">
    <input type="submit" value="<?= Loc::getMessage('USERSTORY_I18N__STEP_SUBMIT_BACK') ?>">
</form>
