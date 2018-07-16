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
        $plugin = Craft::$app->getPlugins()->getPlugin('cache-flag');
        return $plugin->getVersion();
    }

    /**
     * @return string|null
     */
    public function getDocumentationUrl()
    {
        $info = Craft::$app->getPlugins()->getComposerPluginInfo('cache-flag');
        return $info['documentationUrl'] ?? null;
    }

    /**
     * @return array
     */
    public function getCpTabs(): array
    {
        return CacheFlag::$plugin->cacheFlag->getCpTabs();
    }

    /**
     * @return array
     */
    public function getSources(): array
    {
        return CacheFlag::$plugin->cacheFlag->getSources();
    }

    /**
     * @return array
     */
    public function getAllFlags(): array
    {
        return CacheFlag::$plugin->cacheFlag->getAllFlags();
    }

    /**
     * @param string $flags
     * @return bool
     */
    public function flagsHasCaches($flag): bool
    {
        return CacheFlag::$plugin->cacheFlag->flagsHasCaches($flag);
    }
}
