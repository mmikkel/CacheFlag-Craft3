<?php
/**
 * Cache Flag plugin for Craft CMS 3.x
 *
 * Flag and clear template caches.
 *
 * @link      https://vaersaagod.no
 * @copyright Copyright (c) 2018 Mats Mikkel Rummelhoff
 */

namespace mmikkel\cacheflag\records;

use mmikkel\cacheflag\CacheFlag;

use Craft;
use craft\db\ActiveRecord;

/**
 * @author    Mats Mikkel Rummelhoff
 * @package   CacheFlag
 * @since     1.0.0
 */
class Flags extends ActiveRecord
{

    /** @var string|null */
    public $flags;

    /** @var int|null */
    public $sectionId;

    /** @var int|null */
    public $categoryGroupId;

    /** @var int|null */
    public $tagGroupId;

    /** @var int|null */
    public $userGroupId;

    /** @var int|null */
    public $volumeId;

    /** @var int|null */
    public $globalSetId;

    /** @var string|null */
    public $elementType;

    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cacheflag_flags}}';
    }
}
