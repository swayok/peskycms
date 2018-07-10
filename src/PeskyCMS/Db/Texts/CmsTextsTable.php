<?php

namespace PeskyCMS\Db\Texts;

use PeskyCMF\Db\CmfDbTable;

class CmsTextsTable extends CmfDbTable {

    public function getTableStructure(): CmsTextsTableStructure {
        return CmsTextsTableStructure::getInstance();
    }

    public function newRecord(): CmsText {
        return new CmsText();
    }

    public function getTableAlias(): string {
        return 'CmsTexts';
    }

}
