<?php
/**
 * Cache Flag plugin for Craft CMS 3.x
 *
 * Flag and clear template caches.
 *
 * @link      https://vaersaagod.no
 * @copyright Copyright (c) 2018 Mats Mikkel Rummelhoff
 */

namespace mmikkel\cacheflag\services;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

use yii\caching\TagDependency;

use mmikkel\cacheflag\CacheFlag;
use mmikkel\cacheflag\events\BeforeDeleteFlaggedTemplateCachesEvent;
use mmikkel\cacheflag\events\AfterDeleteFlaggedTemplateCachesEvent;
use mmikkel\cacheflag\events\FlaggedTemplateCachesEvent;
use mmikkel\cacheflag\records\Flagged;
use mmikkel\cacheflag\records\Flags;


/**
 * @author    Mats Mikkel Rummelhoff
 * @package   CacheFlag
 * @since     1.0.0
 */
class CacheFlagService extends Component
{

    // Constants
    // =========================================================================
    /**
     * @event Event The event that is triggered before flagged template caches are deleted.
     */
    const EVENT_BEFORE_INVALIDATE_FLAGGED_CACHES = 'beforeInvalidateFlaggedCaches';

    /**
     * @event Event The event that is triggered after flagged template caches are invalidated.
     */
    const EVENT_AFTER_INVALIDATE_FLAGGED_CACHES = 'afterInvalidateFlaggedCaches';

    /**
     * @event Event The event that is triggered before flagged template caches are deleted.
     * @deprecated since 1.1.0. Use [[\mmikkel\cacheflag\services\CacheFlagService::EVENT_BEFORE_INVALIDATE_FLAGGED_CACHES]] instead.
     */
    const EVENT_BEFORE_DELETE_FLAGGED_CACHES = 'beforeDeleteFlaggedCaches';

    /**
     * @event Event The event that is triggered after flagged template caches are deleted.
     * @deprecated since 1.1.0. Use [[\mmikkel\cacheflag\services\CacheFlagService::EVENT_AFTER_INVALIDATE_FLAGGED_CACHES]] instead.
     */
    const EVENT_AFTER_DELETE_FLAGGED_CACHES = 'afterDeleteFlaggedCaches';

    // Public Methods
    // =========================================================================

    /**
     * @return array
     */
    public function getAllFlags(): array
    {
        return (new Query())
            ->select('*')
            ->from([Flags::tableName()])
            ->all();
    }

    /**
     * @param string|string[] $flags
     * @param string $sourceColumn
     * @param string $sourceValue
     * @throws \Throwable
     * @throws \yii\db\Exception
     */
    public function saveFlags($flags, string $sourceColumn, string $sourceValue)
    {

        if (!$flags) {
            return;
        }

        if (\is_array($flags)) {
            $flags = \implode(',', $flags);
        }

        $uid = (new Query())
            ->select(['uid'])
            ->from(Flags::tableName())
            ->where([$sourceColumn => $sourceValue])
            ->scalar();

        $isNew = !$uid;
        if ($isNew) {
            $uid = StringHelper::UUID();
        }

        $sourceKey = null;

        switch ($sourceColumn) {
            case 'sectionId':
                $sourceKey = 'section';
                $sourceValue = Db::uidById(Table::SECTIONS, (int)$sourceValue);
                break;
            case 'categoryGroupId':
                $sourceKey = 'categoryGroup';
                $sourceValue = Db::uidById(Table::CATEGORYGROUPS, (int)$sourceValue);
                break;
            case 'tagGroupId':
                $sourceKey = 'tagGroup';
                $sourceValue = Db::uidById(Table::TAGGROUPS, (int)$sourceValue);
                break;
            case 'userGroupId':
                $sourceKey = 'userGroup';
                $sourceValue = Db::uidById(Table::USERGROUPS, (int)$sourceValue);
                break;
            case 'volumeId':
                $sourceKey = 'volume';
                $sourceValue = Db::uidById(Table::VOLUMES, (int)$sourceValue);
                break;
            case 'globalSetId':
                $sourceKey = 'globalSet';
                $sourceValue = Db::uidById(Table::GLOBALSETS, (int)$sourceValue);
                break;
            case 'elementType':
                $sourceKey = 'elementType';
                break;
            default:
                return;
        }

        if (!$sourceValue) {
            return;
        }

        // Save it to the project config
        $path = "cacheFlags.{$uid}";
        Craft::$app->projectConfig->set($path, [
            'source' => "$sourceKey:$sourceValue",
            'flags' => $flags,
        ]);
    }

    /**
     * @param string $sourceColumn
     * @param string $sourceValue
     * @throws \Throwable
     * @throws \yii\db\Exception
     */
    public function deleteFlagsBySource(string $sourceColumn, string $sourceValue)
    {

        $uid = (new Query())
            ->select(['uid'])
            ->from(Flags::tableName())
            ->where([$sourceColumn => $sourceValue])
            ->scalar();

        if (!$uid) {
            return false;
        }

        // Remove it from the project config
        $path = "cacheFlags.{$uid}";
        Craft::$app->projectConfig->remove($path);
    }

    /**
     * @return bool
     * @deprecated since 1.1.0
     */
    public function flagsHasCaches(): bool
    {
        return true;
    }

    /**
     * Invalidate all flagged template caches
     */
    public function invalidateAllFlaggedCaches()
    {
        TagDependency::invalidate(Craft::$app->getCache(), 'cacheflag');
    }

    /**
     * @return bool
     * @deprecated since 1.1.0. Use [[\mmikkel\cacheflag\services\CacheFlagService::invalidateAllFlaggedCaches()]] instead.
     */
    public function deleteAllFlaggedCaches(): bool
    {
        Craft::$app->getDeprecator()->log('CacheFlagService::deleteAllFlaggedCaches()', 'deleteAllFlaggedCaches() has been deprecated. Use \mmikkel\cacheflag\services\CacheFlagService::invalidateAllFlaggedCaches() instead.');
        $this->invalidateAllFlaggedCaches();
        return true;
    }

