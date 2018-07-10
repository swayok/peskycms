<?php

namespace PeskyCMS\Db\CmsTexts;

use PeskyCMF\Config\CmfConfig;
use PeskyCMF\Db\CmfDbTableStructure;
use PeskyCMS\Db\CmsPages\CmsPagesTable;
use PeskyORM\ORM\Column;
use PeskyORM\ORM\Relation;
use PeskyORMLaravel\Db\TableStructureTraits\IdColumn;
use PeskyORMLaravel\Db\TableStructureTraits\TimestampColumns;

/**
 * @property-read Column    $id
 * @property-read Column    $page_id
 * @property-read Column    $admin_id
 * @property-read Column    $language
 * @property-read Column    $title
 * @property-read Column    $browser_title
 * @property-read Column    $menu_title
 * @property-read Column    $comment
 * @property-read Column    $content
 * @property-read Column    $meta_description
 * @property-read Column    $meta_keywords
 * @property-read Column    $created_at
 * @property-read Column    $updated_at
 * @property-read Column    $custom_info
 */
class CmsTextsTableStructure extends CmfDbTableStructure {

    use IdColumn,
        TimestampColumns;

    static public function getTableName(): string {
        return 'cms_texts';
    }

    private function page_id() {
        return Column::create(Column::TYPE_INT)
            ->convertsEmptyStringToNull();
    }

    private function admin_id() {
        return Column::create(Column::TYPE_INT);
    }

    private function language() {
        return Column::create(Column::TYPE_STRING)
            ->disallowsNullValues()
            ->setDefaultValue(setting()->default_language());
    }

    private function title() {
        return Column::create(Column::TYPE_STRING)
            ->disallowsNullValues()
            ->setDefaultValue('');
    }

    private function browser_title() {
        return Column::create(Column::TYPE_STRING)
            ->disallowsNullValues()
            ->setDefaultValue('');
    }

    private function menu_title() {
        return Column::create(Column::TYPE_STRING)
            ->disallowsNullValues()
            ->setDefaultValue('');
    }

    private function comment() {
        return Column::create(Column::TYPE_TEXT)
            ->disallowsNullValues()
            ->setDefaultValue('');
    }

    private function content() {
        return Column::create(Column::TYPE_TEXT)
            ->convertsEmptyStringToNull()
            ->setDefaultValue('');
    }

    private function meta_description() {
        return Column::create(Column::TYPE_TEXT)
            ->disallowsNullValues()
            ->setDefaultValue('');
    }

    private function meta_keywords() {
        return Column::create(Column::TYPE_STRING)
            ->disallowsNullValues()
            ->setDefaultValue('');
    }

    private function custom_info() {
        return Column::create(Column::TYPE_JSONB)
            ->disallowsNullValues()
            ->setDefaultValue('{}');
    }

    private function Page() {
        return Relation::create('page_id', Relation::BELONGS_TO, CmsPagesTable::class, 'id')
            ->setDisplayColumnName('url_alias');
    }

    private function Admin() {
        return Relation::create('admin_id', Relation::BELONGS_TO, CmfConfig::getDefault()->getAuthModule()->getUsersTable(), 'id')
            ->setDisplayColumnName(function (array $data) {
                return array_get($data, 'name') ?: (array_get($data, 'email') ?: (array_get($data, 'login') ?: array_get($data, 'id')));
            });
    }

}
