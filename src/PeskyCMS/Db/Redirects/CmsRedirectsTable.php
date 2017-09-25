<?php

namespace PeskyCMF\CMS\Redirects;

use PeskyCMS\Db\CmsDbTable;

/**
 * @method CmsRedirectsTableStructure getTableStructure()
 * @method CmsRedirect newRecord()
 */
class CmsRedirectsTable extends CmsDbTable {

    static protected $tableStructureClass = CmsRedirectsTableStructure::class;
    static protected $recordClass = CmsRedirect::class;

    /**
     * @return string
     */
    public function getTableAlias() {
        return 'CmsRedirects';
    }

}
