<?php

namespace PeskyCMS\Db\Admins;

use PeskyCMS\Db\CmsDbTable;

/**
 * @method CmsAdminsTableStructure getTableStructure()
 * @method CmsAdmin newRecord()
 */
class CmsAdminsTable extends CmsDbTable {

    static protected $tableStructureClass = CmsAdminsTableStructure::class;
    static protected $recordClass = CmsAdmin::class;

    /**
     * @return string
     */
    public function getTableAlias() {
        return 'CmsAdmins';
    }
}