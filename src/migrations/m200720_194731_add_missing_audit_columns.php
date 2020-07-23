<?php

namespace mmikkel\cacheflag\migrations;

use Craft;
use craft\db\Migration;

use craft\helpers\Db;
use craft\helpers\StringHelper;

use mmikkel\cacheflag\records\Flags;

/**
 * Class m200721_194731_add_missing_audit_columns
 * @package mmikkel\cacheflag\migrations
 * @since 1.2.0
 */
class m200720_194731_add_missing_audit_columns extends Migration
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

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%cacheflag_flags}}');

        // Make sure our audit columns exist
        if (!isset($tableSchema->columns['dateCreated'])) {
            $this->addColumn('{{%cacheflag_flags}}', 'dateCreated', $this->dateTime()->notNull());
        }
        if (!isset($tableSchema->columns['dateUpdated'])) {
            $this->addColumn('{{%cacheflag_flags}}', 'dateUpdated', $this->dateTime()->notNull());
        }
        if (!isset($tableSchema->columns['uid'])) {
            $this->addColumn('{{%cacheflag_flags}}', 'uid', $this->uid());
        }

        // Add missing UIDs to all rows in the flags table
        $rows = Flags::find()
            ->all();
        foreach ($rows as $row) {
            /** @var Flags $row */
            $row->dateCreated = $row->dateCreated ?? Db::prepareDateForDb(time());
            $row->uid = Db::uidById('{{%cacheflag_flags}}', (int)$row->id) ?? StringHelper::UUID();
            $row->save();
        }

    }

    /**
     * @inheritDoc
     */
    public function safeDown()
    {
        echo "m200720_194731_add_missing_audit_columns cannot be reverted.\n";
        return false;
    }

}
