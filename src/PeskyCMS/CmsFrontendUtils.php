<?php

namespace PeskyCMS;

use Illuminate\Routing\Route;
use PeskyCMF\HttpCode;
use PeskyCMF\PeskyCmfAppSettings;
use PeskyCMS\Db\CmsPages\CmsPage;
use PeskyCMS\Db\CmsPages\CmsPagesTable;
use PeskyCMS\Db\CmsPages\CmsPageWrapper;
use PeskyCMS\Db\CmsRedirects\CmsRedirect;
use PeskyORM\ORM\RecordInterface;
use Swayok\Html\EmptyTag;
use Swayok\Html\Tag;
use Symfony\Component\HttpFoundation\Response;

class CmsFrontendUtils {
    /** @var CmsPageWrapper[] */
    static protected $loadedPages = [];

    static public function registerBladeDirectiveForStringTemplateRendering() {
        \Blade::directive('wysiwygInsert', function ($param) {
            return '<?php echo ' . html_entity_decode($param, ENT_QUOTES | ENT_HTML401) . '; ?>';
        });
    }

    /**
     * Declare route that will handle HTTP GET requests to CmsPagesTable
     * @param string|\Closure $routeAction - Closure, 'Controller@action' string, array.
     *      It is used as 2nd argument for \Route:get('url', $routeAction).
     *      Example: 'PagesController@renderCmsPage'. Where renderCmsPage should look like:
     *      public function renderCmsPage($url) {
     *          return CmsFrontendUtils::renderPage($url, 'frontend.cms_page', ['vide_var' => 'value']);
     *      }
     * @param array $excludeUrlPrefixes - list of url prefixes used in application
     * @param string $extension - url extension (for example: '.html')
     * For example: 'admin' is default url prefix for administration area. It should be excluded in order to allow
     * access to administration area. Otherwise this route will intercept it.
     * @return Route
     */
    static public function addRouteForPages($routeAction, array $excludeUrlPrefixes = [], string $extension = ''): Route {
        /** @var PeskyCmsAppSettings $appSettings */
        $appSettings = app(PeskyCmfAppSettings::class);
        $prefix = '/' . trim($appSettings::cms_pages_url_prefix(), '/');
        $route = \Route::get($prefix . '{page_url}' . $extension, $routeAction);
        if (count($excludeUrlPrefixes) > 0) {
            $route->where('page_url', '/?(?!' . implode('|', $excludeUrlPrefixes) . ').*');
        }
        return $route;
    }

    /**
     * Render $view with $viewData for page with $url.
     * If $url is detected in CmsRedirectsTable - client will be redirected to page provided by CmsRedirect->page_id;
     * If page for $url was not found - 404 page will be shown;
     * 'texts' variable (CmsPageTexts) will be additionally passed to a $view;
     * Data passed to $view:
     *      @section('meta-description')
     *      @section('meta-keywords')
     *      @section('browser-title')
     *      $page CmsPageWrapper
     * @param string $url - page's relative url
     * @param string|\Closure $view
     *      - string: path to view that will render the page;
     *      - \Closure - function (CmsPageWrapper $page) { return 'path.to.view'; }
     * @param \Closure $viewData - function (CmsPageWrapper $page) { return [] }. Returns array with data
     * @param \Closure $pageNotFoundCallback - function () { abort(404); }. Returns a valid response or aborts request
     * to send to view in addition to 'texts' variable (CmsPageTexts).
     * @return string|Response - rendered $view
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     */
    static public function renderPage($url, $view, \Closure $viewData = null, \Closure $pageNotFoundCallback = null) {
        $url = strtolower(rtrim($url, '/'));
        // search for url in redirects
        /** @var CmsRedirect $redirectClass */
        $redirect = CmsRedirect::find([
            'from_url ~*' => '^' . preg_quote($url, null) . '/*$'
        ]);
        if ($redirect->existsInDb()) {
            return redirect(
                rtrim($redirect->Page->relative_url, '/'),
                $redirect->is_permanent ? HttpCode::MOVED_PERMANENTLY : HttpCode::MOVED_TEMPORARILY
            );
        }
        $wrappedPage = static::getWrappedPageByUrl($url);
        if (!$wrappedPage->isValid()) {
            if ($pageNotFoundCallback) {
                return $pageNotFoundCallback();
            } else {
                abort(HttpCode::NOT_FOUND);
            }
        }
        $data = [];
        if (!empty($viewData)) {
            $data = $viewData($wrappedPage);
            if (!is_array($data)) {
                throw new \UnexpectedValueException('$viewData closure must return an array');
            }
        }
        $wrappedPage->sendMetaTagsAndPageTitleSectionToLayout();
        if ($view instanceof \Closure) {
            $view = $view($wrappedPage);
        }
        if (!is_string($view) || empty($view)) {
            throw new \InvalidArgumentException(
                '$view argument must be a not empty string or closure that returns not empty string'
            );
        }
        return view($view, array_merge($data, ['page' => $wrappedPage]))->render();
    }

