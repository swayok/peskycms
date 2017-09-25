<?php

namespace PeskyCMS\Console\Commands;

use Illuminate\Console\Command;
use PeskyCMF\Providers\PeskyCmfServiceProvider;
use PeskyCMS\Providers\PeskyCmsServiceProvider;
use Swayok\Utils\File;
use Swayok\Utils\StringUtils;

class CmsInstall extends Command {

    protected $description = 'Install PeskyCMS';
    protected $signature = 'cms:install';

    public function fire() {
//        $viewsPath = __DIR__ . '/../../resources/views/install/cms/';
        $migrationsPath = database_path('migrations') . DIRECTORY_SEPARATOR;
        // remove laravel's migrations
        if (File::exist($migrationsPath . '2014_10_12_000000_create_users_table.php')) {
            File::remove();
        }
        if (File::exist($migrationsPath . '2014_10_12_100000_create_password_resets_table.php')) {
            File::remove();
        }
        // remove laravel's User model
        if (File::exist(app_path('User.php'))) {
            File::remove();
        }
        // add AppSettings class if not exists
        $appSettingsPath = app_path('AppSettings.php');
        if (!File::exist($appSettingsPath)) {
            File::save($appSettingsPath, $this->getAppSettignsClassContents());
            $this->line('Added ' . $appSettingsPath);
        } else {
            $this->line('- ' . $appSettingsPath . ' already exist. skipped');
        }
        foreach (['settings', 'admins', 'pages', 'texts', 'redirects'] as $index => $tableName) {
            $this->addMigrationForTable($tableName, $index, $migrationsPath);
        }

        // todo: install configs (peskyorm, peskycmf, peskycms)
        // todo: create AdminSiteLoader (extends CmsSiteLoader::class)

        $this->line('Done');

        $this->line('Remeber to perform next steps to activate cms:');
        $this->line('1. Add ' . PeskyCmsServiceProvider::class . ' to you app.providers config (if you do not use package discovery)');
        $this->line('2. Remove ' . PeskyCmfServiceProvider::class . ' from you app.providers config');
        $this->line('3. Configure created AdminSiteLoader class');
        $this->line('4. Run "php artisan migrate" to create tables in database');
        $this->line('5. Run "php artisan cmf::add-admin your-email@address.com" to create superadmin for CMS');
    }

    protected function getAppSettignsClassContents() {
        return <<<FILE
<?php

namespace App;

use PeskyCMS\CmsAppSettings;

class AppSettings extends CmsAppSettings {

}

FILE;
    }

    protected function addMigrationForTable($tableName, $index, $migrationsPath) {
        $filePath = $migrationsPath . "2014_10_12_{$index}00000_create_{$tableName}_table.php";
        if (File::exist($filePath)) {
            $this->line('- migration ' . $filePath . ' already exist. skipped.');
            return;
        }
        $groupName = StringUtils::classify($tableName);
        $className = 'Create' . $groupName . 'Table';
        $extendsClass = 'Cms' . $groupName . 'Migration';
        $fileContents = <<<FILE
<?php 

use PeskyCMS\\{$groupName}\\{$extendsClass};

class {$className} extends {$extendsClass} {

}

FILE;
        File::save($filePath, $fileContents, 0664, 0755);
        $this->line('Added migration ' . $migrationsPath);
    }
}