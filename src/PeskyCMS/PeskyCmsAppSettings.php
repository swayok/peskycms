<?php

namespace PeskyCMS;

use PeskyCMF\PeskyCmfAppSettings;

class PeskyCmsAppSettings extends PeskyCmfAppSettings {

    /**
     * URL prefix used to build final CMS page URL.
     * For example for CMS page with alias '/documentation' and URL prefix '/pages' full relative URL
     * to CMS page will be '/pages/documentation'
     * @return null|string
     */
    static public function cms_pages_url_prefix(): ?string {
        return '';
    }

    /**
     * Use relative urls in CMS page creation/editing forms
     * @return bool
     */
    static public function cms_pages_use_relative_url(): ?bool {
        return false;
    }
}