<?php

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\SystemException;
use NaturaSiberica\Api\Entities\TokensTable;
use NaturaSiberica\Api\Entities\UserAddressTable;
use NaturaSiberica\Api\Entities\WishListTable;
use NaturaSiberica\Api\Events\Listeners\Iblock\IblockElementListener;
use NaturaSiberica\Api\Events\Listeners\Listeners\MainListener;
use NaturaSiberica\Api\Events\Listeners\Main\UserListener;
use NaturaSiberica\Api\Events\Listeners\Sale\OrderListener;

Loc::loadMessages(__FILE__);

class naturasiberica_api extends CModule
{
    public $MODULE_ID   = 'demoapp.api';
    public $MODULE_SORT = 1;

    private Connection   $connection;
    private EventManager $eventManager;

    private array $ormTables = [
        TokensTable::class,
        UserAddressTable::class,
        WishListTable::class,
    ];

    private array $events = [
        [
            'module'  => 'main',
            'event'   => 'OnPageStart',
            'handler' => MainListener::class,
        ],
        [
            'module'  => 'main',
            'event'   => 'OnAfterUserAdd',
            'handler' => UserListener::class,
        ],
        [
            'module'  => 'main',
            'event'   => 'OnBeforeUserUpdate',
            'handler' => UserListener::class,
        ],
        [
            'module'  => 'iblock',
            'event'   => 'OnBeforeIBlockElementUpdate',
            'handler' => IblockElementListener::class,
        ],
        [
            'module'  => 'iblock',
            'event'   => 'OnAfterIBlockElementDelete',
            'handler' => IblockElementListener::class,
        ],
        [
            'module'  => 'iblock',
            'event'   => 'OnBeforeIBlockElementAdd',
            'handler' => IblockElementListener::class,
        ],
        [
            'module'  => 'sale',
            'event'   => 'OnSaleStatusOrder',
            'handler' => OrderListener::class,
        ],
    ];

    public function __construct()
    {
        $this->MODULE_NAME        = 'Natura Siberica API';
        $this->MODULE_DESCRIPTION = Loc::getMessage('NS_MODULE_NAME');

        $this->PARTNER_NAME = 'USERSTORY';
        $this->PARTNER_URI  = 'https://userstory.ru';

        if (file_exists(__DIR__ . '/version.php')) {
            /**
             * @var array $arModuleVersion
             */
            include_once __DIR__ . '/version.php';

            $this->MODULE_VERSION      = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->connection   = Application::getConnection();
        $this->eventManager = EventManager::getInstance();
    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();
    }

    /**
     * @throws LoaderException
     * @throws SystemException
     * @throws ArgumentException
     */
    public function InstallDB()
    {
        Loader::includeModule($this->MODULE_ID);

        /**
         * @var DataManager $ormTable
         */
        foreach ($this->ormTables as $ormTable) {
            $entity = $ormTable::getEntity();
            $table  = $entity->getDBTableName();

            if (! $this->connection->isTableExists($table)) {
                $entity->createDbTable();
            }
        }
    }

    public function InstallEvents()
    {
        foreach ($this->events as $event) {
            $this->eventManager->registerEventHandlerCompatible(
                $event['module'],
                $event['event'],
                $this->MODULE_ID,
                $event['handler'],
                $event['event']
            );
        }
    }

    public function InstallFiles()
    {
        CopyDirFiles(__DIR__ . '/admin', $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/admin', true, true);
    }

    public function DoUninstall()
    {
        ModuleManager::unRegisterModule($this->MODULE_ID);

        $this->UnInstallFiles();
        $this->UnInstallEvents();
    }

    public function UnInstallFiles()
    {
        DeleteDirFiles(__DIR__ . '/admin', $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/admin');
    }

    public function UnInstallEvents()
    {
        foreach ($this->events as $event) {
            $this->eventManager->unRegisterEventHandler($event['module'], $event['event'], $this->MODULE_ID, $event['handler'], $event['event']);
        }
    }
}
