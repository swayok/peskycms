<?php

namespace PeskyCMF\CMS\Redirects;

use PeskyCMF\Db\CmfDbTable;

class CmsRedirectsTable extends CmfDbTable {

    public function getTableStructure(): CmsRedirectsTableStructure {
        return CmsRedirectsTableStructure::getInstance();
    }

    public function newRecord(): CmsRedirect {
        return new CmsRedirect();
    }

    public function getTableAlias(): string {
        return 'CmsRedirects';
    }

}
