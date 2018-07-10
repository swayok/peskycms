<?php

namespace PeskyCMS\Console\Commands;

use PeskyCMF\CMS\Redirects\CmsRedirectsScaffoldConfig;
use PeskyCMF\Console\Commands\CmfCommand;
use PeskyCMS\Db\Pages\CmsMenusScaffoldConfig;
use PeskyCMS\Db\Pages\CmsNewsScaffoldConfig;
use PeskyCMS\Db\Pages\CmsPagesScaffoldConfig;
use PeskyCMS\Db\Pages\CmsTextElementsScaffoldConfig;
use PeskyCMS\Providers\PeskyCmsServiceProvider;

class CmsInstallCommand extends CmfCommand {

    protected $description = 'Install PeskyCMS';
    protected $signature = 'cms:install';

    public function fire() {
        // compatibility with Laravel <= 5.4
        $this->handle();
    }

    public function handle() {
        if ($this->confirm('Have you previously installed PeskyCMF?', false)) {
            $this->call('cmf:install');
        }
        $migrationsPath = database_path('migrations') . DIRECTORY_SEPARATOR;
        foreach (['pages', 'texts', 'redirects'] as $index => $tableName) {
            $this->addMigrationForTable($tableName, $index, $migrationsPath, 'Cms', 'PeskyCMS');
        }
        $this->extender();
        $this->outro();
    }

    protected function extender() {

    }

    protected function outro() {
        $this->line('1. Add ' . PeskyCmsServiceProvider::class . ' to you app.providers config (if you do not use package discovery)');
        $this->line('2. Run "php artisan migrate" to create tables in database');
        $this->line('3. Add next classes to your \'resourses\' array in admin\'s section config file (usually config/admin.php)');
        $this->line('    ' . CmsPagesScaffoldConfig::class . ':class');
        $this->line('    ' . CmsMenusScaffoldConfig::class . ':class');
        $this->line('    ' . CmsNewsScaffoldConfig::class . ':class');
        $this->line('    ' . CmsTextElementsScaffoldConfig::class . ':class');
        $this->line('    ' . CmsRedirectsScaffoldConfig::class . ':class');
    }


}