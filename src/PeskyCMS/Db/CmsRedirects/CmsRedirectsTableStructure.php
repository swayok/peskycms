<?php

namespace PeskyCMS\Db\CmsRedirects;

use PeskyCMF\Config\CmfConfig;
use PeskyCMF\Db\CmfDbTableStructure;
use PeskyCMS\Db\CmsPages\CmsPagesTable;
use PeskyORM\ORM\Column;
use PeskyORM\ORM\Relation;
use PeskyORMLaravel\Db\TableStructureTraits\IdColumn;
use PeskyORMLaravel\Db\TableStructureTraits\TimestampColumns;

class CmsRedirectsTableStructure extends CmfDbTableStructure {

    use IdColumn,
        TimestampColumns;

    static public function getTableName(): string {
        return 'cms_redirects';
    }

    private function page_id() {
        return Column::create(Column::TYPE_INT);
    }

    private function admin_id() {
        return Column::create(Column::TYPE_INT);
    }

    private function from_url() {
        return Column::create(Column::TYPE_STRING)
            ->uniqueValues()
            ->disallowsNullValues();
    }

    private function to_url() {
        return Column::create(Column::TYPE_STRING);
    }

    private function is_permanent() {
        return Column::create(Column::TYPE_BOOL)
            ->disallowsNullValues()
            ->setDefaultValue(true);
    }

    private function Page() {
        return Relation::create('page_id', Relation::BELONGS_TO, CmsPagesTable::class, 'id')
            ->setDisplayColumnName('relative_url');
    }

    private function Admin() {
        return Relation::create('admin_id', Relation::BELONGS_TO, CmfConfig::getDefault()->getAuthModule()->getUsersTable(), 'id')
            ->setDisplayColumnName(function (array $data) {
                return array_get($data, 'name') ?: (array_get($data, 'email') ?: (array_get($data, 'login') ?: array_get($data, 'id')));
            });
    }

}
