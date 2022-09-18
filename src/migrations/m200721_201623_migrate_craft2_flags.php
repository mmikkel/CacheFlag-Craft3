<?php


namespace mmikkel\cacheflag\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\MatrixBlock;
use craft\elements\Tag;
use craft\elements\User;
use craft\helpers\Db;
use mmikkel\cacheflag\records\Flags;

/**
 * Class m200721_201623_migrate_craft2_flags
 *
 * @package mmikkel\cacheflag\migrations
 * @since 1.2.0
 */
class m200721_201623_migrate_craft2_flags extends Migration
{

    /**
     * @inheritDoc
     */
    public function safeUp()
    {

        if ((bool)Flags::find()->count()) {
            // There's already content in the Craft 3 table, so don't attempt to migrate anything
            return;
        }

        $craft2TableSchema = Craft::$app->db->schema->getTableSchema('{{%templatecaches_flags}}');
        if ($craft2TableSchema === null) {
            return;
        }

        // Get all migratable flags
        $baseQuery = (new Query())
            ->select(['flags', 'sectionId', 'categoryGroupId', 'tagGroupId', 'userGroupId', 'assetSourceId', 'globalSetId', 'elementType', 'flags.dateCreated', 'flags.dateUpdated', 'flags.uid'])
            ->from('{{%templatecaches_flags}} AS flags');

        $rowsToInsert = [];

        $rowsToInsert += (clone $baseQuery)
            ->innerJoin('{{%sections}} AS sections', 'sections.id=sectionId')
            ->all();

        $rowsToInsert += (clone $baseQuery)
            ->innerJoin('{{%categorygroups}} AS categorygroups', 'categorygroups.id=categoryGroupId')
            ->all();

        $rowsToInsert += (clone $baseQuery)
            ->innerJoin('{{%taggroups}} AS taggroups', 'taggroups.id=tagGroupId')
            ->all();

        $rowsToInsert += (clone $baseQuery)
            ->innerJoin('{{%usergroups}} AS usergroups', 'usergroups.id=userGroupId')
            ->all();

        $volumeRows = (clone $baseQuery)
            ->innerJoin('{{%volumes}} AS volumes', 'volumes.id=assetSourceId')
            ->all();

        foreach ($volumeRows as $volumeRow) {
            $volumeRow['volumeId'] = $volumeRow['assetSourceId'];
            unset($volumeRow['assetSourceId']);
            $rowsToInsert[] = $volumeRow;
        }

        $rowsToInsert += (clone $baseQuery)
            ->innerJoin('{{%globalsets}} AS globalsets', 'globalsets.id=globalSetId')
            ->all();

        $elementTypeRows = (clone $baseQuery)
            ->where('elementType IS NOT NULL')
            ->all();

        foreach ($elementTypeRows as $elementTypeRow) {
            $elementType = $elementTypeRow['elementType'];
            switch ($elementType) {
                case 'Entry':
                    $elementType = Entry::class;
                    break;
                case 'Category':
                    $elementType = Category::class;
                    break;
                case 'Tag':
                    $elementType = Tag::class;
                    break;
                case 'User':
                    $elementType = User::class;
                    break;
                case 'Asset':
                    $elementType = Asset::class;
                    break;
                case 'GlobalSet':
                    $elementType = GlobalSet::class;
                    break;
                case 'MatrixBlock':
                    $elementType = MatrixBlock::class;
                    break;
                default:
                    $elementType = null;
            }
            if (!$elementType) {
                continue;
            }
            $elementTypeRow['elementType'] = $elementType;
            $rowsToInsert[] = $elementTypeRow;
        }

        foreach ($rowsToInsert as $rowToInsert) {
            if (empty($rowToInsert['flags'])) {
                continue;
            }
            $this->insert('{{%cacheflag_flags}}', [
                'flags' => $rowToInsert['flags'],
                'sectionId' => $rowToInsert['sectionId'] ?? null,
                'categoryGroupId' => $rowToInsert['categoryGroupId'] ?? null,
                'tagGroupId' => $rowToInsert['tagGroupId'] ?? null,
                'userGroupId' => $rowToInsert['userGroupId'] ?? null,
                'volumeId' => $rowToInsert['volumeId'] ?? null,
                'globalSetId' => $rowToInsert['globalSetId'] ?? null,
                'elementType' => $rowToInsert['elementType'] ?? null,
                'dateCreated' => !empty($rowToInsert['dateCreated']) ? Db::prepareDateForDb($rowToInsert['dateCreated']) : null,
                'uid' => $rowToInsert['uid'] ?? null,
            ]);
        }

        // Delete the Craft 2 table
        $this->dropTableIfExists('{{%templatecaches_flags}}');
    }

    /**
     * @inheritDoc
     */
    public function safeDown()
    {
        echo "m200721_201623_migrate_craft2_flags cannot be reverted.\n";
        return false;
    }

}
