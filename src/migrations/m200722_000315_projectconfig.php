<?php


namespace mmikkel\cacheflag\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;

/**
 * Class m200722_000315_projectconfig
 * @package mmikkel\cacheflag\migrations
 * @since 1.1.1
 */
class m200722_000315_projectconfig extends Migration
{

    /**
     * @inheritDoc
     */
    public function safeUp()
    {
        // Don't make the same config changes twice
        $schemaVersion = Craft::$app->projectConfig
            ->get('plugins.cache-flag.schemaVersion', true);

        if (\version_compare($schemaVersion, '1.0.1', '>=')) {
            return;
        }

        $rows = (new Query())
            ->select(['flags', 'sectionId', 'categoryGroupId', 'tagGroupId', 'userGroupId', 'volumeId', 'globalSetId', 'elementType', 'uid'])
            ->from('{{%cacheflag_flags}}')
            ->all();

        foreach ($rows as $row) {

            $source = null;

            if ($row['sectionId']) {
                if ($uid = Db::uidById(Table::SECTIONS, $row['sectionId'])) {
                    $source = "section:$uid";
                }
            } else if ($row['categoryGroupId']) {
                if ($uid = Db::uidById(Table::CATEGORYGROUPS, $row['categoryGroupId'])) {
                    $source = "categoryGroup:$uid";
                }
            } else if ($row['tagGroupId']) {
                if ($uid = Db::uidById(Table::TAGGROUPS, $row['tagGroupId'])) {
                    $source = "tagGroup:$uid";
                }
            } else if ($row['userGroupId']) {
                if ($uid = Db::uidById(Table::USERGROUPS, $row['userGroupId'])) {
                    $source = "userGroup:$uid";
                }
            } else if ($row['volumeId']) {
                if ($uid = Db::uidById(Table::VOLUMES, $row['volumeId'])) {
                    $source = "volume:$uid";
                }
            } else if ($row['globalSetId']) {
                if ($uid = Db::uidById(Table::GLOBALSETS, $row['globalSetId'])) {
                    $source = "globalSet:$uid";
                }
            } else if ($row['elementType']) {
                $source = 'elementType:' . $row['elementType'];
            }

            if (!$source) {
                continue;
            }

            $path = "cacheFlags.{$row['uid']}";

            Craft::$app->projectConfig->set($path, [
                'source' => $source,
                'flags' => $row['flags'],
            ]);
        }

    }

    /**
     * @inheritDoc
     */
    public function safeDown()
    {
        echo "m200722_000315_projectconfig cannot be reverted.\n";
        return false;
    }

}
