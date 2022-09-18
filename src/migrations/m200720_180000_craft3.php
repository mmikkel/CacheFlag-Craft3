<?php

namespace mmikkel\cacheflag\migrations;

use yii\db\Migration;

class m200720_180000_craft3 extends Migration
{
    public function safeUp()
    {
        $tableSchema = \Craft::$app->db->schema->getTableSchema('{{%cacheflag_flags}}');
        if ($tableSchema === null) {
            $installMigration = new Install();
            return $installMigration->safeUp();
        }
        return true;
    }
}