    /**
     * Get value of $columnName from page identified by $pageIdOrPageCode
     * @param int $pageIdOrPageCode
     * @param string $columnName
     * @return string
     */
    static public function getPageData($pageIdOrPageCode, $columnName = 'content'): string {
        $page = static::getPageWrapper($pageIdOrPageCode);
        if (!$page->isValid()) {
            return '';
        }
        return $page->$columnName;
    }

    /**
     * @param int|string $pageIdOrPageCode
     * @param null|string $linkText
     * @param bool $openInNewTab
     * @return Tag
     */
    static public function makeHtmlLinkToPageForInsert($pageIdOrPageCode, $linkText = null, $openInNewTab = false): Tag {
        $page = static::getPageWrapper($pageIdOrPageCode);
        if (!$page->isValid()) {
            return EmptyTag::create();
        }
        if (trim((string)$linkText) === '') {
            $linkText = $page->menu_title;
            /** @noinspection NotOptimalIfConditionsInspection */
            if (trim((string)$linkText) === '') {
                return EmptyTag::create();
            }
        }
        return Tag::a()
            ->setContent(trim(stripslashes($linkText)))
            ->setHref(rtrim($page->relative_url, '/'))
            ->setAttribute('target', $openInNewTab ? '_blank' : null);
    }

    /**
     * @param int|string $pageIdOrPageCode
     * @param bool $absolute
     * @return null|string
     */
    static public function getUrlToPage($pageIdOrPageCode, bool $absolute = false): ?string {
        $page = static::getPageWrapper($pageIdOrPageCode);
        if (!$page->isValid()) {
            return null;
        }
        if ($absolute) {
            return \URL::to($page->relative_url);
        } else {
            return $page->relative_url;
        }
    }

    /**
     * @param string $textWithInserts
     * @param string $cacheKey
     * @return string
     */
    static public function processDataInsertsForText($textWithInserts, $cacheKey): string {
        static $compiled;
        if ($compiled === null) {
            $compiled = [];
        }
        $compiledViewsPath = config('view.compiled', function () {
            return realpath(storage_path('framework/views'));
        });
        $filePath = $compiledViewsPath . DIRECTORY_SEPARATOR . strtolower(preg_replace('%[^a-zA-Z0-9]+%', '_', $cacheKey)) . '.php';
        if (
            !in_array($filePath, $compiled, true)
            && (
                config('app.debug')
                || !\File::exists($filePath)
                || filemtime($filePath) + 3600 < time()
            )
        ) {
            \File::put($filePath, \Blade::compileString(preg_replace(
                ['%<span[^>]+class="wysiwyg-data-insert"[^>]*>(.*?)</span>%is', '%<div[^>]+class="wysiwyg-data-insert"[^>]*>(.*?)</div>%is'],
                ['$1', '<div>$1</div>'],
                $textWithInserts
            )));
            $compiled[] = $filePath;
        }
        \View::startSection($filePath);
        include $filePath;
        return \View::yieldSection();
    }

    /**
     * Get rendered text block contents
     * @param string $pageCode
     * @return string
     */
    static public function getTextBlock($pageCode): string {
        return static::getPageData($pageCode, 'content');
    }

    /**
     * Get rendered menu contents
     * @param string $pageCode
     * @param null|string $language - 2-letter code of language
     * @return string
     */
    static public function getMenu($pageCode): string {
        return static::getPageData($pageCode, 'content');
    }

    /**
     * @param string $pageCode
     * @return string
     */
    static public function getMenuHeader($pageCode): string {
        return static::getPageData($pageCode, 'menu_title');
    }

