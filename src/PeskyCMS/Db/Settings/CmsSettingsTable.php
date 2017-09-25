<?php

namespace PeskyCMS\Db\Settings;

use PeskyCMS\Db\CmsDbTable;
use PeskyORMLaravel\Db\KeyValueTableUtils\KeyValueTableHelpers;
use PeskyORMLaravel\Db\KeyValueTableUtils\KeyValueTableInterface;

/**
 * @method CmsSettingsTableStructure getTableStructure()
 * @method CmsSetting newRecord()
 */
class CmsSettingsTable extends CmsDbTable implements KeyValueTableInterface {

    use KeyValueTableHelpers;

    static protected $tableStructureClass = CmsSettingsTableStructure::class;
    static protected $recordClass = CmsSetting::class;

    public function getMainForeignKeyColumnName() {
        return null;
    }

    /**
     * @param null $foreignKeyValue
     * @return null|string
     */
    static public function getCacheKeyToStoreAllValuesForAForeignKey($foreignKeyValue = null) {
        return 'app-settings';
    }

    /**
     * @return string
     */
    public function getTableAlias() {
        return 'CmsSettings';
    }

}
