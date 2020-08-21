<?php

namespace mmikkel\cacheflag\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\db\Table;
use craft\events\ConfigEvent;
use craft\events\RebuildConfigEvent;
use craft\helpers\Db;

use mmikkel\cacheflag\records\Flags;

/**
 * Class ProjectConfig
 * @package mmikkel\cacheflag\services
 * @since 1.2.0
 */
class ProjectConfig extends Component
{

    /**
     * @param ConfigEvent $event
     * @throws \yii\db\Exception
     */
    public function onProjectConfigChange(ConfigEvent $event)
    {

        $uid = $event->tokenMatches[0];

        $query = (new Query())
            ->select(['id'])
            ->from(Flags::tableName())
            ->where(['uid' => $uid]);

        $source = \explode(':', $event->newValue['source']);
        $sourceKey = $source[0] ?? null;
        $sourceValue = $source[1] ?? null;

        if (!$sourceKey || !$sourceValue) {
            return;
        }

        switch ($sourceKey) {
            case 'section':
                $column = 'sectionId';
                $value = (int)Db::idByUid(Table::SECTIONS, $sourceValue);
                break;
            case 'categoryGroup':
                $column = 'categoryGroupId';
                $value = (int)Db::idByUid(Table::CATEGORYGROUPS, $sourceValue);
                break;
            case 'tagGroup':
                $column = 'tagGroupId';
                $value = (int)Db::idByUid(Table::TAGGROUPS, $sourceValue);
                break;
            case 'userGroup':
                $column = 'userGroupId';
                $value = (int)Db::idByUid(Table::USERGROUPS, $sourceValue);
                break;
            case 'volume':
                $column = 'volumeId';
                $value = (int)Db::idByUid(Table::VOLUMES, $sourceValue);
                break;
            case 'globalSet':
                $column = 'globalSetId';
                $value = (int)Db::idByUid(Table::GLOBALSETS, $sourceValue);
                break;
            case 'elementType':
                $column = 'elementType';
                $value = $sourceValue;
                break;
            default:
                return;
        }

        $query->orWhere([$column => $value]);

        $id = $query->scalar();

        $isNew = empty($id);

        if ($isNew) {

            $flags = $event->newValue['flags'];

            Craft::$app->db->createCommand()
                ->insert(Flags::tableName(), [
                    'flags' => $flags,
                    $column => $value,
                    'uid' => $uid,
                ])
                ->execute();

        } else {

            Craft::$app->db->createCommand()
                ->update(Flags::tableName(), [
                    'flags' => $event->newValue['flags'],
                    'uid' => $uid,
                ], ['id' => $id])
                ->execute();
        }

    }

    /**
     * @param ConfigEvent $event
     * @throws \yii\db\Exception
     */
    public function onProjectConfigDelete(ConfigEvent $event)
    {

        $uid = $event->tokenMatches[0];

        $id = (new Query())
            ->select(['id'])
            ->from(Flags::tableName())
            ->where(['uid' => $uid])
            ->scalar();

        if (!$id) {
            return;
        }

        Craft::$app->db->createCommand()
            ->delete(Flags::tableName(), ['id' => $id])
            ->execute();

    }

    /**
     * @param RebuildConfigEvent $event
     * @return void
     */
    public function onProjectConfigRebuild(RebuildConfigEvent $event)
    {

        Craft::$app->getProjectConfig()->remove('cacheFlags');

        $rows = (new Query())
            ->select(['flags', 'sectionId', 'categoryGroupId', 'tagGroupId', 'userGroupId', 'volumeId', 'globalSetId', 'elementType', 'uid'])
            ->from(Flags::tableName())
            ->all();

        foreach ($rows as $row) {

            $sourceKey = null;
            $sourceValue = null;

            if ($row['sectionId']) {
                $sourceKey = 'section';
                $sourceValue = Db::uidById(Table::SECTIONS, $row['sectionId']);
            } else if ($row['categoryGroupId']) {
                $sourceKey = 'categoryGroup';
                $sourceValue = Db::uidById(Table::CATEGORYGROUPS, $row['categoryGroupId']);
            } else if ($row['tagGroupId']) {
                $sourceKey = 'tagGroup';
                $sourceValue = Db::uidById(Table::TAGGROUPS, $row['tagGroupId']);
            } else if ($row['userGroupId']) {
                $sourceKey = 'userGroup';
                $sourceValue = Db::uidById(Table::USERGROUPS, $row['userGroupId']);
            } else if ($row['volumeId']) {
                $sourceKey = 'volume';
                $sourceValue = Db::uidById(Table::VOLUMES, $row['volumeId']);
            } else if ($row['globalSetId']) {
                $sourceKey = 'globalSet';
                $sourceValue = Db::uidById(Table::GLOBALSETS, $row['globalSetId']);
            } else if ($row['elementType']) {
                $sourceKey = 'elementType';
                $sourceValue = $row['elementType'];
            }

            if (!$sourceKey || !$sourceValue) {
                return;
            }

            $event->config['cacheFlags'][$row['uid']] = [
                'source' => "$sourceKey:$sourceValue",
                'flags' => $row['flags'],
            ];
        }

    }

}
