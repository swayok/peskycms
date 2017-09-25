<?php

use PeskyCMS\CmsAppSettings;
use PeskyCMS\CmsFrontendUtils;

if (!function_exists('insertPageData')) {

    /**
     * @param int $pageId - ID of the page
     * @param string $columnName - page's column name
     * @return mixed
     */
    function insertPageData($pageId, $columnName = 'content') {
        return CmsFrontendUtils::getPageData($pageId, $columnName);
    }
}

if (!function_exists('insertLinkToPage')) {

    /**
     * @param int $pageId - ID of the page
     * @param null|string $linkLabel - content of the <a> tag
     * @return string
     */
    function insertLinkToPage($pageId, $linkLabel = null) {
        return CmsFrontendUtils::makeHtmlLinkToPageForInsert($pageId, $linkLabel)->build();
    }
}

if (!function_exists('setting')) {

    /**
     * Get value for CmsSetting called $name (CmsSetting->key === $name)
     * @param string $name - setting name
     * @param mixed $default - default value
     * @return mixed|CmsAppSettings|\App\AppSettings
     */
    function setting($name = null, $default = null) {
        $class = app(CmsAppSettings::class);
        if ($name === null) {
            return $class::getInstance();
        } else {
            return $class::$name($default);
        }
    }
}