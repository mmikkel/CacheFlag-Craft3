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

use Craft;
use craft\helpers\UrlHelper;

use mmikkel\cacheflag\CacheFlag;

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
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            return [];
        }
        return [
            'cacheFlagIndex' => array(
                'label' => Craft::t('cache-flag', 'Flags'),
                'url' => UrlHelper::url('cache-flag'),
            ),
            'about' => array(
                'label' => Craft::t('cache-flag', 'About'),
                'url' => UrlHelper::url('cache-flag/about'),
            ),
        ];
    }

    /**
     * @return array
     */
    public function getSources(): array
    {
        $sources = [
            'sections' => [
                'column' => 'sectionId',
                'name' => Craft::t('app', 'Sections'),
                'sources' => Craft::$app->getSections()->getAllSections(),
            ],
            'categoryGroups' => [
                'column' => 'categoryGroupId',
                'name' => Craft::t('app', 'Category Groups'),
                'sources' => Craft::$app->getCategories()->getAllGroups(),
            ],
            'volumes' => [
                'column' => 'volumeId',
                'name' => Craft::t('app', 'Asset Volumes'),
                'sources' => Craft::$app->getVolumes()->getAllVolumes(),
            ],
            'globalSets' => [
                'column' => 'globalSetId',
                'name' => Craft::t('app', 'Global Sets'),
                'sources' => Craft::$app->getGlobals()->getAllSets(),
            ],
            'elementTypes' => [
                'column' => 'elementType',
                'name' => Craft::t('app', 'Element Types'),
                'sources' => \array_map(function (string $elementType) {
                    return [
                        'id' => $elementType,
                        'name' => $elementType,
                    ];
                }, Craft::$app->getElements()->getAllElementTypes()),
            ],
        ];

        if (Craft::$app->getEdition() === 1) {
            $sources['userGroups'] = [
                'column' => 'userGroupId',
                'name' => Craft::t('app', 'User Groups'),
                'sources' => Craft::$app->getUserGroups()->getAllGroups(),
            ];
        }

        return $sources;
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
    public function flagsHasCaches(): bool
    {
        return true;
    }
}
