<?php

use PeskyCMS\CmsFrontendUtils;

if (!function_exists('insertCmsPageData')) {

    /**
     * @param int|string $pageIdOrPageCode - ID of the page or page_code
     * @param string $key - what piece of page's data to return
     * @return mixed
     */
    function insertCmsPageData($pageIdOrPageCode, string $key = 'content') {
        return CmsFrontendUtils::getPageData($pageIdOrPageCode, $key);
    }
}

if (!function_exists('insertLinkToCmsPage')) {

    /**
     * This method may return empty string when there is no page with specified $pageIdOrPageCode
     * @param int|string $pageIdOrPageCode - ID of the page or page_code
     * @param null|string $linkLabel - content of the <a> tag
     * @param bool $asTagObject - true: return \Swayok\Html\Tag; false: return string
     * @return string|\Swayok\Html\Tag|\Swayok\Html\EmptyTag
     */
    function insertLinkToCmsPage($pageIdOrPageCode, ?string $linkLabel = null, bool $asTagObject = false) {
        $tag = CmsFrontendUtils::makeHtmlLinkToPageForInsert($pageIdOrPageCode, $linkLabel);
        if ($asTagObject) {
            return $tag;
        } else {
            return $tag->build();
        }
    }
}

if (!function_exists('urlToCmsPage')) {

    /**
     * This method may return null when there is no page with specified $pageIdOrPageCode
     * @param int|string $pageIdOrPageCode - ID of the page or page_code
     * @param bool $absolute
     * @return null|string
     */
    function urlToCmsPage($pageIdOrPageCode, bool $absolute = false): ?string {
        return CmsFrontendUtils::getUrlToPage($pageIdOrPageCode, $absolute);
    }
}