<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;

defined('ADMIN_MODULE_NAME') or define('ADMIN_MODULE_NAME', 'userstory.itsintegrator');

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm('Nope');
}

$app = Application::getInstance();
$context = $app->getContext();
$request = $context->getRequest();

Loc::loadMessages($context->getServer()->getDocumentRoot()."/bitrix/modules/main/options.php");
Loc::loadMessages(__FILE__);

$tabControl = new CAdminTabControl("tabControl", array(
    array(
        "DIV" => "edit1",
        "TAB" => Loc::getMessage("MAIN_TAB_SET"),
        "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_SET"),
    ),
));

if ((!empty($save) || !empty($restore)) && $request->isPost() && check_bitrix_sessid()) {
    if (!empty($restore)) {
        Option::delete(ADMIN_MODULE_NAME);
        CAdminMessage::showMessage(array(
            "MESSAGE" => Loc::getMessage("REFERENCES_OPTIONS_RESTORED"),
            "TYPE" => "OK",
        ));
    } elseif (
        $request->getPost('HOST') ||
        $request->getPost('USER') ||
        $request->getPost('PASS') ||
        $request->getPost('VHOST') ||
        $request->getPost('CATALOG_ID') ||
        $request->getPost('OFFERS_ID') ||
        $request->getPost('EXCHANGE') ||
        $request->getPost('QUEUE') ||
        ($request->getPost('PORT') && ($request->getPost('PORT') > 0))
    ) {
        Option::set(
            ADMIN_MODULE_NAME,
            "HOST",
            $request->getPost('HOST')
        );
        Option::set(
            ADMIN_MODULE_NAME,
            "PORT",
            $request->getPost('PORT')
        );
        Option::set(
            ADMIN_MODULE_NAME,
            "USER",
            $request->getPost('USER')
        );
        Option::set(
            ADMIN_MODULE_NAME,
            "PASS",
            $request->getPost('PASS')
        );
        Option::set(
            ADMIN_MODULE_NAME,
            "VHOST",
            $request->getPost('VHOST')
        );
        Option::set(
            ADMIN_MODULE_NAME,
            "CATALOG_ID",
            $request->getPost('CATALOG_ID')
        );
        Option::set(
            ADMIN_MODULE_NAME,
            "OFFERS_ID",
            $request->getPost('OFFERS_ID')
        );
        Option::set(
            ADMIN_MODULE_NAME,
            "EXCHANGE",
            $request->getPost('EXCHANGE')
        );
        Option::set(
            ADMIN_MODULE_NAME,
            "QUEUE",
            $request->getPost('QUEUE')
        );

        $groupsVal = $request->getPost('groupsVal');
        $iblockIds = $request->getPost('iblockIds');
        $arGroupsSer = [];
        foreach($groupsVal as $key =>$val)
        {
            if(! empty((string)$val)) {
                $arGroupsSer[$key] = ["GROUP_NAME" => (string)$val, "IBLOCK_ID" => $iblockIds[$key]];
            }
        }
        if(!empty($arGroupsSer)) {
            Option::set(ADMIN_MODULE_NAME, "groupCodes", serialize($arGroupsSer));
        }

        CAdminMessage::showMessage(array(
            "MESSAGE" => Loc::getMessage("REFERENCES_OPTIONS_SAVED"),
            "TYPE" => "OK",
        ));
    } else {
        CAdminMessage::showMessage(Loc::getMessage("REFERENCES_INVALID_VALUE"));
    }
}

$tabControl->begin();
?>

