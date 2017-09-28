<?php

namespace PeskyCMS\Providers;

use PeskyCMF\PeskyCmfAppSettings;
use PeskyCMF\Providers\PeskyCmfServiceProvider;
use PeskyCMF\Scaffold\ScaffoldConfig;
use PeskyCMS\CmsFrontendUtils;
use PeskyCMS\Console\Commands\CmsAddAdmin;
use PeskyCMS\Console\Commands\CmsInstall;
use PeskyORM\ORM\RecordInterface;
use PeskyORM\ORM\TableInterface;
use PeskyORM\ORM\TableStructureInterface;

class PeskyCmsServiceProvider extends PeskyCmfServiceProvider {

    public function register() {
        parent::register();

        $this->mergeConfigFrom($this->getCmsConfigFilePath(), 'peskycms');
    }

    public function boot() {
        parent::boot();
        require_once __DIR__ . '/../Config/helpers.php';
        CmsFrontendUtils::registerBladeDirectiveForStringTemplateRendering();
    }

    public function provides() {
        return array_merge(parent::provides(), [
            PeskyCmfAppSettings::class
        ]);
    }

    protected function configurePublishes() {
        parent::configurePublishes();
        $this->publishes([
            $this->getCmsConfigFilePath() => config_path('peskycms.php'),
        ], 'config');
    }

    protected function registerCommands() {
        parent::registerCommands();
        $this->registerCmsInstallCommand();
        $this->registerCmsAddAdminCommand();
    }

    protected function getCmsConfigFilePath() {
        return __DIR__ . '/../Config/peskycms.config.php';
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

    protected function getScaffoldConfigs() {
        $scaffolds = parent::getScaffoldConfigs();
        /** @var ScaffoldConfig[] $resources */
        $resources = (array)config('peskycms.resources', []);
        foreach ($resources as $scaffoldConfig) {
            if (!array_key_exists($scaffoldConfig::getResourceName(), $scaffolds)) {
                $scaffolds[$scaffoldConfig::getResourceName()] = $scaffoldConfig;
            }
        }
        return $scaffolds;
    }

    protected function registerDbClasses() {
        parent::registerDbClasses();
        $dbClasses = (array)config('peskycms.register_db_classes', []);
        foreach ($dbClasses as $singleton => $class) {
            if (is_int($singleton)) {
                $singleton = $class;
            }
            if (method_exists($class, 'getInstance')) {
                $this->registerClassInstanceSingleton($singleton, $class);
            } else {
                $this->registerClassNameSingleton($singleton, $class);
            }
        }
    }

    // admins

    /*public function registerAdminsDbClasses() {
        $this->registerAdminsDbRecordClassName();
        $this->registerAdminsDbTable();
        $this->registerAdminsDbTableStructure();
    }

    public function registerAdminsDbRecordClassName() {
        $this->app->singleton(CmfAdmin::class, function () {
            // note: do not create record here or you will possibly encounter infinite loop because this class may be
            // used in TableStructure via app(NameTableStructure) (for example to get default value, etc)
            return CmfAdmin::class;
        });
    }

    public function registerAdminsDbTable() {
        $this->app->singleton(CmfAdminsTable::class, function () {
            return CmfAdminsTable::getInstance();
        });
    }

    public function registerAdminsDbTableStructure() {
        $this->app->singleton(CmfAdminsTableStructure::class, function () {
            return CmfAdminsTableStructure::getInstance();
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
        $this->app->singleton(CmfSetting::class, function () {
            // note: do not create record here or you will possibly encounter infinite loop because this class may be
            // used in TableStructure via app(NameTableStructure) (for example to get default value, etc)
            return CmfSetting::class;
        });
    }

    public function registerSettingsDbTable() {
        $this->app->singleton(CmfSettingsTable::class, function () {
            return CmfSettingsTable::getInstance();
        });
    }

    public function registerSettingsDbTableStructure() {
        $this->app->singleton(CmfSettingsTableStructure::class, function () {
            return CmfSettingsTableStructure::getInstance();
        });
    }

    public function registerAppSettingsClass() {
        $this->app->singleton(PeskyCmfAppSettings::class, function () {
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
    }*/

}