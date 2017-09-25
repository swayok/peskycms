<?php

namespace PeskyCMS\Db\Texts;

use PeskyCMS\Db\CmsDbTable;

/**
 * @method CmsTextsTableStructure getTableStructure()
 * @method CmsText newRecord()
 */
class CmsTextsTable extends CmsDbTable {

    static protected $tableStructureClass = CmsTextsTableStructure::class;
    static protected $recordClass = CmsText::class;

    /**
     * @return string
     */
    public function getTableAlias() {
        return 'CmsTexts';
    }

}
