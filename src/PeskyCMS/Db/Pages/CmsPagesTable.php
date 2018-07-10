<?php

namespace PeskyCMS\Db\Pages;

use PeskyCMF\Db\CmfDbTable;
use PeskyCMF\Scaffold\ScaffoldConfig;
use PeskyORM\Core\DbExpr;

class CmsPagesTable extends CmfDbTable {

    public function getTableStructure(): CmsPagesTableStructure {
        return CmsPagesTableStructure::getInstance();
    }

    public function newRecord(): CmsPage {
        return new CmsPage();
    }

    public function getTableAlias(): string {
        return 'CmsPages';
    }

    static public function registerUniquePageUrlValidator(ScaffoldConfig $scaffoldConfig) {
        \Validator::extend('unique_page_url', function () {
            $urlAlias = request()->input('url_alias');
            $parentId = (int)request()->input('parent_id');
            if ($parentId > 0 && $urlAlias === '/') {
                return false;
            } else {
                return static::count([
                    'url_alias' => $urlAlias,
                    'id !=' => (int)request()->input('id'),
                    'parent_id' => $parentId > 0 ? $parentId : null
                ]) === 0;
            }
        });
        \Validator::replacer('unique_page_url', function () use ($scaffoldConfig) {
            $urlAlias = request()->input('url_alias');
            $parentId = (int)request()->input('parent_id');
            if ($parentId > 0 && $urlAlias === '/') {
                $otherPageId = $parentId;
            } else {
                $otherPageId = static::selectValue(
                    DbExpr::create('`id`'),
                    [
                        'url_alias' => $urlAlias,
                        'parent_id' => $parentId > 0 ? $parentId : null
                    ]
                );
            }
            return $scaffoldConfig->translate('form.validation', 'unique_page_url', [
                'url' => routeToCmfItemEditForm($scaffoldConfig::getResourceName(), $otherPageId)
            ]);
        });
    }

}
