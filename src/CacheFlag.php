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

use mmikkel\cacheflag\assetbundles\CpBundle;
use mmikkel\cacheflag\services\CacheFlagService;
use mmikkel\cacheflag\services\TemplateCachesService;
use mmikkel\cacheflag\twigextensions\Extension;
use mmikkel\cacheflag\variables\CpVariable;

use Craft;
use craft\base\Plugin;
use craft\elements\actions\SetStatus;
use craft\events\ElementEvent;
use craft\events\ElementActionEvent;
use craft\events\MergeElementsEvent;
use craft\events\MoveElementEvent;
use craft\events\PluginEvent;
use craft\services\Elements;
use craft\services\Plugins;
use craft\services\Structures;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;

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
    public $schemaVersion = '1.0.0';

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

        Craft::$app->getView()->registerTwigExtension(new Extension());

        $this->addEventListeners();
        $this->registerResources();

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
    protected function addEventListeners()
    {
        Event::on(
            Elements::class,
            Elements::EVENT_AFTER_SAVE_ELEMENT,
            function (ElementEvent $event) {
                if ($event->element) {
                    CacheFlag::$plugin->cacheFlag->deleteFlaggedCachesByElement($event->element);
                }
            }
        );

        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_DELETE_ELEMENT,
            function (ElementEvent $event) {
                if ($event->element) {
                    CacheFlag::$plugin->cacheFlag->deleteFlaggedCachesByElement($event->element);
                }
            }
        );

        Event::on(
            Structures::class,
            Structures::EVENT_AFTER_MOVE_ELEMENT,
            function (MoveElementEvent $event) {
                if ($event->element) {
                    CacheFlag::$plugin->cacheFlag->deleteFlaggedCachesByElement($event->element);
                }
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

                $elements = $criteria->all();

                foreach ($elements as $element) {
                    CacheFlag::$plugin->cacheFlag->deleteFlaggedCachesByElement($element);
                }
            }
        );
    }

    /**
     *  Maybe register CP assets bundle and variable
     */
    protected function registerResources()
    {

        $request = Craft::$app->getRequest();

        if (!Craft::$app->getUser() || !$request->getIsCpRequest() || $request->getIsConsoleRequest() || ($request->getSegments()[0] ?? null) !== 'cache-flag') {
            return;
        }

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_LOAD_PLUGINS,
            function () {
                Craft::$app->getView()->registerAssetBundle(CpBundle::class);
            }
        );

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
