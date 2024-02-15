<?php
/**
 * Created by PhpStorm.
 * User: mmikkel
 * Date: 14/07/2018
 * Time: 11:55
 */

namespace mmikkel\cacheflag\services;

use Craft;
use craft\base\Component;
use craft\helpers\StringHelper;
use yii\caching\TagDependency;

use DateTime;

/**
 * Class TemplateCachesService
 * @author    Mats Mikkel Rummelhoff
 * @package mmikkel\cacheflag\services
 * @since 1.0.0
 */
class TemplateCachesService extends Component
{

    /** @var bool */
    private $_collectElementTags = false;

    /**
     * @var string|null The current request's path
     * @see _path()
     */
    private $_path;

    // Public Methods
    // =========================================================================
    /**
     * Returns a cached template by its key.
     *
     * @param string $key The template cache key
     * @param string|string[]|null $flags The Cache Flag flags this cache would've been flagged with
     * @param bool $collectElementTags Whether to cache element queries or not
     * @param bool $global Whether the cache would have been stored globally.
     * @return string|null
     */
    public function getTemplateCache(string $key, $flags, bool $collectElementTags, bool $global)
    {
        // Make sure template caching is enabled
        if ($this->_isTemplateCachingEnabled() === false) {
            return null;
        }

        $this->_collectElementTags = $collectElementTags;

        $cacheKey = $this->_cacheKey($key, $global);
        $data = Craft::$app->getCache()->get($cacheKey);

        if ($data === false) {
            return null;
        }

        list($body, $tags) = $data;

        // Make sure the cache was tagged w/ the same flags
        $flagTags = $this->_getTagsForFlags($flags);
        $cachedFlagTags = \array_filter($tags, function (string $tag) {
            return \strpos($tag, 'cacheflag') === 0 || $tag === 'element' || $tag === 'template';
        });

        if (\array_diff($flagTags, $cachedFlagTags) != \array_diff($cachedFlagTags, $flagTags)) {
            return null;
        }

        // If we're actively collecting element cache tags, add this cache's tags to the collection
        Craft::$app->getElements()->collectCacheTags($tags);
        return $body;
    }

    /**
     *
     */
    public function startTemplateCache()
    {
        // Make sure template caching is enabled
        if ($this->_isTemplateCachingEnabled() === false) {
            return;
        }

        if ($this->_collectElementTags) {
            Craft::$app->getElements()->startCollectingCacheTags();
        }
    }

    /**
     * Ends a template cache.
     *
     * @param string $key The template cache key.
     * @param string|null $flags The flags this cache should be flagged with.
     * @param bool $global Whether the cache should be stored globally.
     * @param string|null $duration How long the cache should be stored for. Should be a [relative time format](http://php.net/manual/en/datetime.formats.relative.php).
     * @param mixed|null $expiration When the cache should expire.
     * @param string $body The contents of the cache.
     * @throws \Throwable
     */
    public function endTemplateCache(string $key, $flags, bool $global, string $duration = null, /** @scrutinizer ignore-unused */ $expiration, string $body)
    {

        // Make sure template caching is enabled
        if ($this->_isTemplateCachingEnabled() === false) {
            return;
        }

        // If there are any transform generation URLs in the body, don't cache it.
        // stripslashes($body) in case the URL has been JS-encoded or something.
        if (StringHelper::contains(stripslashes($body), 'assets/generate-transform')) {
            return;
        }

        // Get flag tags
        $flagTags = $this->_getTagsForFlags($flags);

        if ($this->_collectElementTags) {
            // If we're collecting element tags, collect the flag tags too, and end the collection
            Craft::$app->getElements()->collectCacheTags($flagTags);
            $dep = Craft::$app->getElements()->stopCollectingCacheTags();
        } else {
            // If not, just tag it with the flags
            $dep = new TagDependency([
                'tags' => $flagTags,
            ]);
        }

        $cacheKey = $this->_cacheKey($key, $global);

        if ($duration !== null) {
            $duration = (new DateTime($duration))->getTimestamp() - time();
        }

        Craft::$app->getCache()->set($cacheKey, [$body, $dep->tags], $duration, $dep);
    }

    // Private Methods
    // =========================================================================
    /**
     * Returns whether template caching is enabled, based on the 'enableTemplateCaching' config setting.
     *
     * @return bool Whether template caching is enabled
     */
    private function _isTemplateCachingEnabled(): bool
    {
        return !!Craft::$app->getConfig()->getGeneral()->enableTemplateCaching;
    }

    /**
     * Defines a data cache key that should be used for a template cache.
     *
     * @param string $key
     * @param bool $global
     */
    private function _cacheKey(string $key, bool $global): string
    {
        $cacheKey = "template::$key::" . Craft::$app->getSites()->getCurrentSite()->id;

        if (!$global) {
            $cacheKey .= '::' . $this->_path();
        }

        return $cacheKey;
    }

    /**
     * Returns the current request path, including a "site:" or "cp:" prefix.
     *
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    private function _path(): string
    {
        if ($this->_path !== null) {
            return $this->_path;
        }

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_path = 'cp:';
        } else {
            $this->_path = 'site:';
        }

        $this->_path .= Craft::$app->getRequest()->getPathInfo();
        if (Craft::$app->getDb()->getIsMysql()) {
            $this->_path = StringHelper::encodeMb4($this->_path);
        }

        if (($pageNum = Craft::$app->getRequest()->getPageNum()) != 1) {
            $this->_path .= '/' . Craft::$app->getConfig()->getGeneral()->getPageTrigger() . $pageNum;
        }

        return $this->_path;
    }

    /**
     * @param string|array|null $flags
     * @param string $delimiter
     * @return array
     */
    private function _getTagsForFlags($flags, string $delimiter = '|'): array
    {
        $tagsArray = ['template', 'cacheflag'];
        if (\is_array($flags)) {
            $flags = \implode(',', \array_map(function ($flag) {
                return \preg_replace('/\s+/', '', $flag);
            }, $flags));
        } else {
            $flags = \preg_replace('/\s+/', '', $flags);
        }
        $flags = \array_filter(\explode($delimiter, $flags));
        $tagsArray = \array_merge($tagsArray, \array_map(function (string $flag) {
            return "cacheflag::$flag";
        }, $flags));
        if ($this->_collectElementTags) {
            $tagsArray[] = 'element';
        }
        return \array_unique(($tagsArray));
    }

}
