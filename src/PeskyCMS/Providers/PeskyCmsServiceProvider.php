<?php

namespace PeskyCMS\Providers;

use PeskyCMF\CMS\Redirects\CmsRedirect;
use PeskyCMF\CMS\Redirects\CmsRedirectsTable;
use PeskyCMF\CMS\Redirects\CmsRedirectsTableStructure;
use PeskyCMF\Providers\PeskyCmfServiceProvider;
use PeskyCMS\CmsAppSettings;
use PeskyCMS\CmsFrontendUtils;
use PeskyCMS\Console\Commands\CmsAddAdmin;
use PeskyCMS\Console\Commands\CmsInstall;

class PeskyCmsServiceProvider extends PeskyCmfServiceProvider {

    protected $appSettingsClass;

    public function register() {
        $this->mergeConfigFrom($this->getCmsConfigFilePath(), 'cms');
        $this->appSettingsClass = config('cms.app_settings_class') ?: CmsAppSettings::class;

        parent::register();

        //todo: move this to cms configs file

//        $this->registerAdminsDbClasses();
//        $this->registerSettingsDbClasses();
//        $this->registerPagesDbClasses();
//        $this->registerTextsDbClasses();
//        $this->registerRedirectsDbClasses();


        // note: scaffolds declared in CmsSiteLoader
    }

    protected function registerCommands() {
        parent::registerCommands();
        $this->registerCmsInstallCommand();
        $this->registerCmsAddAdminCommand();
    }

    public function boot() {
        parent::boot();
        require_once __DIR__ . '/../Config/helpers.php';
        CmsFrontendUtils::registerBladeDirectiveForStringTemplateRendering();
    }

    protected function getCmsConfigFilePath() {
        return __DIR__ . '/../Config/peskycms.config.php';
    }

    protected function getOrmConfigFilePath() {
        return __DIR__ . '/../Config/peskyorm.config.php';
    }

    protected function registerCmsInstallCommand() {
        $this->app->singleton('command.cms.install', function() {
            return new CmsInstall();
        });
        $this->commands('command.cms.install');
    }

    protected function registerCmsAddAdminCommand() {
        $this->app->singleton('command.cms.add-admin', function() {
            return new CmsAddAdmin();
        });
        $this->commands('command.cms.add-admin');
    }

    // admins

    public function registerAdminsDbClasses() {
        $this->registerAdminsDbRecordClassName();
        $this->registerAdminsDbTable();
        $this->registerAdminsDbTableStructure();
    }

    public function registerAdminsDbRecordClassName() {
        $this->app->singleton(CmsAdmin::class, function () {
            // note: do not create record here or you will possibly encounter infinite loop because this class may be
            // used in TableStructure via app(NameTableStructure) (for example to get default value, etc)
            return CmsAdmin::class;
        });
    }

    public function registerAdminsDbTable() {
        $this->app->singleton(CmsAdminsTable::class, function () {
            return CmsAdminsTable::getInstance();
        });
    }

    public function registerAdminsDbTableStructure() {
        $this->app->singleton(CmsAdminsTableStructure::class, function () {
            return CmsAdminsTableStructure::getInstance();
        });
    }

    // settings

    public function registerSettingsDbClasses() {
        $this->registerSettingsDbRecordClassName();
        $this->registerSettingsDbTable();
        $this->registerSettingsDbTableStructure();
        $this->registerAppSettingsClass();
    }

    public function registerSettingsDbRecordClassName() {
        $this->app->singleton(CmsSetting::class, function () {
            // note: do not create record here or you will possibly encounter infinite loop because this class may be
            // used in TableStructure via app(NameTableStructure) (for example to get default value, etc)
            return CmsSetting::class;
        });
    }

    public function registerSettingsDbTable() {
        $this->app->singleton(CmsSettingsTable::class, function () {
            return CmsSettingsTable::getInstance();
        });
    }

    public function registerSettingsDbTableStructure() {
        $this->app->singleton(CmsSettingsTableStructure::class, function () {
            return CmsSettingsTableStructure::getInstance();
        });
    }

    public function registerAppSettingsClass() {
        $this->app->singleton(CmsAppSettings::class, function () {
            return $this->appSettingsClass;
        });
    }

    // pages

    public function registerPagesDbClasses() {
        $this->registerPagesDbRecordClassName();
        $this->registerPagesDbTable();
        $this->registerPagesDbTableStructure();
    }

    public function registerPagesDbRecordClassName() {
        $this->app->singleton(CmsPage::class, function () {
            // note: do not create record here or you will possibly encounter infinite loop because this class may be
            // used in TableStructure via app(NameTableStructure) (for example to get default value, etc)
            return CmsPage::class;
        });
    }

    public function registerPagesDbTable() {
        $this->app->singleton(CmsPagesTable::class, function () {
            return CmsPagesTable::getInstance();
        });
    }

    public function registerPagesDbTableStructure() {
        $this->app->singleton(CmsPagesTableStructure::class, function () {
            return CmsPagesTableStructure::getInstance();
        });
    }

    // texts

    public function registerTextsDbClasses() {
        $this->registerTextsDbRecordClassName();
        $this->registerTextsDbTable();
        $this->registerTextsDbTableStructure();
    }

    public function registerTextsDbRecordClassName() {
        $this->app->singleton(CmsText::class, function () {
            // note: do not create record here or you will possibly encounter infinite loop because this class may be
            // used in TableStructure via app(NameTableStructure) (for example to get default value, etc)
            return CmsText::class;
        });
    }

    public function registerTextsDbTable() {
        $this->app->singleton(CmsTextsTable::class, function () {
            return CmsTextsTable::getInstance();
        });
    }

    public function registerTextsDbTableStructure() {
        $this->app->singleton(CmsTextsTableStructure::class, function () {
            return CmsTextsTableStructure::getInstance();
        });
    }

    // redirects

    public function registerRedirectsDbClasses() {
        $this->registerRedirectsDbRecordClassName();
        $this->registerRedirectsDbTable();
        $this->registerRedirectsDbTableStructure();
    }

    public function registerRedirectsDbRecordClassName() {
        $this->app->singleton(CmsRedirect::class, function () {
            // note: do not create record here or you will possibly encounter infinite loop because this class may be
            // used in TableStructure via app(NameTableStructure) (for example to get default value, etc)
            return CmsRedirect::class;
        });
    }

    public function registerRedirectsDbTable() {
        $this->app->singleton(CmsRedirectsTable::class, function () {
            return CmsRedirectsTable::getInstance();
        });
    }

    public function registerRedirectsDbTableStructure() {
        $this->app->singleton(CmsRedirectsTableStructure::class, function () {
            return CmsRedirectsTableStructure::getInstance();
        });
    }

}