<?php

namespace mmikkel\cacheflag\controllers;

use Craft;
use craft\web\Controller;

use mmikkel\cacheflag\CacheFlag;

/**
 * Class CachesController
 * @package mmikkel\cacheflag\controllers
 * @since 1.2.0
 */
class CachesController extends Controller
{

    /** @var array|bool|int */
    public array|bool|int $allowAnonymous = self::ALLOW_ANONYMOUS_OFFLINE;

    /**
     * Invalidate all flagged caches, or caches flagged with one or several particular flags
     *
     * @return bool
     */
    public function actionInvalidate(): bool
    {
        $flags = Craft::$app->getRequest()->getParam('flags', []);

        if (\is_string($flags)) {
            $flags = \preg_replace('/\s+/', '', $flags);
            $flags = \array_filter(\explode(',', $flags));
        } else if (\is_array($flags)) {
            $flags = \array_reduce($flags, function (array $carry, string $flag) {
                $flag = \preg_replace('/\s+/', '', $flag);
                if (\strlen($flag)) {
                    $carry[] = $flag;
                }
                return $carry;
            }, []);
        }

        /** @var array $flags */
        if (empty($flags)) {
            CacheFlag::getInstance()->cacheFlag->invalidateAllFlaggedCaches();
            return true;
        }

        $flags = \array_unique($flags);

        CacheFlag::getInstance()->cacheFlag->invalidateFlaggedCachesByFlags($flags);
        return true;
    }
}