    /**
     * Extract links from menu's content
     * @param string $pageCode
     * @param bool $parseLinks
     *  - false: return list of strings like '<a href="/link/url">link content</a>'
     *  - true: return list of arrays in format: ['url' => '/link/url', 'text' => 'link content']
     * @param int $maxNestingLevel - max level of menu items nesting. 0 = no nesting.
     * @return array
     */
    static public function getLinksForMenu($pageCode, bool $parseLinks = false, int $maxNestingLevel = 0): array {
        $links = [];
        if (preg_match_all('%<a[^>]+href=([\'"])(.*?)\1[^>]+>(.*?)</a>%is', static::getMenu($pageCode), $matches)) {
            if ($parseLinks) {
                foreach ($matches[2] as $index => $url) {
                    $links[] = [
                        'url' => $url,
                        'text' => $matches[3][$index]
                    ];
                }
            } else {
                $links = $matches[0];
            }
        }
        return $links;
    }

    /**
     * @param int $pageIdOrPageCode
     * @return CmsPageWrapper
     */
    static public function getPageWrapper($pageIdOrPageCode): CmsPageWrapper {
        return static::getPageFromCache($pageIdOrPageCode, function ($pageIdOrPageCode) {
            return CmsPage::find(
                [
                    'OR' => [
                        CmsPage::getPrimaryKeyColumnName() => (int)$pageIdOrPageCode,
                        'page_code' => $pageIdOrPageCode
                    ],
                ],
                [],
                ['Parent']
            );
        });
    }

    /**
     * @param string $url
     * @return CmsPageWrapper
     */
    static protected function getWrappedPageByUrl($url): CmsPageWrapper {
        return static::getPageFromCache($url, function ($url) {
            $lastUrlSection = preg_quote(array_last(explode('/', trim($url, '/'))), null);
            $possiblePages = CmsPagesTable::select(['*', 'Parent' => ['*']], [
                'url_alias ~*' => (empty($url) ? '^' : $lastUrlSection) . '/*$',
                'ORDER' => ['parent_id' => 'DESC']
            ]);
            /** @var CmsPage $possiblePage */
            foreach ($possiblePages as $possiblePage) {
                static::savePageToCache($possiblePage);
            }
            return static::getPageFromCache($url, function () {
                return CmsPagesTable::getInstance()->newRecord();
            });
        });
    }

    /**
     * @param string $cacheKey
     * @param \Closure $default
     * @return CmsPageWrapper
     */
    static protected function getPageFromCache($cacheKey, \Closure $default): CmsPageWrapper {
        $cacheKey = static::normalizePageUrl($cacheKey);
        if (!static::hasPageInCache($cacheKey)) {
            static::savePageToCache($default($cacheKey), $cacheKey);
        }
        return static::$loadedPages[$cacheKey];
    }

    /**
     * @param RecordInterface|CmsPage $page
     * @param string|null $cacheKeyForNotExistingPage - cache key to store not existing CmsPage
     */
    static protected function savePageToCache($page, $cacheKeyForNotExistingPage = null) {
        if ($page instanceof CmsPageWrapper) {
            return;
        }
        $wrapper = new CmsPageWrapper($page);
        if ($page->existsInDb()) {
            static::$loadedPages[$page->getPrimaryKeyValue()] = $wrapper;
            if (!empty($page->page_code)) {
                static::$loadedPages[$page->page_code] = $wrapper;
            }
            if (!empty($page->url_alias)) {
                static::$loadedPages[static::normalizePageUrl($page->relative_url)] = $wrapper;
                static::$loadedPages[static::normalizePageUrl($page->full_path)] = $wrapper;
            }
        } else if (!empty($cacheKeyForNotExistingPage)) {
            static::$loadedPages[static::normalizePageUrl($cacheKeyForNotExistingPage)] = $wrapper;
        }
    }

    /**
     * @param string $cacheKey
     * @return bool
     */
    static protected function hasPageInCache($cacheKey): bool {
        return array_key_exists($cacheKey, static::$loadedPages);
    }

    /**
     * @param $url
     * @return string
     */
    static protected function normalizePageUrl($url): string {
        return strtolower(rtrim((string)$url, '/'));
    }

    /**
     * @param CmsPage|CmsPageWrapper $page
     * @param $language
     * @return string
     */
    static public function makeCacheKeyForPageContentView($page, $language): string {
        return 'page-' . $page->id . '-lang-' . $language . '-updated-at-' . $page->updated_at_as_unix_ts;
    }

}