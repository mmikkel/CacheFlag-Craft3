<?php

namespace mmikkel\cacheflag\migrations;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

/**
 * m190330_192323_siteId migration.
 */
class m190330_192323_siteId extends Migration
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

        if ($this->alterTables()) {
            $this->createIndexes();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190330_192323_siteId cannot be reverted.\n";

        return false;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return bool
     */
    protected function alterTables()
    {
        $this->addColumn('{{%cacheflag_flagged}}', 'siteId', $this->integer()->notNull());

        return true;
    }

    /**
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(
            $this->db->getIndexName(
                '{{%cacheflag_flagged}}',
                'siteId',
                false
            ),
            '{{%cacheflag_flagged}}',
            'siteId',
            false
        );
        
        // Additional commands depending on the db driver
        switch ($this->driver) {
            case DbConfig::DRIVER_MYSQL:
                break;
            case DbConfig::DRIVER_PGSQL:
                break;
        }
    }
}
