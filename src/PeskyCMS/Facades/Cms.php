<?php

namespace PeskyCMS\Facades;

use Illuminate\Support\Facades\Facade;
use PeskyCMS\CmsFrontendUtils;

class Cms extends Facade {

    protected static function getFacadeAccessor() {
        return CmsFrontendUtils::class;
    }
}