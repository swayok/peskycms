<?php

namespace PeskyCMS\Scaffolds;

use PeskyCMF\Config\CmfConfig;
use PeskyCMF\Scaffold\DataGrid\DataGridColumn;
use PeskyCMF\Scaffold\Form\FormInput;
use PeskyCMF\Scaffold\Form\InputRenderer;
use PeskyCMF\Scaffold\Form\WysiwygFormInput;
use PeskyCMF\Scaffold\ItemDetails\ValueCell;
use PeskyCMF\Scaffold\NormalTableScaffoldConfig;
use PeskyCMS\Db\Pages\CmsPage;
use PeskyCMS\Db\Pages\CmsPagesTable;
use PeskyCMS\Scaffolds\Utils\CmsPagesScaffoldsHelper;
use Swayok\Utils\Set;

class CmsTextElementsScaffoldConfig extends NormalTableScaffoldConfig {

    protected $isDetailsViewerAllowed = true;
    protected $isCreateAllowed = true;
    protected $isEditAllowed = true;
    protected $isDeleteAllowed = true;

    public static function getTable() {
        return CmsPagesTable::getInstance();
    }

    public static function getResourceName() {
        return 'cms_text_elements';
    }

    static protected function getIconForMenuItem() {
        return 'fa fa-files-o';
    }
    
    protected function createDataGridConfig() {
        return parent::createDataGridConfig()
            ->setSpecialConditions(function () {
                /** @var CmsPage $pageClass */
                $pageClass = app(CmsPage::class);
                return [
                    'type' => $pageClass::TYPE_TEXT_ELEMENT,
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
        /** @var CmsPage $pageClass */
        $pageClass = app(CmsPage::class);
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
                'is_published' => FormInput::create()
                    ->setType(FormInput::TYPE_HIDDEN),
                'admin_id' => FormInput::create()
                    ->setType(FormInput::TYPE_HIDDEN)
            ])
            ->setValidators(function () {
                return [
                    'title' => 'required|string|max:500',
                    'comment' => 'string|max:1000',
                ];
            })
            ->addValidatorsForCreate(function () {
                return [
                    'page_code' => 'regex:%^[a-zA-Z0-9_:-]*$%|unique:pages,page_code',
                ];
            })
            ->addValidatorsForEdit(function () {
                return [
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
                $data['admin_id'] = static::getUser()->id;
                $data['type'] = $pageClass::TYPE_TEXT_ELEMENT;
                $data['is_published'] = true;
                if (!empty($data['Texts']) && is_array($data['Texts'])) {
                    foreach ($data['Texts'] as $i => &$textData) {
                        if (empty($textData['id'])) {
                            unset($textData['id']);
                        }
                        $textData['admin_id'] = $data['admin_id'];
                    }
                }
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