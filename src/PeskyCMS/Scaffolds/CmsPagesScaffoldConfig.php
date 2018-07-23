<?php

namespace PeskyCMS\Scaffolds;

use PeskyCMF\Config\CmfConfig;
use PeskyCMF\Scaffold\DataGrid\DataGridColumn;
use PeskyCMF\Scaffold\DataGrid\DataGridConfig;
use PeskyCMF\Scaffold\Form\FormInput;
use PeskyCMF\Scaffold\Form\ImagesFormInput;
use PeskyCMF\Scaffold\Form\InputRenderer;
use PeskyCMF\Scaffold\Form\WysiwygFormInput;
use PeskyCMF\Scaffold\ItemDetails\ValueCell;
use PeskyCMF\Scaffold\NormalTableScaffoldConfig;
use PeskyCMS\Db\CmsPages\CmsPage;
use PeskyCMS\Db\CmsPages\CmsPagesTable;
use PeskyCMS\Scaffolds\Utils\CmsPagesScaffoldsHelper;
use Swayok\Html\Tag;

class CmsPagesScaffoldConfig extends NormalTableScaffoldConfig {

    protected $isDetailsViewerAllowed = true;
    protected $isCreateAllowed = true;
    protected $isEditAllowed = true;
    protected $isDeleteAllowed = true;

    public static function getTable() {
        return CmsPagesTable::getInstance();
    }

    public static function getResourceName() {
        return 'cms_pages';
    }

    static protected function getIconForMenuItem() {
        return 'fa fa-file-text-o';
    }
    
    protected function createDataGridConfig() {
        return parent::createDataGridConfig()
            ->setSpecialConditions(function () {
                /** @var CmsPage $pageClass */
                $pageClass = get_class(static::getTable()->newRecord());
                return [
                    'type' => $pageClass::TYPE_PAGE,
                ];
            })
            ->enableNestedView()
            ->readRelations([
                'Parent' => ['id', 'url_alias', 'parent_id']
            ])
            ->setOrderBy('id', 'asc')
            ->setAdditionalColumnsToSelect('url_alias')
            ->setColumns([
                DataGridConfig::ROW_ACTIONS_COLUMN_NAME,
                'id' => DataGridColumn::create()
                    ->setWidth(40),
                'title',
                'relative_url' => DataGridColumn::create()
                    ->setIsSortable(false),
                'page_code',
                'is_published',
            ])
            ->setIsRowActionsColumnFixed(false)
            ->setFilterIsOpenedByDefault(false);
    }
    
    protected function createDataGridFilterConfig() {
        return parent::createDataGridFilterConfig()
            ->setFilters([
                'id',
                'title',
                'url_alias',
                'page_code',
                'is_published',
                'Parent.id',
                'Parent.url_alias',
                'Parent.title'
            ]);
    }

