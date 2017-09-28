<?php

namespace PeskyCMS\Console\Commands;

use PeskyCMF\Console\Commands\CmfInstall;
use PeskyCMF\Providers\PeskyCmfServiceProvider;
use PeskyCMS\Providers\PeskyCmsServiceProvider;
use PeskyORMLaravel\Providers\PeskyOrmServiceProvider;
use Swayok\Utils\File;

class CmsInstall extends CmfInstall {

    protected $description = 'Install PeskyCMS';
    protected $signature = 'cms:install';

    protected function extender() {
        // create peskycms.php in config_path() dir
        $peskyCmsConfigFilePath = config_path('peskycms.php');
        $writeCmsConfigFile = !File::exist($peskyCmsConfigFilePath) || $this->confirm('PeskyCMS config file ' . $peskyCmsConfigFilePath . ' already exist. Overwrite?');
        if ($writeCmsConfigFile) {
            File::load(__DIR__ . '/../../Config/peskycms.config.php')->copy($peskyCmsConfigFilePath, true, 0664);
        }

        $migrationsPath = database_path('migrations') . DIRECTORY_SEPARATOR;
        foreach (['pages', 'texts', 'redirects'] as $index => $tableName) {
            $this->addMigrationForTable($tableName, $index, $migrationsPath, 'Cms', 'PeskyCMS');
        }
    }

    protected function outro() {
        $this->line('Remeber to perform next steps to activate CMS:');
        $this->line('1. Add ' . PeskyCmsServiceProvider::class . ' to you app.providers config (if you do not use package discovery)');
        $this->line('2. Remove ' . PeskyCmfServiceProvider::class . ' and ' . PeskyOrmServiceProvider::class . ' from you app.providers config');
        $this->line('3. Run "php artisan migrate" to create tables in database');
        $this->line('4. Run "php artisan cms::add-admin your-email@address.com" to create superadmin for CMS');
    }


}