<?php

namespace PeskyCMS\Scaffolds\Utils;

use PeskyCMF\Config\CmfConfig;
use PeskyCMF\PeskyCmfAppSettings;
use PeskyCMF\Scaffold\Form\FormInput;
use PeskyCMF\Scaffold\Form\WysiwygFormInput;
use PeskyCMF\Scaffold\ScaffoldConfig;
use PeskyCMS\Db\CmsPages\CmsPage;
use PeskyCMS\Db\CmsPages\CmsPagesTable;
use PeskyCMS\PeskyCmsAppSettings;

abstract class CmsPagesScaffoldsHelper {

    static public function getConfigsForWysiwygDataInserts(ScaffoldConfig $scaffold) {
        $ret = [
            WysiwygFormInput::createDataInsertConfigWithArguments(
                'insertCmsPageData(":page_id", ":page_field")',
                $scaffold->translate('form.input.content_inserts', 'part_of_other_page'),
                false,
                [
                    'page_id' => [
                        'label' => $scaffold->translate('form.input.content_inserts', 'page_id_arg_label'),
                        'type' => 'select',
                        'options' => routeToCmfTableCustomData($scaffold::getResourceName(), 'pages_for_inserts', true),
                    ],
                    'page_field' => [
                        'label' => $scaffold->translate('form.input.content_inserts', 'page_field_arg_label'),
                        'type' => 'select',
                        'options' => [
                            'menu_title' => $scaffold->translate('form.input', 'texts.menu_title'),
                            'content' => $scaffold->translate('form.input', 'texts.content'),
                        ],
                        'value' => 'content'
                    ]
                ],
                $scaffold->translate('form.input.content_inserts', 'page_insert_widget_title_template')
            ),
            WysiwygFormInput::createDataInsertConfigWithArguments(
                'insertLinkToCmsPage(":page_id", ":title")',
                $scaffold->translate('form.input.content_inserts', 'link_to_other_page'),
                false,
                [
                    'page_id' => [
                        'label' => $scaffold->translate('form.input.content_inserts', 'page_id_arg_label'),
                        'type' => 'select',
                        'options' => routeToCmfTableCustomData($scaffold::getResourceName(), 'pages_for_inserts', true),
                    ],
                    'title' => [
                        'label' => $scaffold->translate('form.input.content_inserts', 'page_link_title_arg_label'),
                        'type' => 'text',
                    ]
                ],
                $scaffold->translate('form.input.content_inserts', 'insert_link_to_page_widget_title_template')
            ),
            WysiwygFormInput::createDataInsertConfigWithArguments(
                'insertCmsPageData(":page_id", "content")',
                $scaffold->translate('form.input.content_inserts', 'text_block'),
                false,
                [
                    'page_id' => [
                        'label' => $scaffold->translate('form.input.content_inserts', 'text_block_id_arg_label'),
                        'type' => 'select',
                        'options' => routeToCmfTableCustomData($scaffold::getResourceName(), 'text_blocks_for_inserts', true),
                    ]
                ],
                $scaffold->translate('form.input.content_inserts', 'text_block_insert_widget_title_template')
            ),
        ];
        /** @var PeskyCmsAppSettings $appSettings */
        $appSettings = app(PeskyCmfAppSettings::class);
        foreach ($appSettings::getSettingsForWysiwygDataIsnserts() as $settingName) {
            $ret[] = WysiwygFormInput::createDataInsertConfig(
                "setting('$settingName')",
                cmfTransCustom('.settings.content_inserts.' . $settingName)
            );
        }
        return array_merge($ret, CmfConfig::getPrimary()->getAdditionalWysywygDataInsertsForCmsPages($scaffold));
    }

