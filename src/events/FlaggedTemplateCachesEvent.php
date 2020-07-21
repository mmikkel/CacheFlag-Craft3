<?php

namespace mmikkel\cacheflag\events;

use yii\base\Event;

/**
 * Class FlaggedTemplateCachesEvent
 * @package mmikkel\cacheflag\events
 */
class FlaggedTemplateCachesEvent extends Event
{
    /**
     * @var string[] Array of Cache Flag flags that are associated with this event
     */
    public $flags;
}
