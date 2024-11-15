<?php

use Bitrix\Main\EventManager;
use Userstory\ItsIntegrator\ItsProducer;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Userstory\ItsIntegrator\ItsIntegratorTable;

Loc::loadMessages(__FILE__);

class userstory_itsintegrator extends CModule
{
    public array $iblockEvents = [
        'OnAfterIBlockElementAdd',
        'OnBeforeIBlockElementUpdate',
        'OnAfterIBlockElementUpdate',
        'OnIBlockElementDelete',
        'OnAfterIBlockSectionAdd',
        'OnBeforeIBlockSectionUpdate',
        'OnIBlockElementSetPropertyValuesEx',
        'OnAfterIBlockSectionUpdate',
        'OnBeforeIBlockSectionDelete',
    ];

    public function __construct()
    {
        $arModuleVersion = [];

        include __DIR__ . '/version.php';

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION      = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_ID           = 'userstory.itsintegrator';
        $this->MODULE_NAME         = Loc::getMessage('USERSTORY_ITSINTEGRATOR_MODULE_NAME');
        $this->MODULE_DESCRIPTION  = Loc::getMessage('USERSTORY_ITSINTEGRATOR_MODULE_DESCRIPTION');
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->PARTNER_NAME        = Loc::getMessage('USERSTORY_ITSINTEGRATOR_MODULE_PARTNER_NAME');
        $this->PARTNER_URI         = 'https://userstory.ru';
    }

    public function doInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);

        $this->installDB();
        $this->InstallEvents();
    }

    public function installDB()
    {
        if (Loader::includeModule($this->MODULE_ID)) {
            ItsIntegratorTable::getEntity()->createDbTable();
        }
    }

    public function InstallEvents()
    {
        $eventManager = EventManager::getInstance();

        foreach ($this->iblockEvents as $event) {
            $eventManager->registerEventHandlerCompatible(
                'iblock',
                $event,
                $this->MODULE_ID,
                \Userstory\ItsIntegrator\Event\IblockListener::class,
                $event
            );
        }
    }

    public function doUninstall()
    {
        $this->UnInstallEvents();
        $this->uninstallDB();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function UnInstallEvents()
    {
        $eventManager = EventManager::getInstance();

        foreach ($this->iblockEvents as $event) {
            $eventManager->unRegisterEventHandler('iblock', $event, $this->MODULE_ID, \Userstory\ItsIntegrator\Event\IblockListener::class, $event);
        }
    }

    public function uninstallDB()
    {
        if (Loader::includeModule($this->MODULE_ID)) {
            $connection = Application::getInstance()->getConnection();
            $connection->dropTable(ItsIntegratorTable::getTableName());
        }
    }
}
