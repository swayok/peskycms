<?php

namespace PeskyCMS\Db\Settings;

use PeskyCMF\Http\CmfJsonResponse;
use PeskyCMF\HttpCode;
use PeskyCMF\Scaffold\KeyValueTableScaffoldConfig;
use PeskyCMS\CmsAppSettings;

class CmsSettingsScaffoldConfig extends KeyValueTableScaffoldConfig {

    protected $isDetailsViewerAllowed = false;
    protected $isCreateAllowed = false;
    protected $isEditAllowed = true;
    protected $isDeleteAllowed = false;

    protected function createFormConfig() {
        $formConfig = parent::createFormConfig()
            ->setWidth(50)
            ->setShowAsDialog(false);
        /** @var CmsAppSettings $cmsAppSettings */
        $cmsAppSettings = app(CmsAppSettings::class);
        $cmsAppSettings::configureScaffoldFormConfig($formConfig);
        $formConfig
            ->setValidators(function () use ($cmsAppSettings) {
                return $cmsAppSettings::getValidatorsForScaffoldFormConfig();
            })
            ->setIncomingDataModifier(function (array $data) use ($cmsAppSettings) {
                return $cmsAppSettings::modifyIncomingData($data);
            });
        return $formConfig;
    }

    public function renderTemplates() {
        return view(
            'cmf::scaffold.templates_for_settings',
            array_merge(
                $this->getConfigsForTemplatesRendering(),
                ['tableNameForRoutes' => $this->getTableNameForRoutes()]
            )
        )->render();
    }

    public function getRecordsForDataGrid() {
        throw new \BadMethodCallException('Section is not allowed');
    }

    public function getDefaultValuesForFormInputs() {
        return CmfJsonResponse::create([], HttpCode::MOVED_PERMANENTLY)
            ->setRedirect(routeToCmfItemEditForm($this->getTableNameForRoutes(), 'all'));
    }

    public function getRecordValues($ownerRecordId = null) {
        /** @var CmsAppSettings $appSettings */
        $appSettings = app(CmsAppSettings::class);
        $settings = $appSettings::getAllValues(true);
        $settings[static::getTable()->getPkColumnName()] = 0;
        return cmfJsonResponse()->setData($this->getFormConfig()->prepareRecord($settings));
    }

    public function updateBulkOfRecords() {
        throw new \BadMethodCallException('Action is not allowed');
    }

    public function deleteRecord($id) {
        throw new \BadMethodCallException('Action is not allowed');
    }

    public function deleteBulkOfRecords() {
        throw new \BadMethodCallException('Action is not allowed');
    }
}