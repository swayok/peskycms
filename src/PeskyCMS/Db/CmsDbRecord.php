<?php

namespace PeskyCMS\Db;

use PeskyCMF\Db\CmfDbRecord;
use PeskyCMS\Db\Settings\CmsSettingsTable;
use PeskyORMLaravel\Db\KeyValueTableUtils\KeyValueTableInterface;

/**
 * @method static CmsDbTableStructure getTableStructure()
 */
abstract class CmsDbRecord extends CmfDbRecord {

    /**
     * Class name of the table
     * @var string
     */
    static protected $tableClass;

    static public function getSingletonInstanceOfDbClassFromServiceContainer($class) {
        return CmsDbTable::getSingletonInstanceOfDbClassFromServiceContainer($class);
    }

    /**
     * @return CmsSettingsTable|KeyValueTableInterface
     */
    static public function getTable() {
        if (empty(static::$tableClass)) {
            throw new \UnexpectedValueException('You need to provide ' . static::class . '::$tableClass property');
        }
        return static::getSingletonInstanceOfDbClassFromServiceContainer(static::$tableClass);
    }
}