<?php

namespace mmikkel\cacheflag\migrations;

use craft\db\Migration;

/**
 * Class m220620_191334_delete_cacheflag_flagged_database_table
 * @package mmikkel\cacheflag\migrations
 * @since 1.3.1
 */
class m220620_191334_delete_cacheflag_flagged_database_table extends Migration
{

    /** @inheritdoc */
    public function safeUp()
    {
        // Remove the old "cacheflag_flagged" table if it exists
        $this->dropTableIfExists('{{%cacheflag_flagged}}');
    }

    /** @inheritdoc */
    public function safeDown()
    {
        echo "m220620_191334_delete_cacheflag_flagged_database_table cannot be reverted.\n";
        return false;
    }

}
