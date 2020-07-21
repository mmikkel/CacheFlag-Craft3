<?php
/**
 * Cache Flag plugin for Craft CMS 3.x
 *
 * Flag and clear template caches.
 *
 * @link      https://vaersaagod.no
 * @copyright Copyright (c) 2018 Mats Mikkel Rummelhoff
 */

namespace mmikkel\cacheflag;

use Craft;
use craft\base\ElementInterface;
use craft\base\Plugin;
use craft\elements\actions\SetStatus;
use craft\events\ElementEvent;
use craft\events\ElementActionEvent;
use craft\events\MergeElementsEvent;
use craft\events\MoveElementEvent;
use craft\events\PluginEvent;
use craft\events\RegisterCacheOptionsEvent;
use craft\events\TemplateEvent;
use craft\helpers\ElementHelper;
use craft\services\Elements;
use craft\services\Plugins;
use craft\services\Structures;
use craft\utilities\ClearCaches;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\web\View;

use yii\base\Event;
use yii\base\InvalidConfigException;

use mmikkel\cacheflag\services\CacheFlagService;
use mmikkel\cacheflag\services\TemplateCachesService;
use mmikkel\cacheflag\twigextensions\Extension as CacheFlagTwigExtension;
use mmikkel\cacheflag\variables\CpVariable;

/**
 * Class CacheFlag
 *
 * @author    Mats Mikkel Rummelhoff
 * @package   CacheFlag
 * @since     1.0.0
 *
 * @property  CacheFlagService $cacheFlag
 * @property  TemplateCachesService $templateCaches
 */
class CacheFlag extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var CacheFlag
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.1.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Register services
        $this->setComponents([
            'cacheFlag' => CacheFlagService::class,
            'templateCaches' => TemplateCachesService::class,
        ]);

        Craft::$app->getView()->registerTwigExtension(new CacheFlagTwigExtension());

        $this->addElementEventListeners();

        Event::on(ClearCaches::class, ClearCaches::EVENT_REGISTER_CACHE_OPTIONS,
            static function (RegisterCacheOptionsEvent $event) {
                $event->options[] = [
                    'key' => 'cacheflag-flagged-caches',
                    'label' => Craft::t('cache-flag', 'Flagged template caches'),
                    'action' => [CacheFlag::getInstance()->cacheFlag, 'invalidateAllFlaggedCaches'],
                    'info' => Craft::t('cache-flag', 'All template caches flagged using Cache Flag'),
                ];
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_LOAD_PLUGINS,
            function () {
                $this->maybeRegisterResources();
            }
        );

        Craft::info(
            Craft::t(
                'cache-flag',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * Add event listeners for cache breaking
     */
    protected function addElementEventListeners()
    {
        Event::on(
            Elements::class,
            Elements::EVENT_AFTER_SAVE_ELEMENT,
            function (ElementEvent $event) {
                $element = $event->element;
                if (!$element || ElementHelper::isDraftOrRevision($element)) {
                    return;
                }
                CacheFlag::$plugin->cacheFlag->invalidateFlaggedCachesByElement($element);
            }
        );

        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_DELETE_ELEMENT,
            function (ElementEvent $event) {
                $element = $event->element;
                if (!$element || ElementHelper::isDraftOrRevision($element)) {
                    return;
                }
                CacheFlag::$plugin->cacheFlag->invalidateFlaggedCachesByElement($element);
            }
        );

        Event::on(
            Structures::class,
            Structures::EVENT_AFTER_MOVE_ELEMENT,
            function (MoveElementEvent $event) {
                $element = $event->element;
                if (!$element || ElementHelper::isDraftOrRevision($element)) {
                    return;
                }
                CacheFlag::$plugin->cacheFlag->invalidateFlaggedCachesByElement($element);
            }
        );

        Event::on(
            Elements::class,
            Elements::EVENT_AFTER_PERFORM_ACTION,
            function (ElementActionEvent $event) {

                /* @var ElementQueryInterface|null $criteria */
                $criteria = $event->criteria;

                if (!$criteria) {
                    return;
                }

                /* @var ElementActionInterface|null $action */
                $action = $event->action;

                if (!$action || !\in_array(\get_class($action), [
                        SetStatus::class,
                    ])) {
                    return;
                }

                /** @var ElementInterface[] $elements */
                $elements = $criteria->all();

                foreach ($elements as $element) {
                    if (ElementHelper::isDraftOrRevision($element)) {
                        continue;
                    }
                    CacheFlag::$plugin->cacheFlag->invalidateFlaggedCachesByElement($element);
                }
            }
        );
    }

    /**
     *  Maybe register CP assets bundle and variable
     */
    protected function maybeRegisterResources()
    {

        $request = Craft::$app->getRequest();

        if (!Craft::$app->getUser() || !$request->getIsCpRequest() || $request->getIsConsoleRequest() || ($request->getSegments()[0] ?? null) !== 'cache-flag') {
            return;
        }

        // Register CP variable
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('cacheFlag', CpVariable::class);
            }
        );
    }

}
