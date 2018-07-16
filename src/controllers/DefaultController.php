<?php
/**
 * Cache Flag plugin for Craft CMS 3.x
 *
 * Flag and clear template caches.
 *
 * @link      https://vaersaagod.no
 * @copyright Copyright (c) 2018 Mats Mikkel Rummelhoff
 */

namespace mmikkel\cacheflag\controllers;

use mmikkel\cacheflag\CacheFlag;

use Craft;
use craft\web\Controller;
use yii\web\BadRequestHttpException;

/**
 * @author    Mats Mikkel Rummelhoff
 * @package   CacheFlag
 * @since     1.0.0
 */
class DefaultController extends Controller
{

    // Public Methods
    // =========================================================================

    /**
     * @return \yii\web\Response
     * @throws BadRequestHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionSaveFlags()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

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
                    CacheFlag::$plugin->cacheFlag->deleteFlagsBySource($sourceColumn, $sourceId);
                    continue;
                }
                CacheFlag::$plugin->cacheFlag->saveFlags($flags, $sourceColumn, $sourceId);
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
            'message' => $this->_getRandomSuccessMessage(),
            'flags' => CacheFlag::$plugin->cacheFlag->getAllFlags(),
        ]);

    }

    /**
     * @return \yii\web\Response
     * @throws BadRequestHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionDeleteFlaggedCachesByFlags()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $params = Craft::$app->getRequest()->getBodyParams();
        $flags = $params['flags'] ?? null;

        if (!$flags) {
            return $this->asJson([
                'success' => false,
                'message' => Craft::t('cache-flag', 'No flags to clear'),
            ]);
        }

        $error = null;

        try {
            CacheFlag::$plugin->cacheFlag->deleteFlaggedCachesByFlags($flags);
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
            'message' => Craft::t('cache-flag', 'Caches cleared'),
        ]);

    }

    /**
     * @return \yii\web\Response
     * @throws BadRequestHttpException
     */
    public function actionDeleteAllFlaggedCaches()
    {
        $this->requirePostRequest();

        $error = null;

        try {
            CacheFlag::$plugin->cacheFlag->deleteAllFlaggedCaches();
        } catch (\Throwable $e) {
            $error = $e;
        }

        if ($error) {
            Craft::$app->getSession()->setError($error);
        } else {
            Craft::$app->getSession()->setNotice(Craft::t('cache-flag', 'All flagged caches cleared'));
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * @return string
     */
    private function _getRandomSuccessMessage(): string
    {
        $messages = [
            "Flags saved! Good day to you.",
            "Ahoy! All flags saved.",
            "Flags. Saved. High five.",
            "Flags! We haz them.",
            "Cache flags saved, friend.",
            "Yarr! Yer flags be safe.",
            "Dun dun dun. All flags saved.",
            "You are awesome. And so are your flags.",
            "Flags, so many flags. All mine!",
            "Flags flags flags flags",
            "You say save, I say sure.",
            "Before, your flags were lost. But now they are saved.",
            "My, what beautiful flags you have.",
            "Your flags are safe with me.",
            "I'll just hang on to these flags for you.",
            "If you want a symbolic gesture, don't burn the flag; save it.",
            'Those are the second biggest flags I\'ve ever seen!',
            'Put a flag on it.',
            'No worries, your flags are totally saved.',
            'I just can\'t believe Cache Flag is finally running on Craft 3',
            'Current state: flags saved.',
            'Mhmm, yummy flags been saved.',
            'Much flags. So saved.',
            'Your Cache Flags (tm) are safely saved.',
            'Flags saved! Go forth and conquer!',
            'Saving flags is my favorite thing in the world',
            'Safe and sound.',
            'It\'s getting hot in here',
            'Flags. Saved. Profit.',
            'These might just be the prettiest lil\' flags I\'ve ever saved',
            'Saving cache flags sure is fun, huh',
        ];
        return $messages[\array_rand($messages)];
    }

}
