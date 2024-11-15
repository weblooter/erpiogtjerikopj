<?php

namespace Userstory\I18n;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Connection;
use Userstory\I18n\Orm\HLBlockMigrationTable;
use Userstory\I18n\Orm\IblockMigrationTable;

/**
 * Class DB
 *
 * @package Userstory\I18n
 */
class DB
{
    /**
     * @var array
     */
    protected array $ormTables;

    /**
     * @var Connection|\Bitrix\Main\DB\Connection
     */
    protected Connection $connection;

    /**
     * DB constructor
     *
     * @param Application $application Экземпляр приложения.
     */
    public function __construct(Application $application)
    {
        $this->connection = $application::getConnection();
        $this->ormTables = [
            IblockMigrationTable::class,
            HLBlockMigrationTable::class
        ];
    }

    /**
     * @return bool
     */
    public function createModuleTables(): bool
    {
        foreach ($this->ormTables as $ormTable) {
            $entity = $ormTable::getEntity();
            $table  = $entity->getDBTableName();

            if (! $this->connection->isTableExists($table)) {
                $entity->createDbTable();
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function dropModuleTables(): bool
    {
        foreach ($this->ormTables as $ormTable) {
            $entity = $ormTable::getEntity();
            $table  = $entity->getDBTableName();

            if ($this->connection->isTableExists($table)) {
                $this->connection->dropTable($table);
            }
        }

        return true;
    }
}
