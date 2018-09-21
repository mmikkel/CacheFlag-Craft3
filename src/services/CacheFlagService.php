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

use craft\base\Element;
use mmikkel\cacheflag\CacheFlag;
use mmikkel\cacheflag\events\BeforeDeleteFlaggedTemplateCachesEvent;
use mmikkel\cacheflag\events\AfterDeleteFlaggedTemplateCachesEvent;
use mmikkel\cacheflag\records\Flagged;
use mmikkel\cacheflag\records\Flags;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\UrlHelper;


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
    const EVENT_BEFORE_DELETE_FLAGGED_CACHES = 'beforeDeleteFlaggedCaches';

    /**
     * @event Event The event that is triggered after flagged template caches are deleted.
     */
    const EVENT_AFTER_DELETE_FLAGGED_CACHES = 'afterDeleteFlaggedCaches';

    // Public Methods
    // =========================================================================

    /**
     * @return array
     */
    public function getCpTabs(): array
    {
        return [
            'cacheFlagIndex' => array(
                'label' => '',
                'url' => UrlHelper::url('cache-flag'),
            ),
            'about' => array(
                'label' => Craft::t('cache-flag', 'About Cache Flag'),
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
        return (new Query())
            ->select('*')
            ->from([Flags::tableName()])
            ->all();
    }

    /**
     * @param $flags
     * @param string $sourceColumn
     * @param string $sourceId
     * @throws \Throwable
     * @throws \yii\db\Exception
     */
    public function saveFlags($flags, string $sourceColumn, string $sourceId)
    {

        if (!$flags) {
            return;
        }

        if (\is_array($flags)) {
            $flags = \implode(',', $flags);
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {

            $flagsId = (int)(new Query())
                ->select(['id'])
                ->from([Flags::tableName()])
                ->where([$sourceColumn => $sourceId])
                ->scalar();

            if ($flagsId) {

                Craft::$app->getDb()->createCommand()
                    ->update(
                        Flags::tableName(),
                        [
                            'flags' => $flags,
                        ],
                        [
                            $sourceColumn => $sourceId,
                        ],
                        null,
                        false
                    )
                    ->execute();

            } else {

                Craft::$app->getDb()->createCommand()
                    ->insert(
                        Flags::tableName(),
                        [
                            'flags' => $flags,
                            $sourceColumn => $sourceId,
                        ],
                        false)
                    ->execute();
            }

            $transaction->commit();

        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    /**
     * @param string $sourceColumn
     * @param string $sourceValue
     * @throws \Throwable
     * @throws \yii\db\Exception
     */
    public function deleteFlagsBySource(string $sourceColumn, string $sourceValue)
    {

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {

            Craft::$app->getDb()->createCommand()
                ->delete(
                    Flags::tableName(),
                    [$sourceColumn => $sourceValue]
                )
                ->execute();

            $transaction->commit();

        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    /**
     * @param string|array $flags
     * @return bool
     */
    public function flagsHasCaches($flags): bool
    {

        $query = (new Query())
            ->select(['cacheId', 'flags'])
            ->from([Flagged::tableName()]);

        if (!\is_array($flags)) {
            $flags = \explode(',', \preg_replace('/\s+/', '', $flags));
        } else {
            $flags = \array_map(function ($flag) {
                return \preg_replace('/\s+/', '', $flag);
            }, $flags);
        }

        $dbDriver = Craft::$app->getDb()->getDriverName();

        foreach ($flags as $flag) {
            if ($dbDriver === 'pgsql') {
                $query->orWhere("'{$flag}' = ANY(string_to_array(flags, ','))");
            } else {
                $query->orWhere('FIND_IN_SET("' . $flag . '",flags)');
            }
        }

        return !!$query->scalar();

    }

    /**
     * @return bool
     */
    public function deleteAllFlaggedCaches(): bool
    {
        $cacheIds = (new Query())
            ->select(['cacheId'])
            ->from([Flagged::tableName()])
            ->column();
        if (!$cacheIds) {
            return true;
        }
        return Craft::$app->getTemplateCaches()->deleteCacheById($cacheIds);
    }

    /**
     * @param Element $element
     * @return bool
     */
    public function deleteFlaggedCachesByElement(Element $element): bool
    {

        // Collect all flags for this element
        $query = (new Query())
            ->select(['flags'])
            ->from(Flags::tableName());

        $elementType = \get_class($element);

        switch ($elementType) {
            case 'craft\elements\Asset':
                $query->orWhere([
                    'volumeId' => $element->volumeId,
                ]);
                break;
            case 'craft\elements\Category':
                $query->orWhere([
                    'categoryGroupId' => $element->groupId,
                ]);
                break;
            case 'craft\elements\Entry':
                $query->orWhere([
                    'sectionId' => $element->sectionId,
                ]);
                break;
            case 'craft\elements\GlobalSet':
                $query->orWhere([
                    'globalSetId' => $element->id,
                ]);
                break;
            case 'craft\elements\Tag':
                $query->orWhere([
                    'tagGroupId' => $element->groupId,
                ]);
                break;
            case 'craft\elements\User':
                foreach ($element->groups as $userGroup) {
                    $query->orWhere([
                        'userGroupId' => $userGroup->id,
                    ]);
                }
                break;
        }

        $query->orWhere([
            'elementType' => $elementType,
        ]);

        $flags = \array_unique($query->column());

        return $this->deleteFlaggedCachesByFlags($flags);
    }

    /**
     * @param $flags
     * @return bool
     */
    public function deleteFlaggedCachesByFlags($flags): bool
    {

        if (!$flags) {
            return false;
        }

        if (\is_array($flags)) {
            $flags = $this->implodeFlagsArray($flags);
        } else {
            $flags = \preg_replace('/\s+/', '', $flags);
        }

        $flags = \array_values(\array_unique(\array_filter(\explode(',', $flags))));

        $query = (new Query())
            ->select(['cacheId', 'flags'])
            ->from([Flagged::tableName()]);

        $dbDriver = Craft::$app->getDb()->getDriverName();

        foreach ($flags as $flag) {
            if ($dbDriver === 'pgsql') {
                $query->orWhere("'{$flag}' = ANY(string_to_array(flags, ','))");
            } else {
                $query->orWhere('FIND_IN_SET("' . $flag . '",flags)');
            }
        }

        $rows = $query->all();

        return $this->deleteCaches($rows);

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
                $flags .= "{$this->implodeFlagsArray($item, ',')},";
            } else {
                $flags .= \preg_replace('/\s+/', '', $item) . ',';
            }
        }

        $flags = \substr($flags, 0, 0 - strlen(','));

        return $flags;
    }

    /**
     * @param array $rows
     * @return bool
     */
    protected function deleteCaches(array $rows): bool
    {

        if (empty($rows)) {
            return true;
        }

        $cacheIds = [];
        $cacheFlags = [];

        foreach ($rows as $row) {
            $cacheIds[] = (int)$row['cacheId'];
            $cacheFlags = \array_merge($cacheFlags, \explode(',', $row['flags']));
        }

        $cacheFlags = \array_unique(\array_filter($cacheFlags));

        // Fire a 'beforeDeleteFlaggedCaches' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_FLAGGED_CACHES)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_FLAGGED_CACHES, new BeforeDeleteFlaggedTemplateCachesEvent([
                'cacheIds' => $cacheIds,
                'flags' => $cacheFlags,
            ]));
        }

        $success = Craft::$app->getTemplateCaches()->deleteCacheById($cacheIds);

        // Fire a 'afterDeleteFlaggedCaches' event
        if ($success && $this->hasEventHandlers(self::EVENT_AFTER_DELETE_FLAGGED_CACHES)) {
            $this->trigger(self::EVENT_AFTER_DELETE_FLAGGED_CACHES, new AfterDeleteFlaggedTemplateCachesEvent([
                'cacheIds' => $cacheIds,
                'flags' => $cacheFlags,
            ]));
        }

        return $success;

    }
}
