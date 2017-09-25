<?php

namespace PeskyCMF\CMS\Redirects;

use PeskyCMS\Db\CmsDbTableStructure;
use PeskyCMS\Db\Pages\CmsPagesTable;
use PeskyCMS\Db\TableStructureTraits\AdminIdColumn;
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
class CmsRedirectsTableStructure extends CmsDbTableStructure {

    use IdColumn,
        AdminIdColumn,
        TimestampColumns;

    /**
     * @return string
     */
    static public function getTableName() {
        return 'redirects';
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

    private function Page() {
        return Relation::create('page_id', Relation::BELONGS_TO, app(CmsPagesTable::class), 'id')
            ->setDisplayColumnName('relative_url');
    }

}
