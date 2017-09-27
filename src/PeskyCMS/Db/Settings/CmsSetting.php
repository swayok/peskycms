<?php

namespace PeskyCMS\Db\Settings;

use PeskyCMF\Db\Admins\CmfAdmin;
use PeskyCMS\Db\CmsDbRecord;
use PeskyORMLaravel\Db\KeyValueTableUtils\KeyValueRecordHelpers;

/**
 * @property-read int         $id
 * @property-read string      $key
 * @property-read string      $value
 * @property-read CmfAdmin    $Admin
 *
 * @method $this    setId($value, $isFromDb = false)
 * @method $this    setKey($value, $isFromDb = false)
 * @method $this    setValue($value, $isFromDb = false)
 *
 * @method static CmsSettingsTable getTable()
 */
class CmsSetting extends CmsDbRecord {

    use KeyValueRecordHelpers;

    static protected $tableClass = CmsSettingsTable::class;

}