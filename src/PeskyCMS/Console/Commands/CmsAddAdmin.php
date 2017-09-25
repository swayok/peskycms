<?php

namespace PeskyCMS\Console\Commands;

use Illuminate\Console\Command;
use PeskyCMF\Config\CmfConfig;
use PeskyCMS\Db\Admins\CmsAdmin;
use PeskyORM\ORM\RecordInterface;

class CmsAddAdmin extends Command {

    protected $description = 'Create administrator in DB using Record class provided by CmfConfig::getDefault()->user_object_class()';
    protected $signature = 'cms:add-admin {email_or_login} {role=admin}';

    public function fire() {
        $args = $this->input->getArguments();
        $emailOrLogin = strtolower(trim($args['email_or_login']));
        // create Record (empty)
        /** @var RecordInterface $adminObjectClass */
        $adminObjectClass = CmfConfig::getDefault()->user_object_class();
        $authColumn = CmfConfig::getDefault()->user_login_column();
        /** @var CmsAdmin $admin */
        $admin = $adminObjectClass::newEmptyRecord();
        // request password entry
        $password = $this->secret('Enter password for admin');
        if (empty($password)) {
            $this->line('Cannot continue: password is empty');
            exit;
        }
        try {
            // search for existing record
            $admin->updateValue($authColumn, $emailOrLogin, false);
            $admin->fromDb([
                $authColumn => $admin->getValue($authColumn)
            ]);
            $alreadyExists = $admin->existsInDb();
            // create/update admin
            if ($alreadyExists) {
                $admin->begin();
            }
            $admin
                ->setRole($args['role'])
                ->setPassword($password)
                ->setIsSuperadmin(true)
                ->setLanguage(CmfConfig::getDefault()->default_locale());
            if ($alreadyExists) {
                $admin->commit();
            } else {
                $admin
                    ->updateValue($authColumn, $emailOrLogin, false)
                    ->save();
            }
            $this->line($alreadyExists ? 'Admin updated' : 'Admin created');
        } catch (\Exception $exc) {
            $this->line('Fail. Exception:');
            $this->line($exc->getMessage());
            $this->line($exc->getTraceAsString());
            exit;
        }
    }
}