<?php

namespace PeskyCMS\Db\Texts;

use PeskyCMS\CmsFrontendUtils;
use PeskyCMS\Db\Pages\CmsPage;
use PeskyORM\ORM\RecordInterface;

class CmsTextWrapper {

    /** @var CmsPage */
    protected $page;
    /** @var string */
    protected $mainLanguage;
    /** @var string */
    protected $fallbackLanguage;
    /** @var CmsText */
    protected $mainTextRecord;
    /** @var CmsText */
    protected $fallbackTextRecord;

    /** @var string */
    protected $browser_title;
    /** @var string */
    protected $title;
    /** @var string */
    protected $menu_title;
    /** @var string */
    protected $content;
    /** @var string */
    protected $contentProcessed;
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
    public function getMainLanguage() {
        return $this->mainLanguage;
    }

    /**
     * @return string
     */
    public function getFallbackLanguage() {
        return $this->fallbackLanguage;
    }

    /**
     * @return CmsText
     * @throws \UnexpectedValueException
     * @throws \PeskyORM\Exception\OrmException
     * @throws \PeskyORM\Exception\InvalidDataException
     * @throws \PDOException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    protected function getMainTextRecord() {
        if ($this->mainTextRecord === null) {
            /** @var CmsText $textRecordClass */
            $textRecordClass = app(CmsText::class);
            $this->mainTextRecord = $textRecordClass::find([
                'page_id' => $this->getPage()->id,
                'language' => $this->mainLanguage
            ]);
        }
        return $this->mainTextRecord;
    }

    /**
     * @return CmsPage
     */
    public function getPage() {
        return $this->page;
    }

    /**
     * @return CmsText
     * @throws \UnexpectedValueException
     * @throws \PeskyORM\Exception\OrmException
     * @throws \PeskyORM\Exception\InvalidDataException
     * @throws \PDOException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    protected function getFallbackTextRecord() {
        if ($this->fallbackTextRecord === null) {
            /** @var CmsText $textRecordClass */
            $textRecordClass = app(CmsText::class);
            if (empty($this->fallbackLanguage)) {
                $this->fallbackTextRecord = $textRecordClass::newEmptyRecord();
            } else {
                $this->fallbackTextRecord = $textRecordClass::find([
                    'page_id' => $this->getPage()->id,
                    'language' => $this->fallbackLanguage
                ]);
            }
        }
        return $this->fallbackTextRecord;
    }

    /**
     * @param string $columnName
     * @param string|null $fallbackColumnName - column name to use if $columnName is empty
     * @return mixed
     * @throws \PeskyORM\Exception\InvalidDataException
     * @throws \UnexpectedValueException
     * @throws \PeskyORM\Exception\OrmException
     * @throws \PDOException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    protected function getDataFromTextRecords($columnName, $fallbackColumnName = null) {
        if ($this->getMainTextRecord()->existsInDb()) {
            if (!empty($this->getMainTextRecord()->$columnName)) {
                $value = $this->getMainTextRecord()->$columnName;
                if (!is_string($value) || trim((string)$value) !== '') {
                    return $value;
                }
            } else if (!empty($fallbackColumnName) && !empty($this->getMainTextRecord()->$fallbackColumnName)) {
                $value = $this->getMainTextRecord()->$fallbackColumnName;
                /** @noinspection NotOptimalIfConditionsInspection */
                if (!is_string($value) || trim((string)$value) !== '') {
                    return $value;
                }
            }
        }
        if ($this->getFallbackTextRecord()->existsInDb()) {
            if (!empty($this->getFallbackTextRecord()->$columnName)) {
                $value = $this->getFallbackTextRecord()->$columnName;
                if (!is_string($value) || trim((string)$value) !== '') {
                    return $value;
                }
            } else if (!empty($fallbackColumnName) && !empty($this->getFallbackTextRecord()->$fallbackColumnName)) {
                $value = $this->getFallbackTextRecord()->$fallbackColumnName;
                /** @noinspection NotOptimalIfConditionsInspection */
                if (!is_string($value) || trim((string)$value) !== '') {
                    return $value;
                }
            }
        }
        return '';
    }

    /**
     * @return string
     */
    public function browser_title() {
        if ($this->browser_title === null) {
            $this->browser_title = trim((string)$this->getDataFromTextRecords('browser_title', 'title'));
        }
        return $this->browser_title;
    }

    /**
     * @return string
     */
    public function title() {
        if ($this->title === null) {
            $this->title = trim((string)$this->getDataFromTextRecords('title'));
        }
        return $this->title;
    }

    /**
     * @return string
     */
    public function menu_title() {
        if ($this->menu_title === null) {
            $this->menu_title = trim((string)$this->getDataFromTextRecords('menu_title', 'title'));
        }
        return $this->menu_title;
    }

    /**
     * @param bool $processInserts - true: replace all data inserts by real data
     * @return string
     */
    public function content($processInserts = true) {
        if ($this->content === null) {
            $this->content = $this->getDataFromTextRecords('content');
        }

        if ($processInserts && $this->contentProcessed === null) {
            $this->contentProcessed = CmsFrontendUtils::processDataInsertsForText(
                $this->content,
                CmsFrontendUtils::makeCacheKeyForPageContentView($this->getPage(), $this->mainLanguage)
            );
        }
        return $processInserts ? $this->contentProcessed : $this->content;
    }

    /**
     * @return string
     */
    public function meta_description() {
        if ($this->meta_description === null) {
            $this->meta_description = trim((string)$this->getDataFromTextRecords('meta_description'));
        }
        return $this->meta_description;
    }

    /**
     * @return string
     */
    public function meta_keywords() {
        if ($this->meta_keywords === null) {
            $this->meta_keywords = trim((string)$this->getDataFromTextRecords('meta_keywords'));
        }
        return $this->meta_keywords;
    }

}