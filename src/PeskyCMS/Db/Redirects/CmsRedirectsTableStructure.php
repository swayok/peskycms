<?php

namespace PeskyCMF\CMS\Redirects;

use PeskyCMF\Config\CmfConfig;
use PeskyCMF\Db\CmfDbTableStructure;
use PeskyCMS\Db\Pages\CmsPagesTable;
use PeskyORM\ORM\Column;
use PeskyORM\ORM\Relation;
use PeskyORMLaravel\Db\TableStructureTraits\IdColumn;
use PeskyORMLaravel\Db\TableStructureTraits\TimestampColumns;

/**
 * @property-read Column    $id
 * @property-read Column    $page_id
 * @property-read Column    $admin_id
 * @property-read Column    $relative_url
 * @property-read Column    $is_permanent
 * @property-read Column    $created_at
 * @property-read Column    $updated_at
 * @property-read Relation  $Page
 * @property-read Relation  $Admin
 */
class CmsRedirectsTableStructure extends CmfDbTableStructure {

    use IdColumn,
        TimestampColumns;

    static public function getTableName(): string {
        return 'cms_redirects';
    }

    private function page_id() {
        return Column::create(Column::TYPE_INT)
            ->disallowsNullValues()
            ->convertsEmptyStringToNull();
    }

    private function relative_url() {
        return Column::create(Column::TYPE_STRING)
            ->disallowsNullValues();
    }

    private function is_permanent() {
        return Column::create(Column::TYPE_BOOL)
            ->disallowsNullValues()
            ->setDefaultValue(true);
    }

    private function admin_id() {
        return Column::create(Column::TYPE_INT);
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
