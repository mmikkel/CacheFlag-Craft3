<?php

namespace mmikkel\cacheflag\console\controllers;

use craft\console\Controller;

use yii\console\ExitCode;

use mmikkel\cacheflag\CacheFlag;

/**
 * Class CachesController
 * @package mmikkel\cacheflag\console\controllers
 * @since 1.2.0
 */
class CachesController extends Controller
{

    /**
     * Invalidate all flagged caches, or caches flagged with one or several particular flags
     *
     * @param string $flags
     * @return int
     */
    public function actionInvalidate(string $flags = '')
    {

        $flags = preg_replace('/\s+/', '', $flags);
        $flags = array_unique(array_filter(explode(',', $flags)));

        if (empty($flags)) {
            CacheFlag::getInstance()->cacheFlag->invalidateAllFlaggedCaches();
            return ExitCode::OK;
        }

        CacheFlag::getInstance()->cacheFlag->invalidateFlaggedCachesByFlags($flags);

        return ExitCode::OK;

    }

}
