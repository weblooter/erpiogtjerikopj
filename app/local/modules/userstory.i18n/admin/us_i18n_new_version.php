<?php

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Userstory\I18n\Exception\HelperException;
use Userstory\I18n\Export\HLBlockExportService;
use Userstory\I18n\Export\IblockExportService;
use Userstory\I18n\Helper\HLBlockHelper;
use Userstory\I18n\Helper\IblockHelper;
use Userstory\I18n\Helper\SiteHelper;
use Userstory\I18n\Helper\UserTypeEntityHelper;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

global $APPLICATION;
$APPLICATION->SetTitle(Loc::getMessage('USERSTORY_I18N__TITLE'));

include __DIR__ . '/includes/module_checker.php';

try {
    $siteHelper           = new SiteHelper();
    $iblockHelper         = new IblockHelper();
    $hlblockHelper        = new HLBlockHelper();
    $userTypeEntityHelper = new UserTypeEntityHelper();
    
    $defaultSiteId = $siteHelper->getDefaultSiteIdIfExists();
    $iblocks       = $iblockHelper->getIblocksForMainSite();
    $hlblocks      = $hlblockHelper->getHLBlocksForMainSite();
    
    $context = Application::getInstance()->getContext();
    $server  = $context->getServer();
    $request = $context->getRequest();
} catch (HelperException $e) {
    $errors[] = $e->getMessage();
}

