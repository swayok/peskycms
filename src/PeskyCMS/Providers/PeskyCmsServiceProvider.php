<?php

namespace PeskyCMS\Providers;

use Illuminate\Support\ServiceProvider;
use PeskyCMS\CmsFrontendUtils;
use PeskyCMS\Console\Commands\CmsInstallCommand;

class PeskyCmsServiceProvider extends ServiceProvider {

    public function register() {
        $this->registerCommands();
    }

    public function boot() {
        require_once __DIR__ . '/../helpers.php';
        CmsFrontendUtils::registerBladeDirectiveForStringTemplateRendering();
    }

    protected function registerCommands() {
        if ($this->app->runningInConsole()) {
            $this->registerCmsInstallCommand();
        }
    }

    protected function registerCmsInstallCommand() {
        $this->app->singleton('command.cms.install', function() {
            return new CmsInstallCommand();
        });
        $this->commands('command.cms.install');
    }

}