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

class CmsNewsScaffoldConfig extends NormalTableScaffoldConfig {

    protected $isDetailsViewerAllowed = true;
    protected $isCreateAllowed = true;
    protected $isEditAllowed = true;
    protected $isDeleteAllowed = true;

    public static function getTable() {
        return CmsPagesTable::getInstance();
    }

    public static function getResourceName() {
        return 'cms_news';
    }

    static protected function getIconForMenuItem() {
        return 'fa fa-newspaper-o';
    }
    
    protected function createDataGridConfig() {
        return parent::createDataGridConfig()
            ->setSpecialConditions(function () {
                /** @var CmsPage $pageClass */
                $pageClass = get_class(static::getTable()->newRecord());
                return [
                    'type' => $pageClass::TYPE_NEWS,
                ];
            })
            ->setOrderBy('publish_at', 'desc')
            ->readRelations([
                'Parent' => ['id', 'url_alias', 'parent_id']
            ])
            ->setInvisibleColumns('url_alias')
            ->setColumns([
                'id' => DataGridColumn::create()
                    ->setWidth(40),
                'title',
                'relative_url',
                'is_published',
                'publish_at',
            ])
            ->setFilterIsOpenedByDefault(false);
    }
    
    protected function createDataGridFilterConfig() {
        return parent::createDataGridFilterConfig()
            ->setFilters([
                'id',
                'title',
                'url_alias',
                'is_published',
                'publish_at',
                'Parent.id',
                'Parent.title',
                'Parent.url_alias',
            ]);
    }

    protected function createItemDetailsConfig() {
        $itemDetailsConfig = parent::createItemDetailsConfig();
        $itemDetailsConfig
            ->readRelations([
                'Parent', 'Admin', 'Texts'
            ])
            ->addTab($this->translate('item_details.tab', 'general'), [
                'id',
                'title',
                'relative_url',
                'parent_id' => ValueCell::create()
                    ->setType(ValueCell::TYPE_LINK),
//                'order',
//                'custom_info',
                'is_published',
                'publish_at',
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
//        if (static::getTable()->getTableStructure()->images->hasImagesConfigurations()) {
//            $itemDetailsConfig->addTab($this->translate('item_details.tab', 'images'), [
//                'images',
//            ]);
//        }
        foreach (setting()->languages() as $langId => $langLabel) {
            $itemDetailsConfig->addTab($this->translate('item_details.tab', 'texts', ['language' => $langLabel]), [
                "texts:$langId.id" => ValueCell::create()->setNameForTranslation('texts.id'),
                "texts:$langId.language" => ValueCell::create()
                    ->setNameForTranslation('texts.language')
                    ->setValueConverter(function () use ($langLabel) {
                        return $langLabel;
                    }),
                "texts:$langId.browser_title" => ValueCell::create()->setNameForTranslation('texts.browser_title'),
                "texts:$langId.menu_title" => ValueCell::create()->setNameForTranslation('texts.menu_title'),
                "texts:$langId.meta_description" => ValueCell::create()->setNameForTranslation('texts.meta_description'),
                "texts:$langId.meta_keywords" => ValueCell::create()->setNameForTranslation('texts.meta_keywords'),
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
                'title',
                'parent_id' => FormInput::create()
                    ->setType(FormInput::TYPE_SELECT)
                    ->setOptionsLoader(function ($pkValue) use ($pageClass) {
                        return CmsPagesScaffoldsHelper::getPagesUrlsOptions($pageClass::TYPE_NEWS, (int)$pkValue);
                    })
                    ->setDefaultRendererConfigurator(function (InputRenderer $renderer) {
                        $renderer->addData('isHidden', true);
                    }),
                'url_alias' => FormInput::create()
                    ->setDefaultRendererConfigurator(function (InputRenderer $rendererConfig) use ($formConfig) {
                        $rendererConfig
                            ->setIsRequired(true)
                            ->setPrefixText('<span id="parent-id-url-alias"></span>')
                            ->addAttribute('data-regexp', '^[a-z0-9_/-]+$')
                            ->addAttribute('placeholder', $this->translate('form.input', 'url_alias_placeholder'));
                    })
                    ->setSubmittedValueModifier(function ($value) {
                        return $value === '/' ? $value : preg_replace('%//+%', '/', rtrim($value, '/'));
                    })
                    ->addJavaScriptBlock(function (FormInput $formInput) {
                        return CmsPagesScaffoldsHelper::getJsCodeForUrlAliasInput($formInput);
                    }),
                'comment',
                'is_published',
                'publish_at' => FormInput::create()
                    ->setType(FormInput::TYPE_DATE)
                    ->setDefaultRendererConfigurator(function (InputRenderer $renderer) {
                        $renderer->addData('config', [
                            'minDate' => null,
                            'useCurrent' => true
                        ]);
                    }),
                'type' => FormInput::create()
                    ->setType(FormInput::TYPE_HIDDEN),
                'admin_id' => FormInput::create()
                    ->setType(FormInput::TYPE_HIDDEN),
            ])
            ->setValidators(function () {
                static::getTable()->registerUniquePageUrlValidator($this);
                $validators = [
                    'is_published' => 'required|boolean',
                    'publish_at' => 'required|date',
                    'title' => 'required|string|max:500',
                    'comment' => 'nullable|string|max:1000',
                    'url_alias' => 'required|regex:%^/[a-z0-9_/-]*$%|unique_page_url',
                    'page_code' => 'regex:%^[a-zA-Z0-9_:-]*$%|unique:' . static::getTable()->getName() . ',page_code',
                ];
                foreach (setting()->languages() as $lang => $lebel) {
                    $validators["texts.$lang.browser_title"] = "required_with:texts.$lang.content";
                }
                return $validators;
            })
            ->setIncomingDataModifier(function (array $data) use ($pageClass) {
                return CmsPagesScaffoldsHelper::modifyIncomingData($this, $data, $pageClass::TYPE_NEWS);
            });

//        if (static::getTable()->getTableStructure()->images->hasImagesConfigurations()) {
//            $formConfig->addTab($this->translate('form.tab', 'images'), [
//                'images' => ImagesFormInput::create(),
//            ]);
//        }
        foreach (setting()->languages() as $langId => $langLabel) {
            $formConfig->addTab($this->translate('form.tab', 'texts', ['language' => $langLabel]), [
                "texts:$langId.id" => FormInput::create()->setType(FormInput::TYPE_HIDDEN),
                "texts:$langId.browser_title" => FormInput::create()->setNameForTranslation('texts.browser_title'),
                "texts:$langId.menu_title" => FormInput::create()->setNameForTranslation('texts.menu_title'),
                "texts:$langId.meta_description" => FormInput::create()->setNameForTranslation('texts.meta_description'),
                "texts:$langId.meta_keywords" => FormInput::create()->setNameForTranslation('texts.meta_keywords'),
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
                    ->setSubmittedValueModifier(function () {
                        return static::getUser()->id;
                    }),
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