    /**
     * @param ScaffoldConfig $scaffold
     * @param string $dataId
     * @param null|int $currentPageId
     * @return array|null
     * @throws \UnexpectedValueException
     * @throws \PeskyORM\Exception\OrmException
     * @throws \PDOException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    static public function getDataForWysiwygInserts(ScaffoldConfig $scaffold, $dataId, $currentPageId = null) {
        /** @var CmsPagesTable $pagesTable */
        $pagesTable = $scaffold::getTable();
        /** @var CmsPage $pageClass */
        $pageClass = get_class($pagesTable->newRecord());
        if (empty($currentPageId)) {
            $currentPageId = 0;
        }
        switch ($dataId) {
            case 'text_blocks_for_inserts':
                return $pagesTable::selectAssoc('id', 'title', [
                    'type' => $pageClass::getTypesWithoutUrls(),
                    'id !=' => $currentPageId,
                ]);
            case 'pages_for_inserts':
                $pages = $pagesTable::select(['id', 'url_alias', 'type', 'Parent' => ['id', 'url_alias']], [
                    'type !=' => $pageClass::getTypesWithoutUrls(),
                    'id !=' => $currentPageId,
                ]);
                $options = [];
                $typesTrans = [];
                /** @var CmsPage $pageData */
                foreach ($pages as $pageData) {
                    if (!array_key_exists($pageData->type, $typesTrans)) {
                        $typesTrans[$pageData->type] = $scaffold->translate('type', $pageData->type);
                    }
                    if (!array_key_exists($typesTrans[$pageData->type], $options)) {
                        $options[$typesTrans[$pageData->type]] = [];
                    }
                    $options[$typesTrans[$pageData->type]][$pageData->id] = $pageData->relative_url;
                }
                return $options;
            default:
                return null;
        }
    }

    /**
     * @param string $pageType
     * @param null|int $currentPageId
     * @return array
     * @throws \UnexpectedValueException
     * @throws \PeskyORM\Exception\OrmException
     * @throws \PDOException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    static public function getPagesUrlsOptions($pageType, $currentPageId = null) {
        $pages = CmsPagesTable::select(['*', 'Parent' => ['*']], [
            'id !=' => (int)$currentPageId,
            'type' => $pageType,
            'url_alias !=' => '/'
        ]);
        $pages->optimizeIteration();
        /** @var PeskyCmsAppSettings $appSettings */
        $appSettings = app(PeskyCmfAppSettings::class);
        $urlPrefix = trim((string)$appSettings::cms_pages_url_prefix(), '/');
        if ($appSettings::cms_pages_use_relative_url()) {
            $baseUrl = '/' . $urlPrefix;
        } else {
            $baseUrl = rtrim(request()->getSchemeAndHttpHost() . '/' . $urlPrefix, '/');
        }
        $options = ['' => $baseUrl];
        /** @var CmsPage $page */
        foreach ($pages as $page) {
            $options[$page->id] = $baseUrl . $page->relative_url;
        }
        asort($options);
        return [] + $options;
    }

    /**
     * @param FormInput $formInput
     * @return string
     * @throws \PeskyCMF\Scaffold\ScaffoldSectionConfigException
     * @throws \UnexpectedValueException
     * @throws \PeskyCMF\Scaffold\ValueViewerConfigException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    static public function getJsCodeForUrlAliasInput(FormInput $formInput) {
        $parentIdInput = $formInput->getScaffoldSectionConfig()->getFormInput('parent_id');
        return <<<SCRIPT
            var parentIdSelect = $('#{$parentIdInput->getDefaultId()}');
            var parentIdSelectContainer = parentIdSelect.parent().removeClass('hidden');
            $('#parent-id-url-alias')
                .append(parentIdSelectContainer)
                .parent()
                    .addClass('pn');
            parentIdSelect.selectpicker();
            parentIdSelect
                .css('z-index', '1');
            parentIdSelectContainer
                .css('height', '32px')
                .css('min-width', '150px')
                .addClass('mn')
                .find('button.dropdown-toggle')
                    .addClass('br-n')
                    .css('position', 'relative')
                    .css('z-index', '2')
                    .css('height', '32px')
                    .css('line-height', '32px')
                    .css('padding', '1px 30px 0 10px')
                    .find('.filter-option')
                        .addClass('ib')
                        .css('position', 'static')
                        .css('padding', '0')
                        .end()
                    .end()
                .find('.dropdown.bootstrap-select')
                    .css('height', '32px')  
                    .css('line-height', '32px')  
                    .end()
                .find('div.dropdown-menu')
                    .css('margin', '1px 0 0 -1px');
                
SCRIPT;
    }

    static public function modifyIncomingData(ScaffoldConfig $scaffold, array $data, string $pageType) {
        if (!empty($data['texts']) && is_array($data['texts'])) {
            $texts = [];
            foreach ($data['texts'] as $key => $textData) {
                $content = array_get($textData, 'content', '');
                if (trim($content) === '') {
                    continue;
                }
                $texts[$key] = $textData;
            }
            $data['texts'] = $texts;
        }
        $data['type'] = $pageType;
        $data['admin_id'] = $scaffold::getUser()->id;
        return $data;
    }
}