    /**
     * @param Element $element
     * @return bool
     */
    public function invalidateFlaggedCachesByElement(Element $element): bool
    {
        // Collect all flags for this element
        $query = (new Query())
            ->select(['flags'])
            ->from(Flags::tableName());

        $elementType = \get_class($element);
        $dynamicFlags = ["element:$element->id", "element:$element->uid"];

        switch ($elementType) {
            case 'craft\elements\Asset':
                /** @var Asset $element */
                $query->orWhere([
                    'volumeId' => $element->volumeId,
                ]);
                $dynamicFlags[] = "asset:$element->id";
                $dynamicFlags[] = "asset:$element->uid";
                break;
            case 'craft\elements\Category':
                /** @var Category $element */
                $query->orWhere([
                    'categoryGroupId' => $element->groupId,
                ]);
                $dynamicFlags[] = "category:$element->id";
                $dynamicFlags[] = "category:$element->uid";
                break;
            case 'craft\elements\Entry':
                /** @var Entry $element */
                $query->orWhere([
                    'sectionId' => $element->sectionId,
                ]);
                $dynamicFlags[] = "entry:$element->id";
                $dynamicFlags[] = "entry:$element->uid";
                break;
            case 'craft\elements\GlobalSet':
                /** @var GlobalSet $element */
                $query->orWhere([
                    'globalSetId' => $element->id,
                ]);
                $dynamicFlags[] = "globalSet:$element->id";
                $dynamicFlags[] = "globalSet:$element->uid";
                break;
            case 'craft\elements\Tag':
                /** @var Tag $element */
                $query->orWhere([
                    'tagGroupId' => $element->groupId,
                ]);
                $dynamicFlags[] = "tag:$element->id";
                $dynamicFlags[] = "tag:$element->uid";
                break;
            case 'craft\elements\User':
                /** @var User $element */
                foreach ($element->getGroups() as $userGroup) {
                    $query->orWhere([
                        'userGroupId' => $userGroup->id,
                    ]);
                }
                $dynamicFlags[] = "user:$element->id";
                $dynamicFlags[] = "user:$element->uid";
                break;
        }

        $query->orWhere([
            'elementType' => $elementType,
        ]);

        $flags = \array_unique(\array_merge($query->column(), $dynamicFlags));

        return $this->invalidateFlaggedCachesByFlags($flags);
    }

    /**
     * @param Element $element
     * @return bool
     * @deprecated since 1.1.0. Use [[\mmikkel\cacheflag\services\CacheFlagService::invalidateFlaggedCachesByElement()]] instead.
     */
    public function deleteFlaggedCachesByElement(Element $element): bool
    {
        Craft::$app->getDeprecator()->log('CacheFlagService::deleteFlaggedCachesByElement()', 'deleteFlaggedCachesByElement() has been deprecated. Use \mmikkel\cacheflag\services\CacheFlagService::invalidateFlaggedCachesByElement() instead.');
        return $this->invalidateFlaggedCachesByElement($element);
    }

    /**
     * @param string|string[]|null $flags
     * @return bool
     */
    public function invalidateFlaggedCachesByFlags($flags): bool
    {

        if (!$flags) {
            return false;
        }

        if (\is_array($flags)) {
            $flags = $this->implodeFlagsArray($flags);
        } else {
            $flags = \preg_replace('/\s+/', '', $flags);
        }

        $flags = \array_values(\array_unique(\explode(',', $flags)));

        if (empty($flags)) {
            return false;
        }

        // Fire a `beforeInvalidateFlaggedCaches` event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_INVALIDATE_FLAGGED_CACHES)) {
            $this->trigger(self::EVENT_BEFORE_INVALIDATE_FLAGGED_CACHES, new FlaggedTemplateCachesEvent([
                'flags' => $flags,
            ]));
        }

        $flagTags = \array_map(function (string $flag) {
            return "cacheflag::$flag";
        }, $flags);

        TagDependency::invalidate(Craft::$app->getCache(), $flagTags);

        // Fire a 'afterInvalidateFlaggedCaches' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_INVALIDATE_FLAGGED_CACHES)) {
            $this->trigger(self::EVENT_AFTER_INVALIDATE_FLAGGED_CACHES, new FlaggedTemplateCachesEvent([
                'flags' => $flags,
            ]));
        }

        return true;
    }

    /**
     * @param string|string[] $flags
     * @return bool
     * @deprecated since 1.1.0. Use [[\mmikkel\cacheflag\services\CacheFlagService::invalidateFlaggedCachesByFlags()]] instead.
     */
    public function deleteFlaggedCachesByFlags($flags): bool
    {
        Craft::$app->getDeprecator()->log('CacheFlagService::deleteFlaggedCachesByFlags()', 'deleteFlaggedCachesByFlags() has been deprecated. Use \mmikkel\cacheflag\services\CacheFlagService::invalidateFlaggedCachesByFlags() instead.');
        return $this->invalidateFlaggedCachesByFlags($flags);
    }

    /*
     * Protected methods
     */
    /**
     * @param array $flagsArray
     * @return string
     */
    protected function implodeFlagsArray(array $flagsArray): string
    {

        $flags = '';

        foreach ($flagsArray as $item) {
            if (\is_array($item)) {
                $flags .= "{$this->implodeFlagsArray($item)},";
            } else {
                $flags .= \preg_replace('/\s+/', '', $item) . ',';
            }
        }

        $flags = \substr($flags, 0, 0 - strlen(','));

        return $flags;
    }
}
