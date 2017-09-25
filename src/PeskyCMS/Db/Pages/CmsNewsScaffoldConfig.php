<?php

namespace PeskyCMS\Db\Pages;

use PeskyCMF\Config\CmfConfig;
use PeskyCMF\Scaffold\DataGrid\DataGridColumn;
use PeskyCMF\Scaffold\Form\FormInput;
use PeskyCMF\Scaffold\Form\InputRenderer;
use PeskyCMF\Scaffold\Form\WysiwygFormInput;
use PeskyCMF\Scaffold\ItemDetails\ValueCell;
use PeskyCMF\Scaffold\NormalTableScaffoldConfig;
use Swayok\Utils\Set;

class CmsNewsScaffoldConfig extends NormalTableScaffoldConfig {

    protected $isDetailsViewerAllowed = true;
    protected $isCreateAllowed = true;
    protected $isEditAllowed = true;
    protected $isDeleteAllowed = true;
    
    protected function createDataGridConfig() {
        return parent::createDataGridConfig()
            ->setSpecialConditions(function () {
                /** @var CmsPage $pageClass */
                $pageClass = app(CmsPage::class);
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
        /** @var CmsPagesTable $pagesTable */
//        $pagesTable = app(CmsPagesTable::class);
//        if ($pagesTable->getTableStructure()->images->hasImagesConfigurations()) {
//            $itemDetailsConfig->addTab($this->translate('item_details.tab', 'images'), [
//                'images',
//            ]);
//        }
        foreach (setting()->languages() as $langId => $langLabel) {
            $itemDetailsConfig->addTab($this->translate('item_details.tab', 'texts', ['language' => $langLabel]), [
                "Texts.$langId.id" => ValueCell::create()->setNameForTranslation('Texts.id'),
                "Texts.$langId.language" => ValueCell::create()
                    ->setNameForTranslation('Texts.language')
                    ->setValueConverter(function () use ($langLabel) {
                        return $langLabel;
                    }),
                "Texts.$langId.browser_title" => ValueCell::create()->setNameForTranslation('Texts.browser_title'),
                "Texts.$langId.menu_title" => ValueCell::create()->setNameForTranslation('Texts.menu_title'),
                "Texts.$langId.meta_description" => ValueCell::create()->setNameForTranslation('Texts.meta_description'),
                "Texts.$langId.meta_keywords" => ValueCell::create()->setNameForTranslation('Texts.meta_keywords'),
//                "Texts.$langId.comment" => ValueCell::create()->setNameForTranslation('Texts.comment'),
                "Texts.$langId.content" => ValueCell::create()
                    ->setType(ValueCell::TYPE_HTML)
                    ->setNameForTranslation('Texts.content'),

            ]);
        }
        return $itemDetailsConfig;
    }
    
    protected function createFormConfig() {
        $formConfig = parent::createFormConfig();
        /** @var CmsPagesTable $pagesTable */
        $pagesTable = app(CmsPagesTable::class);
        /** @var CmsPage $pageClass */
        $pageClass = app(CmsPage::class);
        $formConfig
            ->setWidth(80)
            ->addTab($this->translate('form.tab', 'general'), [
                'title',
                'parent_id' => FormInput::create()
                    ->setType(FormInput::TYPE_SELECT)
                    ->setOptionsLoader(function ($pkValue) use ($pagesTable, $pageClass) {
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
            ->setValidators(function () use ($pagesTable) {
                $pagesTable::registerUniquePageUrlValidator($this);
                $validators = [
                    'is_published' => 'required|boolean',
                    'publish_at' => 'required|date',
                    'title' => 'string|max:500',
                    'comment' => 'string|max:1000',
                ];
                foreach (setting()->languages() as $lang => $lebel) {
                    $validators["Texts.$lang.browser_title"] = "required_with:Texts.$lang.content";
                }
                return $validators;
            })
            ->addValidatorsForCreate(function () {
                return [
                    'url_alias' => 'required|regex:%^/[a-z0-9_/-]*$%|unique_page_url',
                    'page_code' => 'regex:%^[a-zA-Z0-9_:-]*$%|unique:pages,page_code',
                ];
            })
            ->addValidatorsForEdit(function () {
                return [
                    'url_alias' => 'required|regex:%^/[a-z0-9_/-]*$%|unique_page_url',
                    'page_code' => 'regex:%^[a-zA-Z0-9_:-]*$%|unique:pages,page_code,{{id}},id',
                ];
            })
            ->setRawRecordDataModifier(function (array $record) {
                if (!empty($record['Texts'])) {
                    $record['Texts'] = Set::combine($record['Texts'], '/language', '/');
                }
                return $record;
            })
            ->setIncomingDataModifier(function (array $data) use ($pageClass) {
                if (!empty($data['Texts']) && is_array($data['Texts'])) {
                    foreach ($data['Texts'] as $i => &$textData) {
                        if (empty($textData['id'])) {
                            unset($textData['id']);
                        }
                    }
                }
                unset($textData);
                $data['type'] = $pageClass::TYPE_NEWS;
                $data['admin_id'] = \Auth::guard()->user()->getAuthIdentifier();
                return $data;
            });

//        if ($pagesTable->getTableStructure()->images->hasImagesConfigurations()) {
//            $formConfig->addTab($this->translate('form.tab', 'images'), [
//                'images' => ImagesFormInput::create(),
//            ]);
//        }
        foreach (setting()->languages() as $langId => $langLabel) {
            $formConfig->addTab($this->translate('form.tab', 'texts', ['language' => $langLabel]), [
                "Texts.$langId.id" => FormInput::create()->setType(FormInput::TYPE_HIDDEN),
                "Texts.$langId.browser_title" => FormInput::create()->setNameForTranslation('Texts.browser_title'),
                "Texts.$langId.menu_title" => FormInput::create()->setNameForTranslation('Texts.menu_title'),
                "Texts.$langId.meta_description" => FormInput::create()->setNameForTranslation('Texts.meta_description'),
                "Texts.$langId.meta_keywords" => FormInput::create()->setNameForTranslation('Texts.meta_keywords'),
                "Texts.$langId.comment" => FormInput::create()->setNameForTranslation('Texts.comment'),
                "Texts.$langId.content" => WysiwygFormInput::create()
                    ->setRelativeImageUploadsFolder('/assets/wysiwyg/pages')
                    ->setDataInserts(function () {
                        return $this->getDataInsertsForContentEditor();
                    })
                    ->setHtmlInserts(function () {
                        return CmfConfig::getPrimary()->getWysywygHtmlInsertsForCmsPages($this);
                    })
                    ->setNameForTranslation('Texts.content'),
                "Texts.$langId.language" => FormInput::create()
                    ->setType(FormInput::TYPE_HIDDEN)
                    ->setSubmittedValueModifier(function () use ($langId) {
                        return $langId;
                    }),
                "Texts.$langId.admin_id" => FormInput::create()
                    ->setType(FormInput::TYPE_HIDDEN)
                    ->setSubmittedValueModifier(function () {
                        return \Auth::guard()->user()->getAuthIdentifier();
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