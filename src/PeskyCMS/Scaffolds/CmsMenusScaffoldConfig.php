<?php

namespace PeskyCMS\Scaffolds;

use PeskyCMF\Config\CmfConfig;
use PeskyCMF\Scaffold\DataGrid\DataGridColumn;
use PeskyCMF\Scaffold\Form\FormInput;
use PeskyCMF\Scaffold\Form\InputRenderer;
use PeskyCMF\Scaffold\Form\WysiwygFormInput;
use PeskyCMF\Scaffold\ItemDetails\ValueCell;
use PeskyCMF\Scaffold\NormalTableScaffoldConfig;
use PeskyCMS\Db\CmsPages\CmsPage;
use PeskyCMS\Db\CmsPages\CmsPagesTable;
use PeskyCMS\Scaffolds\Utils\CmsPagesScaffoldsHelper;
use Swayok\Utils\Set;

class CmsMenusScaffoldConfig extends NormalTableScaffoldConfig {

    protected $isDetailsViewerAllowed = true;
    protected $isCreateAllowed = true;
    protected $isEditAllowed = true;
    protected $isDeleteAllowed = true;

    public static function getTable() {
        return CmsPagesTable::getInstance();
    }

    public static function getResourceName() {
        return 'cms_menus';
    }

    static protected function getIconForMenuItem() {
        return 'fa fa-list';
    }

    protected function createDataGridConfig() {
        return parent::createDataGridConfig()
            ->setSpecialConditions(function () {
                /** @var CmsPage $pageClass */
                $pageClass = get_class(static::getTable()->newRecord());
                return [
                    'type' => $pageClass::TYPE_MENU,
                ];
            })
            ->setColumns([
                'id' => DataGridColumn::create()
                    ->setWidth(40),
                'title',
                'page_code',
            ])
            ->setFilterIsOpenedByDefault(false);
    }
    
    protected function createDataGridFilterConfig() {
        return parent::createDataGridFilterConfig()
            ->setFilters([
                'id',
                'title',
                'page_code',
            ]);
    }

    protected function createItemDetailsConfig() {
        $itemDetailsConfig = parent::createItemDetailsConfig();
        $itemDetailsConfig
            ->readRelations([
                'Admin', 'Texts'
            ])
            ->addTab($this->translate('item_details.tab', 'general'), [
                'id',
                'title',
                'page_code',
                'created_at',
                'updated_at',
                'admin_id' => ValueCell::create()
                    ->setType(ValueCell::TYPE_LINK),
            ])
            ->setRawRecordDataModifier(function ($record) {
                if (!empty($record['Texts'])) {
                    $record['Texts'] = Set::combine($record['Texts'], '/language', '/');
                }
                return $record;
            });
        foreach (setting()->languages() as $langId => $langLabel) {
            $itemDetailsConfig->addTab($this->translate('item_details.tab', 'texts', ['language' => $langLabel]), [
                "texts:$langId.id" => ValueCell::create()->setNameForTranslation('texts.id'),
                "texts:$langId.language" => ValueCell::create()
                    ->setNameForTranslation('texts.language')
                    ->setValueConverter(function () use ($langLabel) {
                        return $langLabel;
                    }),
                "texts:$langId.menu_title" => ValueCell::create()->setNameForTranslation('texts.menu_title'),
//                "texts:$langId.comment" => ValueCell::create()->setNameForTranslation('texts.comment'),
                "texts:$langId.content" => ValueCell::create()
                    ->setType(ValueCell::TYPE_HTML)
                    ->setNameForTranslation('texts.content'),
            ]);
        }
        return $itemDetailsConfig;
    }
    
    protected function createFormConfig() {
        $formConfig = parent::createFormConfig();
        /** @var CmsPage $pageClass */
        $pageClass = get_class(static::getTable()->newRecord());
        $formConfig
            ->setWidth(80)
            ->addTab($this->translate('form.tab', 'general'), [
                'title' => FormInput::create()
                    ->setDefaultRendererConfigurator(function (InputRenderer $renderer) {
                        $renderer->required();
                    }),
                'page_code' => FormInput::create()
                    ->setDefaultRendererConfigurator(function (InputRenderer $renderer) {
                        $renderer->addAttribute('data-regexp', '^[a-zA-Z0-9_:-]+$');
                    }),
                'comment',
                'type' => FormInput::create()
                    ->setType(FormInput::TYPE_HIDDEN),
                'is_published' => FormInput::create(),
                'admin_id' => FormInput::create()
                    ->setType(FormInput::TYPE_HIDDEN)
            ])
            ->setValidators(function () {
                return [
                    'title' => 'required|string|max:500',
                    'comment' => 'nullable|string|max:1000',
                    'page_code' => 'nullable|regex:%^[a-zA-Z0-9_:-]*$%|unique: ' . static::getTable()->getName() . ',page_code,{{id}},id',
                    'texts' => 'nullable|array'
                ];
            })
            ->setIncomingDataModifier(function (array $data) use ($pageClass) {
                return CmsPagesScaffoldsHelper::modifyIncomingData($this, $data, $pageClass::TYPE_MENU);
            });

//        if ($pagesTable->getTableStructure()->images->hasImagesConfigurations()) {
//            $formConfig->addTab($this->translate('form.tab', 'images'), [
//                'images' => ImagesFormInput::create(),
//            ]);
//        }
        foreach (setting()->languages() as $langId => $langLabel) {
            $formConfig->addTab($this->translate('form.tab', 'texts', ['language' => $langLabel]), [
                "texts:$langId.id" => FormInput::create()->setType(FormInput::TYPE_HIDDEN),
                "texts:$langId.menu_title" => FormInput::create()->setNameForTranslation('texts.menu_title'),
                "texts:$langId.comment" => FormInput::create()->setNameForTranslation('texts.comment'),
                "texts:$langId.content" => WysiwygFormInput::create()
                    ->setRelativeImageUploadsFolder('/assets/wysiwyg/pages')
                    ->setDataInserts(function () {
                        return $this->getDataInsertsForContentEditor();
                    })
                    ->setHtmlInserts(function () {
                        return CmfConfig::getPrimary()->getWysywygHtmlInsertsForCmsPages($this);
                    })
                    ->setNameForTranslation('texts.content'),
                "texts:$langId.language" => FormInput::create()
                    ->setType(FormInput::TYPE_HIDDEN)
                    ->setSubmittedValueModifier(function () use ($langId) {
                        return $langId;
                    }),
                "texts:$langId.admin_id" => FormInput::create()
                    ->setType(FormInput::TYPE_HIDDEN)
            ]);
        }
        return $formConfig;
    }

    protected function getDataInsertsForContentEditor() {
        return CmsPagesScaffoldsHelper::getConfigsForWysiwygDataInserts($this);
    }

    public function getCustomData($dataId) {
         $data = CmsPagesScaffoldsHelper::getDataForWysiwygInserts($this, $dataId, (int)request()->query('pk', 0));
         return ($data === null) ? parent::getCustomData($dataId) : $data;
    }
}