<form method="post" action="<?=sprintf('%s?mid=%s&lang=%s', $request->getRequestedPage(), urlencode($mid), LANGUAGE_ID)?>">
    <?php
    echo bitrix_sessid_post();
    $tabControl->beginNextTab();
    ?>
    <tr>
        <td width="40%">
            <label for="HOST"><?=Loc::getMessage("USERSTORY_ITSINTEGRATOR_RABBITMQ_HOST") ?>:</label>
        <td width="60%">
            <input type="text"
                   size="50"
                   maxlength="255"
                   name="HOST"
                   value="<?=HtmlFilter::encode(Option::get(ADMIN_MODULE_NAME, "HOST", ''));?>"
                   />
        </td>
    </tr>
    <tr>
        <td width="40%">
            <label for="PORT"><?=Loc::getMessage("USERSTORY_ITSINTEGRATOR_RABBITMQ_PORT") ?>:</label>
        <td width="60%">
            <input type="text"
                   size="50"
                   maxlength="5"
                   name="PORT"
                   value="<?=HtmlFilter::encode(Option::get(ADMIN_MODULE_NAME, "PORT", Loc::getMessage("USERSTORY_ITSINTEGRATOR_RABBITMQ_PORT_DEFAULT_VALUE")));?>"
                   />
        </td>
    </tr>
    <tr>
        <td width="40%">
            <label for="USER"><?=Loc::getMessage("USERSTORY_ITSINTEGRATOR_RABBITMQ_USER") ?>:</label>
        <td width="60%">
            <input type="text"
                   size="50"
                   maxlength="255"
                   name="USER"
                   value="<?=HtmlFilter::encode(Option::get(ADMIN_MODULE_NAME, "USER", ''));?>"
            />
        </td>
    </tr>
    <tr>
        <td width="40%">
            <label for="PASS"><?=Loc::getMessage("USERSTORY_ITSINTEGRATOR_RABBITMQ_PASS") ?>:</label>
        <td width="60%">
            <input type="text"
                   size="50"
                   maxlength="255"
                   name="PASS"
                   value="<?=HtmlFilter::encode(Option::get(ADMIN_MODULE_NAME, "PASS", ''));?>"
            />
        </td>
    </tr>
    <tr>
        <td width="40%">
            <label for="VHOST"><?=Loc::getMessage("USERSTORY_ITSINTEGRATOR_RABBITMQ_VHOST") ?>:</label>
        <td width="60%">
            <input type="text"
                   size="50"
                   maxlength="255"
                   name="VHOST"
                   value="<?=HtmlFilter::encode(Option::get(ADMIN_MODULE_NAME, "VHOST", '/'));?>"
            />
        </td>
    </tr>
    <tr>
        <td width="40%">
            <label for="CATALOG_ID"><?=Loc::getMessage("USERSTORY_ITSINTEGRATOR_CATALOG_ID") ?>:</label>
        <td width="60%">
            <input type="text"
                   size="50"
                   maxlength="255"
                   name="CATALOG_ID"
                   value="<?=HtmlFilter::encode(Option::get(ADMIN_MODULE_NAME, "CATALOG_ID", ''));?>"
            />
        </td>
    </tr>
    <tr>
        <td width="40%">
            <label for="OFFERS_ID"><?=Loc::getMessage("USERSTORY_ITSINTEGRATOR_OFFERS_ID") ?>:</label>
        <td width="60%">
            <input type="text"
                   size="50"
                   maxlength="255"
                   name="OFFERS_ID"
                   value="<?=HtmlFilter::encode(Option::get(ADMIN_MODULE_NAME, "OFFERS_ID", ''));?>"
            />
        </td>
    </tr>
    <tr>
        <td width="40%">
            <label for="EXCHANGE"><?=Loc::getMessage("USERSTORY_ITSINTEGRATOR_RABBITMQ_EXCHANGE") ?>:</label>
        <td width="60%">
            <input type="text"
                   size="50"
                   maxlength="255"
                   name="EXCHANGE"
                   value="<?=HtmlFilter::encode(Option::get(ADMIN_MODULE_NAME, "EXCHANGE", 'thumbnailer_exchange'));?>"
            />
        </td>
    </tr>
    <tr>
        <td width="40%">
            <label for="QUEUE"><?=Loc::getMessage("USERSTORY_ITSINTEGRATOR_RABBITMQ_QUEUE") ?>:</label>
        <td width="60%">
            <input type="text"
                   size="50"
                   maxlength="255"
                   name="QUEUE"
                   value="<?=HtmlFilter::encode(Option::get(ADMIN_MODULE_NAME, "QUEUE", 'thumbnailer_queue'));?>"
            />
        </td>
    </tr>

    <tr class="heading">
		<td colspan="2"><?=GetMessage("USERSTORY_ITSINTEGRATOR_MAPPING_TITLE")?></td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<table cellspacing="0" cellpadding="0" border="0" class="internal">
				<tr class="heading">
					<td valign="top">
						<?echo GetMessage("USERSTORY_ITSINTEGRATOR_MAPPING_GROUP_NAME")?>
					</td>
					<td valign="top">
						<?echo GetMessage("USERSTORY_ITSINTEGRATOR_MAPPING_IBLOCK_ID")?>
					</td>
				</tr>
                <?
                $val = COption::GetOptionString(ADMIN_MODULE_NAME, "groupCodes", 'a:4:{i:1;a:2:{s:10:"GROUP_NAME";s:7:"product";s:9:"IBLOCK_ID";s:1:"1";}i:2;a:2:{s:10:"GROUP_NAME";s:0:"";s:9:"IBLOCK_ID";s:0:"";}i:3;a:2:{s:10:"GROUP_NAME";s:0:"";s:9:"IBLOCK_ID";s:0:"";}i:4;a:2:{s:10:"GROUP_NAME";s:0:"";s:9:"IBLOCK_ID";s:0:"";}}');
                $key = 0;
                if(strlen($val) > 0)
                {
                    $arGroups = unserialize($val);
                    foreach($arGroups as $key => $val)
                    {
                        ?>
                        <tr>
							<td><input type="text" name="groupsVal[<?=$key?>]" value="<?=$val["GROUP_NAME"]?>"></td>
							<td><input type="text" name="iblockIds[<?=$key?>]" value="<?=$val["IBLOCK_ID"]?>"></td>
						</tr>
                        <?
                    }
                }
                if ((int)$key <= 0)
                    $key = 0;
                ?>
                <tr>
					<td><input type="text" name="groupsVal[<?=++$key?>]" value=""></td>
                    <td><input type="text" name="iblockIds[<?=$key?>]" value=""></td>
				</tr>
				<tr>
					<td><input type="text" name="groupsVal[<?=++$key?>]" value=""></td>
                    <td><input type="text" name="iblockIds[<?=$key?>]" value=""></td>
                </tr>
				<tr>
					<td><input type="text" name="groupsVal[<?=++$key?>]" value=""></td>
                    <td><input type="text" name="iblockIds[<?=$key?>]" value=""></td>
				</tr>

			</table>
		</td>
	</tr>
    <?php
    $tabControl->buttons();
    ?>
    <input type="submit"
           name="save"
           value="<?=Loc::getMessage("MAIN_SAVE") ?>"
           title="<?=Loc::getMessage("MAIN_OPT_SAVE_TITLE") ?>"
           class="adm-btn-save"
           />
    <input type="submit"
           name="restore"
           title="<?=Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS") ?>"
           onclick="return confirm('<?= AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING")) ?>')"
           value="<?=Loc::getMessage("MAIN_RESTORE_DEFAULTS") ?>"
           />
    <?php
    $tabControl->end();
    ?>
</form>
