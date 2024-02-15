<?php

namespace mmikkel\cacheflag\utilities;

use Craft;
use craft\base\Utility;

use mmikkel\cacheflag\CacheFlag;

class CacheFlagUtility extends Utility
{

    /** @inheritdoc */
    public static function id(): string
    {
        return 'cache-flag-utility';
    }

    /** @inheritdoc */
    public static function displayName(): string
    {
        return 'Cache Flag';
    }

    /** @inheritdoc */
    public static function icon(): ?string
    {
        return 'flag';
    }

    /**
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     */
    public static function contentHtml(): string
    {
        $sources = [
            'sections' => [
                'column' => 'sectionId',
                'name' => Craft::t('app', 'Sections'),
                'sources' => Craft::$app->getEntries()->getAllSections(),
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
                'sources' => array_map(function (string $elementType) {
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

        return Craft::$app->getView()->renderTemplate('cache-flag/_utility.twig', [
            'sources' => $sources,
            'allFlags' => CacheFlag::getInstance()->cacheFlag->getAllFlags(),
            'version' => Craft::$app->getPlugins()->getPlugin('cache-flag')->getVersion(),
            'documentationUrl' => Craft::$app->getPlugins()->getComposerPluginInfo('cache-flag')['documentationUrl'] ?? null,
        ]);
    }
}
