<?php

namespace PeskyCMS\Scaffolds;

use PeskyCMF\Scaffold\DataGrid\DataGridColumn;
use PeskyCMF\Scaffold\Form\FormInput;
use PeskyCMF\Scaffold\ItemDetails\ValueCell;
use PeskyCMF\Scaffold\NormalTableScaffoldConfig;
use PeskyCMS\Db\CmsPages\CmsPage;
use PeskyCMS\Db\CmsPages\CmsPagesTable;
use PeskyCMS\Db\CmsRedirects\CmsRedirectsTable;
use Swayok\Utils\Set;

class CmsRedirectsScaffoldConfig extends NormalTableScaffoldConfig {

    protected $isDetailsViewerAllowed = true;
    protected $isCreateAllowed = true;
    protected $isEditAllowed = true;
    protected $isDeleteAllowed = true;

    public static function getTable() {
        return CmsRedirectsTable::class;
    }

    /**
     * @return CmsPagesTable
     */
    public static function getPagesTable() {
        return CmsPagesTable::getInstance();
    }

    public static function getResourceName() {
        return 'cms_redirects';
    }

    static protected function getIconForMenuItem() {
        return 'fa fa-map-signs';
    }

    protected function createDataGridConfig() {
        return parent::createDataGridConfig()
            ->readRelations([
                'Page' => ['*'],
                'Admin' => ['*'],
            ])
            ->setOrderBy('id', 'asc')
            ->setColumns([
                'id' => DataGridColumn::create()
                    ->setWidth(40),
                'from_url',
                'to_url',
                'page_id' => DataGridColumn::create()
                    ->setType(DataGridColumn::TYPE_LINK),
                'is_permanent',
            ])
            ->closeFilterByDefault();
    }
    
    protected function createDataGridFilterConfig() {
        return parent::createDataGridFilterConfig()
            ->setFilters([
                'id',
                'from_url',
                'to_url',
                'page_id',
                'is_permanent',
                'admin_id',
                'Page.title',
                'Page.url_alias'
            ]);
    }

    protected function createItemDetailsConfig() {
        return parent::createItemDetailsConfig()
            ->readRelations([
                'Page','Admin',
            ])
            ->setValueCells([
                'id',
                'from_url',
                'to_url',
                'page_id' => ValueCell::create()
                    ->setType(ValueCell::TYPE_LINK),
                'is_permanent',
                'admin_id' => ValueCell::create()
                    ->setType(ValueCell::TYPE_LINK),
                'created_at',
                'updated_at'
            ]);
    }
    
    protected function createFormConfig() {
        return parent::createFormConfig()
            ->setWidth(50)
            ->setFormInputs([
                'from_url',
                'to_url',
                'page_id' => FormInput::create()
                    ->setType(FormInput::TYPE_SELECT)
                    ->setOptionsLoader(function () {
                        return $this->getPagesOptions();
                    }),
                'is_permanent',
                'admin_id' => FormInput::create()
                    ->setType(FormInput::TYPE_HIDDEN)
                    ->setSubmittedValueModifier(function () {
                        return static::getUser()->id;
                    }),
            ])
            ->setValidators(function ($data) {
                $validators = [
                    'from_url' => 'required|string|regex:%^\/.+|unique_ceseinsensitive:' . static::getTable()->getName() . ',from_url,{{id}},id',
                ];
                if (empty($data['page_id'])) {
                    $validators['to_url'] = 'required|string|regex:%^\/.+%';
                } else {
                    $validators['to_url'] = 'nullable|string|regex:%^$%';
                }
                return $validators;
            });
    }

    protected function getPagesOptions() {
        /** @var CmsPage $pageClass */
        $pageClass = get_class(static::getPagesTable()->newRecord());
        $typesTranslations = $this->translate('form.input.page_types');
        $pages = static::getPagesTable()->select(
                ['id', 'url_alias', 'type', 'parent_id', 'Parent' => ['id', 'url_alias', 'parent_id']],
                [
                    'type !=' => $pageClass::getTypesWithoutUrls(),
                    'url_alias IS NOT' => null,
                    'url_alias !=' => ''
                ]
            )
            ->getDataFromEachObject(function ($page) use ($typesTranslations) {
                /** @var CmsPage $page */
                return [
                    'id' => $page->id,
                    'relative_url' => $page->relative_url,
                    'type' => array_get($typesTranslations, $page->type, $page->type)
                ];
            });
        return Set::combine($pages, '/id', '/relative_url', '/type');
    }

}