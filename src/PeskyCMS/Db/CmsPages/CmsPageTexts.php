<?php

namespace PeskyCMS\Db\CmsPages;

use PeskyCMS\CmsFrontendUtils;
use PeskyORM\ORM\RecordInterface;

class CmsPageTexts {

    /** @var CmsPage */
    protected $page;
    /** @var string */
    protected $mainLanguage;
    /** @var string */
    protected $fallbackLanguage;
    /** @var array */
    protected $mainTexts;
    /** @var array */
    protected $fallbackTexts;
     /** @var string */
    protected $isContentProcessed;

    /** @var string */
    protected $browser_title;
    /** @var string */
    protected $title;
    /** @var string */
    protected $menu_title;
    /** @var string */
    protected $content;
    /** @var string */
    protected $meta_description;
    /** @var string */
    protected $meta_keywords;

    /**
     * @param RecordInterface|CmsPage $page
     * @param string $mainLanguage
     * @param string $fallbackLanguage - used when some field of $mainLanguage is empty
     * @throws \InvalidArgumentException
     */
    public function __construct(RecordInterface $page, $mainLanguage, $fallbackLanguage = null) {
        if (!$page->existsInDb()) {
            throw new \InvalidArgumentException('$page argument must contain a DB record that exists in DB');
        }
        if (empty($mainLanguage) || !is_string($mainLanguage) || mb_strlen($mainLanguage) !== 2) {
            throw new \InvalidArgumentException('$mainLanguage argument must contain a 2 letters string (for example: "en")');
        }
        if (!empty($fallbackLanguage) && (mb_strlen($fallbackLanguage) !== 2 || !is_string($fallbackLanguage))) {
            throw new \InvalidArgumentException('$fallbackLanguage argument must contain a string with 2 letters (for example: "en") or null');
        }
        $this->page = $page;
        $this->mainLanguage = strtolower($mainLanguage);
        if (!empty($fallbackLanguage) && strtolower($fallbackLanguage) !== $this->mainLanguage) {
            $this->fallbackLanguage = strtolower($fallbackLanguage);
        }
    }

    /**
     * @return string
     */
    public function getMainLanguage(): string {
        return $this->mainLanguage;
    }

    /**
     * @return string
     */
    public function getFallbackLanguage(): string {
        return $this->fallbackLanguage;
    }

    /**
     * @return CmsPage
     */
    public function getPage(): CmsPage {
        return $this->page;
    }

    /**
     * @return array
     */
    protected function getMainTexts(): array {
        if ($this->mainTexts === null) {
            $this->mainTexts = [];
            $texts = array_get($this->getPage()->texts_as_array, $this->mainLanguage, []);
            foreach ($texts as $key => $value) {
                if (empty($value) || (is_string($value) && trim($value) === '')) {
                    continue;
                }
                $this->mainTexts[$key] = $value;
            }
        }
        return $this->mainTexts;
    }

    /**
     * @return array
     */
    protected function getFallbackTexts(): array {
        if ($this->fallbackTexts === null) {
            $this->fallbackTexts = [];
            if (!empty($this->fallbackLanguage)) {
                $this->fallbackTexts = [];
                $texts = array_get($this->getPage()->texts_as_array, $this->mainLanguage, []);
                foreach ($texts as $key => $value) {
                    if (empty($value) || (is_string($value) && trim($value) === '')) {
                        continue;
                    }
                    $this->fallbackTexts[$key] = $value;
                }
            }
        }
        return $this->fallbackTexts;
    }

    /**
     * @param string $columnName
     * @param string|null $fallbackColumnName - column name to use if $columnName is empty
     * @return mixed
     */
    protected function getDataFromTexts($columnName, $fallbackColumnName = null) {
        $mainTexts = $this->getMainTexts();
        return array_get($mainTexts, $columnName, function () use ($columnName, $mainTexts, $fallbackColumnName) {
            $useFallbackTexts = function () use ($columnName, $fallbackColumnName) {
                $fallbackTexts = $this->getFallbackTexts();
                if (empty($fallbackTexts)) {
                    return '';
                }
                return array_get($fallbackTexts, $columnName, function () use ($fallbackColumnName, $fallbackTexts) {
                    return array_get($fallbackTexts, $fallbackColumnName, '');
                });
            };
            if ($fallbackColumnName) {
                return array_get($mainTexts, $fallbackColumnName, $useFallbackTexts);
            }
            return $useFallbackTexts();
        });
    }

    /**
     * @return string
     */
    public function browser_title(): string {
        if ($this->browser_title === null) {
            $this->browser_title = trim((string)$this->getDataFromTexts('browser_title', 'title'));
        }
        return $this->browser_title;
    }

    /**
     * @return string
     */
    public function title(): string {
        if ($this->title === null) {
            $this->title = trim((string)$this->getDataFromTexts('title'));
        }
        return $this->title;
    }

    /**
     * @return string
     */
    public function menu_title(): string {
        if ($this->menu_title === null) {
            $this->menu_title = trim((string)$this->getDataFromTexts('menu_title', 'title'));
        }
        return $this->menu_title;
    }

    /**
     * @param bool $processInserts - true: replace all data inserts by real data
     * @return string
     */
    public function content($processInserts = true): string {
        if ($this->content === null) {
            $this->content = $this->getDataFromTexts('content');
        }

        if ($processInserts && $this->isContentProcessed === null) {
            $this->isContentProcessed = CmsFrontendUtils::processDataInsertsForText(
                $this->content,
                CmsFrontendUtils::makeCacheKeyForPageContentView($this->getPage(), $this->mainLanguage)
            );
        }
        return $processInserts ? $this->isContentProcessed : $this->content;
    }

    /**
     * @return string
     */
    public function meta_description(): string {
        if ($this->meta_description === null) {
            $this->meta_description = trim((string)$this->getDataFromTexts('meta_description'));
        }
        return $this->meta_description;
    }

    /**
     * @return string
     */
    public function meta_keywords(): string {
        if ($this->meta_keywords === null) {
            $this->meta_keywords = trim((string)$this->getDataFromTexts('meta_keywords'));
        }
        return $this->meta_keywords;
    }

}