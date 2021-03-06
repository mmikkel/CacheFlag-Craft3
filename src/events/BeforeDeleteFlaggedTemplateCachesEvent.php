<?php
/**
 * Created by PhpStorm.
 * User: mmikkel
 * Date: 16/07/2018
 * Time: 01:59
 */

namespace mmikkel\cacheflag\events;

/**
 * Class BeforeDeleteFlaggedTemplateCachesEvent
 * @package mmikkel\cacheflag\events
 * @deprecated since 1.1.0
 */
class BeforeDeleteFlaggedTemplateCachesEvent extends \yii\base\Event
{

    // Properties
    // =========================================================================
    /**
     * @var int[] Array of template cache IDs that are associated with this event
     */
    public $cacheIds;
    /**
     * @var string[] Array of Cache Flag flags that are associated with this event
     */
    public $flags;
}
