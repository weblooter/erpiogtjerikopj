<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Userstory\I18n\DB;

Loc::loadMessages(__FILE__);

/**
 * Class userstory_i18n
 */
class userstory_i18n extends CModule
{
    /**
     * userstory_i18n constructor
     */
    public function __construct()
    {
        $this->MODULE_ID          = str_replace('_', '.', get_class($this));
        $this->MODULE_NAME        = Loc::getMessage('USERSTORY_I18N__NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('USERSTORY_I18N__DESCRIPTION');
        $this->PARTNER_NAME       = Loc::getMessage('USERSTORY_I18N__PARTNER_NAME');
        $this->PARTNER_URI        = Loc::getMessage('USERSTORY_I18N__PARTNER_URI');

        if (file_exists(__DIR__ . '/version.php')) {
            include_once __DIR__ . '/version.php';
            
            $this->MODULE_VERSION      = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }
    }

    /**
     * Установка модуля
     * 
     * @return bool
     */
    public function DoInstall(): bool
    {
        global $APPLICATION;

        if (CheckVersion(ModuleManager::getVersion('main'), '14.00.00')) {
            $this->InstallDB();
            $this->InstallFiles();
        } else {
            $APPLICATION->ThrowException(Loc::getMessage('USERSTORY_I18N__INSTALL_ERROR_VERSION'));
        }

        $APPLICATION->IncludeAdminFile(Loc::getMessage('USERSTORY_I18N__INSTALL_TITLE') . ' "' . Loc::getMessage('USERSTORY_I18N__NAME') . '"', __DIR__ . '/step.php');

        return true;
    }

    /**
     * @return bool
     */
    public function InstallFiles(): bool
    {
        CopyDirFiles(__DIR__ . '/admin', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin');
        return true;
    }

    /**
     * @return bool
     */
    public function InstallDB(): bool
    {
        ModuleManager::registerModule($this->MODULE_ID);
        Loader::includeModule($this->MODULE_ID);

        $application = Application::getInstance();
        $db          = new DB($application);
        $db->createModuleTables();

        return true;
    }
   
    /**
     * Удаление модуля
     * 
     * @return bool
     */
    public function DoUninstall(): bool
    {
        global $APPLICATION;

        $this->UnInstallFiles();
        $this->UnInstallDB();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(Loc::getMessage('USERSTORY_I18N__UNINSTALL_TITLE') . ' "' . Loc::getMessage('USERSTORY_I18N__NAME') . '"', __DIR__ . '/unstep.php');

        return true;
    }

    /**
     * @return bool
     */
    public function UnInstallFiles(): bool
    {
        DeleteDirFiles(__DIR__ . '/admin', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin');
        return true;
    }

    /**
     * @return bool
     */
    public function UnInstallDB(): bool
    {
        Loader::includeModule($this->MODULE_ID);

        $application = Application::getInstance();
        $db          = new DB($application);
        $db->dropModuleTables();

        return true;
    }

    /**
     * @return string[][]
     */
    public function GetModuleRightList(): array
    {
        return [
            'reference_id' => [
                'D',
                'W',
            ],
            'reference'    => [
                '[D] ' . Loc::getMessage('USERSTORY_I18N__RIGHT_D'),
                '[W] ' . Loc::getMessage('USERSTORY_I18N__RIGHT_W'),
            ],
        ];
    }
}
