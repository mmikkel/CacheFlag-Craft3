<?php
/**
 * Cache Flag plugin for Craft CMS 3.x
 *
 * Flag and clear template caches.
 *
 * @link      https://vaersaagod.no
 * @copyright Copyright (c) 2018 Mats Mikkel Rummelhoff
 */

namespace mmikkel\cacheflag\variables;

use mmikkel\cacheflag\CacheFlag;

use Craft;

/**
 * @author    Mats Mikkel Rummelhoff
 * @package   CacheFlag
 * @since     1.0.0
 */
class CpVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public function getVersion()
    {
        return Craft::$app->getPlugins()->getPlugin('cache-flag')->getVersion();
    }

    /**
     * @return string|null
     */
    public function getDocumentationUrl()
    {
        return Craft::$app->getPlugins()->getComposerPluginInfo('cache-flag')['documentationUrl'] ?? null;
    }

    /**
     * @return array
     */
    public function getCpTabs(): array
    {
        return CacheFlag::getInstance()->cacheFlag->getCpTabs();
    }

    /**
     * @return array
     */
    public function getSources(): array
    {
        return CacheFlag::getInstance()->cacheFlag->getSources();
    }

    /**
     * @return array
     */
    public function getAllFlags(): array
    {
        return CacheFlag::getInstance()->cacheFlag->getAllFlags();
    }

    /**
     * @param string $flags
     * @return bool
     * @deprecated since 1.1.0
     */
    public function flagsHasCaches($flag): bool
    {
        return CacheFlag::getInstance()->cacheFlag->flagsHasCaches($flag);
    }
}
