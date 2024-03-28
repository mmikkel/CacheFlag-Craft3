<?php

namespace mmikkel\cacheflag;

use Craft;
use craft\base\Element;
use craft\base\ElementActionInterface;
use craft\base\ElementInterface;
use craft\base\Plugin;
use craft\elements\actions\SetStatus;
use craft\elements\db\ElementQueryInterface;
use craft\events\ElementEvent;
use craft\events\ElementActionEvent;
use craft\events\MoveElementEvent;
use craft\events\PluginEvent;
use craft\events\RegisterCacheOptionsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\ElementHelper;
use craft\services\Elements;
use craft\services\Plugins;
use craft\services\ProjectConfig;
use craft\services\Structures;
use craft\services\Utilities;
use craft\utilities\ClearCaches;

use yii\base\Event;

use mmikkel\cacheflag\services\CacheFlagService;
use mmikkel\cacheflag\services\ProjectConfig as CacheFlagProjectConfigService;
use mmikkel\cacheflag\services\TemplateCachesService;
use mmikkel\cacheflag\twigextensions\Extension as CacheFlagTwigExtension;
use mmikkel\cacheflag\utilities\CacheFlagUtility;

/**
 * Class CacheFlag
 *
 * @author    Mats Mikkel Rummelhoff
 * @package   CacheFlag
 * @since     1.0.0
 *
 * @property CacheFlagService $cacheFlag
 * @property CacheFlagProjectConfigService $projectConfig
 * @property TemplateCachesService $templateCaches
 */
class CacheFlag extends Plugin
{

    /** @var string */
    public string $schemaVersion = '1.0.2';

    /** @var bool */
    public bool $hasCpSection = false;

    /** @var bool */
    public bool $hasCpSettings = false;

    /** @inheritdoc */
    public function init(): void
    {
        parent::init();

        // Register services
        $this->setComponents([
            'cacheFlag' => CacheFlagService::class,
            'projectConfig' => CacheFlagProjectConfigService::class,
            'templateCaches' => TemplateCachesService::class,
        ]);

        $this->_initProjectConfig();
        $this->_addElementEventListeners();

        // Register custom Twig extension
        Craft::$app->getView()->registerTwigExtension(new CacheFlagTwigExtension());

        // Add tag option to the Clear Caches utility to invalidate all flagged caches
        Event::on(ClearCaches::class, ClearCaches::EVENT_REGISTER_TAG_OPTIONS,
            static function (RegisterCacheOptionsEvent $event) {
                $event->options[] = [
                    'key' => 'cacheflag-flagged-caches',
                    'label' => Craft::t('cache-flag', 'Flagged template caches'),
                    'tag' => 'cacheflag',
                    'info' => Craft::t('cache-flag', 'Template caches flagged using Cache Flag'),
                ];
            }
        );

        // Register utility
        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITIES,
            static function(RegisterComponentTypesEvent $event) {
                $event->types[] = CacheFlagUtility::class;
            }
        );

    }

    /**
     * @return void
     */
    private function _initProjectConfig(): void
    {
        Event::on(
            ProjectConfig::class,
            ProjectConfig::EVENT_REBUILD,
            [$this->projectConfig, 'onProjectConfigRebuild']
        );

        Craft::$app->getProjectConfig()
            ->onAdd('cacheFlags.{uid}', [$this->projectConfig, 'onProjectConfigChange'])
            ->onUpdate('cacheFlags.{uid}', [$this->projectConfig, 'onProjectConfigChange'])
            ->onRemove('cacheFlags.{uid}', [$this->projectConfig, 'onProjectConfigDelete']);

        // Flush the project config when the plugin is uninstalled
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_UNINSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    Craft::$app->getProjectConfig()->remove('cacheFlags');
                }
            }
        );
    }

    /**
     * @return void
     */
    private function _addElementEventListeners(): void
    {
        // Invalidate flagged caches when elements are saved
        Event::on(
            Elements::class,
            Elements::EVENT_AFTER_SAVE_ELEMENT,
            function (ElementEvent $event) {
                $this->_maybeInvalidateFlaggedCachesByElement($event->element);
            }
        );

        // Invalidate flagged caches when elements are deleted
        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_DELETE_ELEMENT,
            function (ElementEvent $event) {
                $this->_maybeInvalidateFlaggedCachesByElement($event->element);
            }
        );

        // Invalidate flagged caches when structure entries are moved
        Event::on(
            Structures::class,
            Structures::EVENT_AFTER_MOVE_ELEMENT,
            function (MoveElementEvent $event) {
                $this->_maybeInvalidateFlaggedCachesByElement($event->element);
            }
        );

        // Invalidate flagged caches when elements change status
        Event::on(
            Elements::class,
            Elements::EVENT_AFTER_PERFORM_ACTION,
            function (ElementActionEvent $event) {

                /* @var ElementActionInterface|null $action */
                $action = $event->action;
                if (!$action instanceof SetStatus) {
                    return;
                }

                /* @var ElementQueryInterface|null $criteria */
                $criteria = $event->criteria;
                if (empty($criteria)) {
                    return;
                }

                /** @var ElementInterface[] $elements */
                $elements = $criteria->all();
                foreach ($elements as $element) {
                    $this->_maybeInvalidateFlaggedCachesByElement($element);
                }
            }
        );
    }

    /**
     * @param ElementInterface|null $element
     * @return void
     */
    private function _maybeInvalidateFlaggedCachesByElement(?ElementInterface $element): void
    {
        /** @var Element $element */
        // This try/catch is introduced to mitigate an edge case where a nested element could have an invalid (deleted) owner ID.
        // See https://github.com/mmikkel/CacheFlag-Craft3/issues/21
        try {
            if (ElementHelper::isDraftOrRevision($element)) {
                return;
            }
        } catch (\Throwable) {
            // We don't care about handling this exception
        }
        CacheFlag::getInstance()->cacheFlag->invalidateFlaggedCachesByElement($element);
    }

}
