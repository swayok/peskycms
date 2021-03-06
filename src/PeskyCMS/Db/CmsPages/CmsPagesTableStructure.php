<?php

namespace PeskyCMS\Db\CmsPages;

use PeskyCMF\Config\CmfConfig;
use PeskyCMF\Db\CmfDbTableStructure;
use PeskyCMF\PeskyCmfAppSettings;
use PeskyCMS\PeskyCmsAppSettings;
use PeskyORM\Core\DbExpr;
use PeskyORM\ORM\Column;
use PeskyORM\ORM\RecordValue;
use PeskyORM\ORM\Relation;
use PeskyORMLaravel\Db\Column\ImagesColumn;
use PeskyORMLaravel\Db\TableStructureTraits\IdColumn;
use PeskyORMLaravel\Db\TableStructureTraits\IsPublishedColumn;
use PeskyORMLaravel\Db\TableStructureTraits\TimestampColumns;

class CmsPagesTableStructure extends CmfDbTableStructure {

    use IdColumn,
        IsPublishedColumn,
        TimestampColumns;

    /**
     * @return string
     */
    static public function getTableName() {
        return 'cms_pages';
    }

    private function admin_id() {
        return Column::create(Column::TYPE_INT);
    }

    private function type() {
        return Column::create(Column::TYPE_STRING)
            ->disallowsNullValues()
            ->setDefaultValue('page');
    }

    private function parent_id() {
        return Column::create(Column::TYPE_INT)
            ->convertsEmptyStringToNull();
    }

    private function comment() {
        return Column::create(Column::TYPE_TEXT)
            ->disallowsNullValues()
            ->setDefaultValue('');
    }

    private function title() {
        return Column::create(Column::TYPE_STRING)
            ->disallowsNullValues()
            ->setDefaultValue('');
    }

    private function url_alias() {
        return Column::create(Column::TYPE_STRING)
            ->uniqueValues(Column::CASE_SENSITIVE, 'parent_id')
            ->convertsEmptyStringToNull();
    }

    private function relative_url() {
        return Column::create(Column::TYPE_STRING)
            ->doesNotExistInDb()
            ->valueCannotBeSetOrChanged()
            ->setValueExistenceChecker(function (RecordValue $value) {
                return $value->getRecord()->existsInDb();
            })
            ->setValueGetter(function (RecordValue $value, $format = null) {
                /** @var CmsPage $record */
                $record = $value->getRecord();
                if (!$record->hasValue('url_alias', false)) {
                    return null;
                }
                /** @var PeskyCmsAppSettings $appSettings */
                $appSettings = app(PeskyCmfAppSettings::class);
                $baseUrl = rtrim('/' . trim($appSettings::cms_pages_url_prefix(), '/'), '/');
                if (
                    (
                        $record->hasValue('parent_id', false)
                        && $record->parent_id !== null
                    )
                    || (
                        $record->isRelatedRecordAttached('Parent')
                        && $record->Parent->existsInDb()
                    )
                ) {
                    return $baseUrl . $record->Parent->full_path . $record->url_alias;
                } else {
                    return $baseUrl . $record->url_alias;
                }
            });
    }

    private function full_path() {
        return Column::create(Column::TYPE_STRING)
            ->doesNotExistInDb()
            ->valueCannotBeSetOrChanged()
            ->setValueExistenceChecker(function (RecordValue $value) {
                return $value->getRecord()->existsInDb();
            })
            ->setValueGetter(function (RecordValue $value, $format = null) {
                /** @var CmsPage $record */
                $record = $value->getRecord();
                if (!$record->hasValue('url_alias', false)) {
                    return null;
                }
                $baseUrl = '';
                if (
                    (
                        $record->hasValue('parent_id', false)
                        && $record->parent_id !== null
                    )
                    || (
                        $record->isRelatedRecordAttached('Parent')
                        && $record->Parent->existsInDb()
                    )
                ) {
                    $baseUrl = $record->Parent->full_path;
                }
                return $baseUrl . $record->url_alias;
            });
    }

    private function page_code() {
        return Column::create(Column::TYPE_STRING)
            ->uniqueValues()
            ->convertsEmptyStringToNull();
    }

    private function publish_at() {
        return Column::create(Column::TYPE_TIMESTAMP)
            ->disallowsNullValues()
            ->setDefaultValue(DbExpr::create('NOW()'));
    }

    private function images() {
        $column = ImagesColumn::create()
            ->setRelativeUploadsFolderPath('assets/pages');
        $this->configureImages($column);
        return $column;
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

    private function position() {
        return Column::create(Column::TYPE_INT)
            ->convertsEmptyStringToNull();
    }

    private function with_contact_form() {
        return Column::create(Column::TYPE_BOOL)
            ->disallowsNullValues()
            ->setDefaultValue(false);
    }

    private function texts() {
        return Column::create(Column::TYPE_JSONB)
            ->disallowsNullValues()
            ->setDefaultValue('{}');
    }

    private function custom_info() {
        return Column::create(Column::TYPE_JSONB)
            ->disallowsNullValues()
            ->setDefaultValue('{}');
    }

    private function Parent() {
        return Relation::create('parent_id', Relation::BELONGS_TO, CmsPagesTable::class, 'id')
            ->setDisplayColumnName('title');
    }

    private function Admin() {
        return Relation::create('admin_id', Relation::BELONGS_TO, CmfConfig::getDefault()->getAuthModule()->getUsersTable(), 'id')
            ->setDisplayColumnName(function (array $data) {
                return array_get($data, 'name') ?: (array_get($data, 'email') ?: (array_get($data, 'login') ?: array_get($data, 'id')));
            });
    }

    protected function configureImages(ImagesColumn $column) {

    }

}
