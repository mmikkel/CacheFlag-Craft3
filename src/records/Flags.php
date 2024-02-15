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

use craft\db\ActiveRecord;

/**
 * @property int $id
 *
 * @author    Mats Mikkel Rummelhoff
 * @package   CacheFlag
 * @since     1.0.0
 */
class Flags extends ActiveRecord
{

    /** @var string|null */
    public ?string $flags;

    /** @var int|null */
    public ?int $sectionId;

    /** @var int|null */
    public ?int $categoryGroupId;

    /** @var int|null */
    public ?int $tagGroupId;

    /** @var int|null */
    public ?int $userGroupId;

    /** @var int|null */
    public ?int $volumeId;

    /** @var int|null */
    public ?int $globalSetId;

    /** @var string|null */
    public ?string $elementType;

    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%cacheflag_flags}}';
    }
}
