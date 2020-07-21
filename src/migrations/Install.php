<?php
/**
 * Cache Flag plugin for Craft CMS 3.x
 *
 * Flag and clear template caches.
 *
 * @link      https://vaersaagod.no
 * @copyright Copyright (c) 2018 Mats Mikkel Rummelhoff
 */

namespace mmikkel\cacheflag\migrations;

use mmikkel\cacheflag\CacheFlag;

use Craft;
use craft\db\Migration;

/**
 * @author    Mats Mikkel Rummelhoff
 * @package   CacheFlag
 * @since     1.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

   /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%cacheflag_flags}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%cacheflag_flags}}',
                [
                    'id' => $this->primaryKey(),
                    'flags' => $this->string(255)->notNull(),
                    'sectionId' => $this->integer()->unique(),
                    'categoryGroupId' => $this->integer()->unique(),
                    'tagGroupId' => $this->integer()->unique(),
                    'userGroupId' => $this->integer()->unique(),
                    'volumeId' => $this->integer()->unique(),
                    'globalSetId' => $this->integer()->unique(),
                    'elementType' => $this->string(255)->unique(),
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(
            $this->db->getIndexName(
                '{{%cacheflag_flags}}',
                'flags',
                false
            ),
            '{{%cacheflag_flags}}',
            'flags',
            false
        );
    }

    /**
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%cacheflag_flags}}', 'sectionId'),
            '{{%cacheflag_flags}}',
            'sectionId',
            '{{%sections}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%cacheflag_flags}}', 'categoryGroupId'),
            '{{%cacheflag_flags}}',
            'categoryGroupId',
            '{{%categorygroups}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%cacheflag_flags}}', 'tagGroupId'),
            '{{%cacheflag_flags}}',
            'tagGroupId',
            '{{%taggroups}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%cacheflag_flags}}', 'userGroupId'),
            '{{%cacheflag_flags}}',
            'userGroupId',
            '{{%usergroups}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%cacheflag_flags}}', 'volumeId'),
            '{{%cacheflag_flags}}',
            'volumeId',
            '{{%volumes}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%cacheflag_flags}}', 'globalSetId'),
            '{{%cacheflag_flags}}',
            'globalSetId',
            '{{%globalsets}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * @return void
     */
    protected function insertDefaultData()
    {
    }

    /**
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists('{{%cacheflag_flagged}}');

        $this->dropTableIfExists('{{%cacheflag_flags}}');
    }
}
