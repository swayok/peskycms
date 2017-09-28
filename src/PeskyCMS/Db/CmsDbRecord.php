<?php

namespace PeskyCMS\Db;

use PeskyCMF\Db\CmfDbRecord;
use PeskyCMF\Db\CmfDbTableStructure;
use PeskyCMS\Db\Settings\CmfSettingsTable;
use PeskyORMLaravel\Db\KeyValueTableUtils\KeyValueTableInterface;

/**
 * @method static CmfDbTableStructure getTableStructure()
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
     * @return CmfSettingsTable|KeyValueTableInterface
     */
    static public function getTable() {
        if (empty(static::$tableClass)) {
            throw new \UnexpectedValueException('You need to provide ' . static::class . '::$tableClass property');
        }
        return static::getSingletonInstanceOfDbClassFromServiceContainer(static::$tableClass);
    }
}