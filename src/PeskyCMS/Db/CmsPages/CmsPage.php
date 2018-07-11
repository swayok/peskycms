<?php

namespace PeskyCMS\Db\CmsPages;

use PeskyCMF\Db\Admins\CmfAdmin;
use PeskyCMF\Db\CmfDbRecord;
use PeskyCMS\Db\CmsTexts\CmsTextWrapper;

/**
 * @property-read int         $id
 * @property-read null|int    $parent_id
 * @property-read null|int    $admin_id
 * @property-read string      $type
 * @property-read string      $title
 * @property-read string      $comment
 * @property-read string      $texts
 * @property-read string      $texts_ar_array
 * @property-read \stdClass   $texts_ar_object
 * @property-read string      $url_alias
 * @property-read string      $relative_url
 * @property-read null|string $page_code
 * @property-read string      $images
 * @property-read array       $images_as_array
 * @property-read \stdClass   $images_as_object
 * @property-read array       $images_as_urls
 * @property-read array       $images_as_urls_with_timestamps
 * @property-read array       $images_as_paths
 * @property-read array       $images_as_file_info_arrays
 * @property-read string      $meta_description
 * @property-read string      $meta_keywords
 * @property-read null|int    $position
 * @property-read string      $with_contact_form
 * @property-read string      $is_published
 * @property-read string      $publish_at
 * @property-read string      $publish_at_as_date
 * @property-read string      $publish_at_as_time
 * @property-read int         $publish_at_as_unix_ts
 * @property-read string      $created_at
 * @property-read string      $created_at_as_date
 * @property-read string      $created_at_as_time
 * @property-read int         $created_at_as_unix_ts
 * @property-read string      $updated_at
 * @property-read string      $updated_at_as_date
 * @property-read string      $updated_at_as_time
 * @property-read int         $updated_at_as_unix_ts
 * @property-read string      $custom_info
 * @property-read string      $custom_info_as_array
 * @property-read \stdClass   $custom_info_as_object
 * @property-read CmsPage     $Parent
 * @property-read CmfAdmin    $Admin
 *
 * @method $this    setId($value, $isFromDb = false)
 * @method $this    setParentId($value, $isFromDb = false)
 * @method $this    setAdminId($value, $isFromDb = false)
 * @method $this    setType($value, $isFromDb = false)
 * @method $this    setTitle($value, $isFromDb = false)
 * @method $this    setComment($value, $isFromDb = false)
 * @method $this    setTexts($value, $isFromDb = false)
 * @method $this    setUrlAlias($value, $isFromDb = false)
 * @method $this    setPageCode($value, $isFromDb = false)
 * @method $this    setImages($value, $isFromDb = false)
 * @method $this    setMetaDescription($value, $isFromDb = false)
 * @method $this    setMetaKeywords($value, $isFromDb = false)
 * @method $this    setOrder($value, $isFromDb = false)
 * @method $this    setWithContactForm($value, $isFromDb = false)
 * @method $this    setIsPublished($value, $isFromDb = false)
 * @method $this    setPublishAt($value, $isFromDb = false)
 * @method $this    setCustomInfo($value, $isFromDb = false)
 */
class CmsPage extends CmfDbRecord {

    const TYPE_PAGE = 'page';
    const TYPE_CATEGORY = 'category';
    const TYPE_ITEM = 'item';
    const TYPE_NEWS = 'news';
    const TYPE_TEXT_ELEMENT = 'text_element';
    const TYPE_MENU = 'menu';

    static protected $types = [
        self::TYPE_PAGE,
        self::TYPE_NEWS,
        self::TYPE_CATEGORY,
        self::TYPE_ITEM,
        self::TYPE_MENU,
        self::TYPE_TEXT_ELEMENT,
    ];

    /**
     * Page types that cannot be inserted as link
     * @var array
     */
    static protected $typesWithoutUrls = [
        self::TYPE_TEXT_ELEMENT,
        self::TYPE_MENU
    ];
    /** @var CmsTextWrapper */
    protected $textsWrapper;

    public static function getTable(): CmsPagesTable {
        return CmsPagesTable::getInstance();
    }

    static public function getTypes($asOptions = false) {
        return static::toOptions(static::$types, $asOptions, function ($value) {
            return cmfTransCustom('.cms_pages.type.' . $value);
        }, true);
    }

    static public function getTypesWithoutUrls($asOptions = false) {
        return static::toOptions(static::$typesWithoutUrls, $asOptions, function ($value) {
            return cmfTransCustom('.cms_pages.type.' . $value);
        }, true);
    }

    /**
     * @param bool $ignoreCache - remake CmsTextWrapper
     * @return CmsTextWrapper
     * @throws \InvalidArgumentException
     */
    public function getLocalizedText($ignoreCache = false) {
        if ($ignoreCache || $this->textsWrapper === null) {
            $locale = app()->getLocale();
            $localeRedirects = setting()->fallback_languages();
            if (is_array($localeRedirects) && !empty($localeRedirects[$locale])) {
                // replace current locale by supported one. For example - replace 'ua' locale by 'ru'
                $locale = $localeRedirects[$locale];
            }
            $this->textsWrapper = new CmsTextWrapper($this, $locale, setting()->default_language());
        }
        return $this->textsWrapper;
    }

    /**
     * Check if page is allowed to be displayed to user
     * @return bool
     */
    public function isValid() {
        return $this->existsInDb() && $this->is_published;
    }

    public function reset() {
        $this->textsWrapper = null;
        return parent::reset();
    }

}