    protected function createItemDetailsConfig() {
        $itemDetailsConfig = parent::createItemDetailsConfig();
        $itemDetailsConfig
            ->readRelations([
                'Parent' => ['*'],
                'Admin' => ['*']
            ])
            ->setAdditionalColumnsToSelect('url_alias')
            ->addTab($this->translate('item_details.tab', 'general'), [
                'id',
                'title',
                'relative_url' => ValueCell::create()
                    ->setValueConverter(function ($value) {
                        $url = request()->getSchemeAndHttpHost() . $value;
                        return Tag::a()
                            ->setHref($url)
                            ->setContent($url)
                            ->setTarget('_blank')
                            ->build();
                    }),
                'page_code',
                'parent_id' => ValueCell::create()
                    ->setType(ValueCell::TYPE_LINK),
//                'order',
//                'custom_info',
                'is_published',
                'created_at',
                'updated_at',
                'admin_id' => ValueCell::create()
                    ->setType(ValueCell::TYPE_LINK),
            ])
            ->setRawRecordDataModifier(function ($record) {
                /*if (!empty($record['Texts'])) {
                    $record['Texts'] = Set::combine($record['Texts'], '/language', '/');
                }*/
                return $record;
            });
        if (static::getTable()->getTableStructure()->images->hasImagesGroupsConfigurations()) {
            $itemDetailsConfig->addTab($this->translate('item_details.tab', 'images'), [
                'images',
            ]);
        }
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
                'title' => FormInput::create()
                    ->setDefaultRendererConfigurator(function (InputRenderer $renderer) {
                        $renderer->setIsRequired(true);
                    }),
                'parent_id' => FormInput::create()
                    ->setType(FormInput::TYPE_SELECT)
                    ->setOptionsLoader(function ($pkValue) use ($pageClass) {
                        return CmsPagesScaffoldsHelper::getPagesUrlsOptions($pageClass::TYPE_PAGE, (int)$pkValue);
                    })
                    ->setDefaultRendererConfigurator(function (InputRenderer $renderer) {
                        $renderer
                            ->addAttribute('class', 'form-control', true)
                            ->addData('isHidden', true);
                    }),
                'url_alias' => FormInput::create()
                    ->setDefaultRendererConfigurator(function (InputRenderer $rendererConfig) use ($formConfig) {
                        $rendererConfig
                            ->setIsRequired(true)
                            ->setPrefixText('<div class="ib" id="parent-id-url-alias"></div>')
                            ->addAttribute('data-regexp', '^/[a-z0-9_/-]+$')
                            ->addAttribute('placeholder', $this->translate('form.input', 'url_alias_placeholder'));
                    })
                    ->setSubmittedValueModifier(function ($value) {
                        return $value === '/' ? $value : preg_replace('%//+%', '/', rtrim($value, '/'));
                    })
                    ->addJavaScriptBlock(function (FormInput $valueViewer) {
                        return CmsPagesScaffoldsHelper::getJsCodeForUrlAliasInput($valueViewer);
                    }),
                'page_code' => FormInput::create()
                    ->setDefaultRendererConfigurator(function (InputRenderer $renderer) {
                        $renderer->addAttribute('data-regexp', '^[a-zA-Z0-9_:-]+$');
                    }),
                'comment',
//                'with_contact_form',
                'is_published',
                'type' => FormInput::create()
                    ->setType(FormInput::TYPE_HIDDEN),
                'admin_id' => FormInput::create()
                    ->setType(FormInput::TYPE_HIDDEN)
            ])
            ->setValidators(function () {
                static::getTable()->registerUniquePageUrlValidator($this);
                $validators = [
                    'is_published' => 'required|bool',
                    'title' => 'required|string|max:500',
                    'comment' => 'nullable|string|max:1000',
                    'url_alias' => 'required|regex:%^/[a-z0-9_/-]*$%|unique_page_url',
                    'page_code' => 'nullable|regex:%^[a-zA-Z0-9_:-]*$%|unique:' . static::getTable()->getName() . ',page_code,{{id}},id',
                ];
                foreach (setting()->languages() as $lang => $lebel) {
                    $validators["texts.$lang.browser_title"] = "required_with:texts.$lang.content";
                }
                return $validators;
            })
            ->setIncomingDataModifier(function (array $data) use ($pageClass) {
                return CmsPagesScaffoldsHelper::modifyIncomingData($this, $data, $pageClass::TYPE_PAGE);
            });

        if (static::getTable()->getTableStructure()->images->hasImagesGroupsConfigurations()) {
            $formConfig->addTab($this->translate('form.tab', 'images'), [
                'images' => ImagesFormInput::create(),
            ]);
        }
        foreach (setting()->languages() as $langId => $langLabel) {
            $formConfig->addTab($this->translate('form.tab', 'texts', ['language' => $langLabel]), [
                "texts:$langId.browser_title" => FormInput::create()
                    ->setNameForTranslation('texts.browser_title'),
                "texts:$langId.menu_title" => FormInput::create()
                    ->setNameForTranslation('texts.menu_title'),
                "texts:$langId.meta_description" => FormInput::create()
                    ->setNameForTranslation('texts.meta_description'),
                "texts:$langId.meta_keywords" => FormInput::create()
                    ->setNameForTranslation('texts.meta_keywords'),
                "texts:$langId.comment" => FormInput::create()
                    ->setNameForTranslation('texts.comment'),
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