if (!empty($errors)) {
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

    foreach ($errors as $error) {
        echo CAdminMessage::ShowMessage($error);
    }
    
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
} else {
    if (($server->getRequestMethod() === 'POST') && $request->getPost('add') && check_bitrix_sessid()) {
        $data   = $request->getPostList()->toArray();
        $errors = [];

        try {
            $newSiteId = $siteHelper->copyDefaultSite($data['siteId'] ?: '', $data['siteName'] ?: '');
            $resultMessage = 'Добавлена новая языковая версия сайта [LID] = "' . $newSiteId . '"<br>';
        } catch (Exception $e) {
            $errors[] = ['id' => 'ADD_SITE', 'text' => $e->getMessage()];
        }

        if (empty($errors)) {
            global $propertiesWithLinks , $userFieldsWithLinks;
            $propertiesWithLinks = [];
            $userFieldsWithLinks = [];
            $newIblocks  = [];
            $newHlblocks = [];
    
            foreach ($data['iblocks'] as $iblockId) {
                if (key_exists($iblockId, $iblocks)) {
                    if ($newIblockId = (new IblockExportService())->export((int) $iblockId, $newSiteId)) {
                        $resultMessage .= 'Скопирован инфоблок [' . $iblockId  . '] ' . $iblocks[$iblockId]['NAME'] . '-> ['. $newIblockId . ']<br>';
                        $newIblocks[$iblockId] = $newIblockId;
                    } else {
                        $resultMessage .= 'Ошибка копирования инфоблока [' . $iblockId  . '] ' . $iblocks[$iblockId]['NAME'] . '<br>';
                    }
                }
            }

            (new IblockExportService())->addCatalogIblocksIfNeeds($newIblocks);
    
            foreach ($data['hlblocks'] as $hlblockId) {
                if (key_exists($hlblockId, $hlblocks)) {
                    if ($newHlblockId = (new HLBlockExportService())->export((int) $hlblockId, $newSiteId)) {
                        $resultMessage .= 'Скопирован HL-блок [' . $hlblockId  . '] ' . ($hlblock['LANG'][LANG]['NAME'] ?? $hlblock['NAME']) . '-> ['. $newHlblockId . ']<br>';
                        $newHlblocks[$hlblockId] = $newHlblockId;
                    } else {
                        $resultMessage .= 'Ошибка копирования HL-блока [' . $hlblockId  . '] ' . $hlblock['LANG'][LANG]['NAME'] ?? $hlblock['NAME'] . '<br>';
                    }
                }
            }
    
            if (!empty($propertiesWithLinks)) {
                foreach ($propertiesWithLinks as $propId => $propData) {
                    if (in_array($propData['PROPERTY_TYPE'], ['E', 'G']) && key_exists($propData['LINK_IBLOCK_ID'], $newIblocks)) {
                        $propData['LINK_IBLOCK_ID'] = $newIblocks[$propData['LINK_IBLOCK_ID']];
                        $iblockHelper->updatePropertyById($propId, $propData);
                    }
                    if (($propData['PROPERTY_TYPE'] === 'S') && ($propData['USER_TYPE'] === 'directory') && !empty($propData['USER_TYPE_SETTINGS'])) {
                        $hlblockId = $hlblockHelper->getHlblockId(['TABLE_NAME' => $propData['USER_TYPE_SETTINGS']['TABLE_NAME']]);
                        if (key_exists($hlblockId, $newHlblocks)) {
                            $newHlblockTableName = $hlblockHelper->getHlblock($newHlblocks[$hlblockId])['TABLE_NAME'] ?: '';
                            if (!empty($newHlblockTableName)) {
                                $propData['USER_TYPE_SETTINGS']['TABLE_NAME'] = $newHlblockTableName;
                                $iblockHelper->updatePropertyById($propId, $propData);
                            }
                        }
                    }
                }
            }
            if (!empty($userFieldsWithLinks)) {
                foreach ($userFieldsWithLinks as $userFieldId => $userFieldData) {
                    if (in_array($userFieldData['USER_TYPE_ID'], ['iblock_section', 'iblock_element'])) {
                        $iblockId = $iblockHelper->getIblockIdByUid($userFieldData['SETTINGS']['IBLOCK_ID']);
                        if (key_exists($iblockId, $newIblocks)) {
                            $userFieldData['SETTINGS']['IBLOCK_ID'] = $newIblocks[$iblockId];
                            $userTypeEntityHelper->updateUserTypeEntity($userFieldId, $userFieldData);
                        }
                    }
                    if ($userFieldData['USER_TYPE_ID'] === 'hlblock') {
                        $hlblockId = $hlblockHelper->getHlblockIdByUid($userFieldData['SETTINGS']['HLBLOCK_ID']);
                        if (key_exists($hlblockId, $newHlblocks)) {
                            $userFieldData['SETTINGS']['HLBLOCK_ID'] = $newHlblocks[$hlblockId];
                            $userTypeEntityHelper->updateUserTypeEntity($userFieldId, $userFieldData);
                        }
                    }
                }
            }
        }

        if (empty($errors)) {
            $_SESSION['ADD_VERSION_RESULT_MESSAGE'] = $resultMessage;
            LocalRedirect($APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID);
        }
    }

    $aTabs = [
        [
            'DIV' => 'add_version',
            'TAB' => Loc::getMessage('USERSTORY_I18N__ADD_VERSION_TAB'),
            'TITLE' => Loc::getMessage('USERSTORY_I18N__ADD_VERSION_TAB_TITLE')
        ]
    ];
    $tabControl = new CAdminTabControl('tabControl', $aTabs, true, true);

    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
    CUtil::InitJSCore(['jquery']);

    if (!empty($errors)) {
        $message = new CAdminMessage(
            Loc::getMessage('USERSTORY_I18N__ADD_ERROR'),
            new CAdminException($errors)
        );
    } elseif (isset($_SESSION['ADD_VERSION_RESULT_MESSAGE'])) {
        $message = new CAdminMessage(
            ['MESSAGE' => $_SESSION['ADD_VERSION_RESULT_MESSAGE'], 'TYPE' => 'OK']
        );
        unset($_SESSION['ADD_VERSION_RESULT_MESSAGE']);
    }
    if ($message) {
        echo $message->Show();
    }
    ?>

    <form name="new_version" method="POST" action="<?= $APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID ?>" enctype="multipart/form-data">
        <?php
        $tabControl->Begin();
        $tabControl->BeginNextTab();
        ?>

        <tr>
            <td><?= Loc::getMessage('USERSTORY_I18N__SITE_ID_INPUT') ?></td>
            <td>
                <input type="text" name="siteId" size="2" maxlength="2" value="<?= htmlspecialcharsbx($data['siteId'] ?: '') ?>">
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage('USERSTORY_I18N__SITE_NAME_INPUT') ?></td>
            <td>
                <input type="text" name="siteName" size="30" value="<?= htmlspecialcharsbx($data['siteName'] ?: '') ?>">
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage('USERSTORY_I18N__IBLOCKS_SELECT') ?></td>
            <td>
                <select name="iblocks[]" size="<?= count($iblocks) ?>" multiple>
                    <?php foreach ($iblocks as $iblock): ?>
                        <option value="<?= $iblock['ID'] ?>"<?= (empty($data['iblocks']) || in_array($iblock['ID'], $data['iblocks'])) ? ' selected' : '' ?>>
                            <?= sprintf('[%s] %s', $iblock['ID'], $iblock['NAME']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage('USERSTORY_I18N__HLBLOCKS_SELECT') ?></td>
            <td>
                <select name="hlblocks[]" size="<?= count($hlblocks) ?>" multiple>
                    <?php foreach ($hlblocks as $hlblock): ?>
                        <option value="<?= $hlblock['ID'] ?>"<?= (empty($data['hlblocks']) || in_array($hlblock['ID'], $data['hlblocks'])) ? ' selected' : '' ?>>
                            <?= sprintf('[%s] %s', $hlblock['ID'], $hlblock['LANG'][LANG]['NAME'] ?? $hlblock['NAME']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </td>
        </tr>        

        <?= bitrix_sessid_post();?>
        <input type="hidden" name="lang" value="<?= LANG ?>">

        <?php
        $tabControl->Buttons();
        ?>

        <input type="submit"
            name="add"
            value="<?= Loc::getMessage('USERSTORY_I18N__BTN') ?>"
            title="<?= Loc::getMessage('USERSTORY_I18N__BTN') ?>"
            class="adm-btn-save">

        <?php
        $tabControl->End();
        ?>
    </form>

    <?php
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
}
