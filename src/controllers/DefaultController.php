<?php

namespace mmikkel\cacheflag\controllers;

use mmikkel\cacheflag\CacheFlag;

use Craft;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

/**
 * @author    Mats Mikkel Rummelhoff
 * @package   CacheFlag
 * @since     1.0.0
 */
class DefaultController extends Controller
{

    /** @var array|bool|int */
    public array|bool|int $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

    /**
     * @return \yii\web\Response
     * @throws BadRequestHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionSaveFlags(): \yii\web\Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment.');
        }

        $params = Craft::$app->getRequest()->getBodyParams();
        $cacheFlags = $params['cacheflags'] ?? null;

        $error = null;

        foreach ($cacheFlags as $source => $flags) {

            $sourceArray = \explode(':', $source);
            $sourceColumn = $sourceArray[0] ?? null;
            $sourceId = $sourceArray[1] ?? null;

            if (!$sourceColumn || !$sourceId) {
                continue;
            }

            $flags = \preg_replace('/\s+/', '', $flags);

            try {
                if (!$flags) {
                    CacheFlag::getInstance()->cacheFlag->deleteFlagsBySource($sourceColumn, $sourceId);
                    continue;
                }
                CacheFlag::getInstance()->cacheFlag->saveFlags($flags, $sourceColumn, $sourceId);
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }

            if ($error) {
                break;
            }

        }

        if ($error) {
            return $this->asJson([
                'success' => false,
                'message' => $error,
            ]);
        }

        return $this->asJson([
            'success' => true,
            'message' => Craft::t('cache-flag', 'Cache flags saved'),
            'flags' => CacheFlag::getInstance()->cacheFlag->getAllFlags(),
        ]);

    }

    /**
     * @return \yii\web\Response
     * @throws BadRequestHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionInvalidateFlaggedCachesByFlags()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $params = Craft::$app->getRequest()->getBodyParams();
        $flags = $params['flags'] ?? null;

        if (!$flags) {
            return $this->asJson([
                'success' => false,
                'message' => Craft::t('cache-flag', 'No flags to invalidate caches for'),
            ]);
        }

        $error = null;

        try {
            CacheFlag::getInstance()->cacheFlag->invalidateFlaggedCachesByFlags($flags);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        if ($error) {
            return $this->asJson([
                'success' => false,
                'message' => $error,
            ]);
        }

        return $this->asJson([
            'success' => true,
            'message' => Craft::t('cache-flag', 'Flagged caches invalidated'),
        ]);
    }

    /**
     * @return \yii\web\Response
     * @throws BadRequestHttpException
     * @throws \craft\errors\MissingComponentException
     */
    public function actionInvalidateAllFlaggedCaches()
    {
        $this->requirePostRequest();

        $error = null;

        try {
            CacheFlag::getInstance()->cacheFlag->invalidateAllFlaggedCaches();
        } catch (\Throwable $e) {
            $error = $e;
        }

        if ($error) {
            Craft::$app->getSession()->setError($error);
        } else {
            Craft::$app->getSession()->setNotice(Craft::t('cache-flag', 'All flagged caches invalidated'));
        }

        return $this->redirectToPostedUrl();
    }

}
