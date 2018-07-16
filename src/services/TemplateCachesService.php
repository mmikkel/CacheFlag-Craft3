<?php
/**
 * Created by PhpStorm.
 * User: mmikkel
 * Date: 14/07/2018
 * Time: 11:55
 */

namespace mmikkel\cacheflag\services;

use mmikkel\cacheflag\CacheFlag;
use mmikkel\cacheflag\records\Flagged;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\events\DeleteTemplateCachesEvent;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\services\TemplateCaches;
use craft\queue\jobs\DeleteStaleTemplateCaches;
use DateTime;
use yii\base\Component;
use yii\base\Event;
use yii\web\Response;

class TemplateCachesService extends Component
{

    // Properties
    // =========================================================================
    /**
     * The table that template caches are stored in.
     *
     * @var string
     */
    private static $_templateCachesTable = '{{%templatecaches}}';

    /**
     * The current request's path, as it will be stored in the templatecaches table.
     *
     * @var string|null
     */
    private $_path;

    // Public Methods
    // =========================================================================
    /**
     * Returns a cached template by its key.
     *
     * @param string $key The template cache key
     * @param mixed|null $flags The Cache Flag flags this cache would've been flagged with
     * @param bool $global Whether the cache would have been stored globally.
     * @return string|null
     */
    public function getTemplateCache(string $key, $flags = null, bool $global)
    {
        // Make sure template caching is enabled
        if ($this->_isTemplateCachingEnabled() === false) {
            return null;
        }
        // Don't return anything if it's not a global request and the path > 255 characters.
        if (!$global && strlen($this->_getPath()) > 255) {
            return null;
        }
        // Take the opportunity to delete any expired caches
        Craft::$app->getTemplateCaches()->deleteExpiredCachesIfOverdue();
        /** @noinspection PhpUnhandledExceptionInspection */
        $query = (new Query())
            ->select(['body'])
            ->from([self::$_templateCachesTable . ' templatecaches'])
            ->where([
                'and',
                [
                    'cacheKey' => $key,
                    'siteId' => Craft::$app->getSites()->getCurrentSite()->id
                ],
                ['>', 'expiryDate', Db::prepareDateForDb(new \DateTime())],
            ]);
        if (!$global) {
            $query->andWhere([
                'path' => $this->_getPath()
            ]);
        }
        if ($flags) {
            
            // Sanitize flags
            if (\is_array($flags)) {
                $flags = \implode(',', \array_map(function ($flag) {
                    return \preg_replace('/\s+/', '', $flag);
                }, $flags));
            } else {
                $flags = \preg_replace('/\s+/', '', $flags);
            }

            $flags = \implode(',', \explode('|', $flags));

            $query
                ->innerJoin(
                    Flagged::tableName() . ' flagged',
                    '[[flagged.cacheId]] = [[templatecaches.id]]'
                )
                ->andWhere(['flagged.flags' => $flags]);
        }
        $cachedBody = $query->scalar();
        if ($cachedBody === false) {
            return null;
        }
        return $cachedBody;
    }

    /**
     * Starts a new template cache.
     *
     * @param string $key The template cache key.
     */
    public function startTemplateCache(string $key)
    {
        return;
    }

    /**
     * Ends a template cache.
     *
     * @param string $key The template cache key.
     * @param mixed|null $flags The Cache Flag flags this cache should be flagged with.
     * @param bool $global Whether the cache should be stored globally.
     * @param string|null $duration How long the cache should be stored for. Should be a [relative time format](http://php.net/manual/en/datetime.formats.relative.php).
     * @param mixed|null $expiration When the cache should expire.
     * @param string $body The contents of the cache.
     * @throws \Throwable
     */
    public function endTemplateCache(string $key, $flags = null, bool $global, string $duration = null, $expiration, string $body)
    {
        // Make sure template caching is enabled
        if ($this->_isTemplateCachingEnabled() === false) {
            return;
        }

        // If there are any transform generation URLs in the body, don't cache it.
        // stripslashes($body) in case the URL has been JS-encoded or something.
        if (StringHelper::contains(stripslashes($body), 'assets/generate-transform')) {
            return;
        }

        if (!$global && (strlen($path = $this->_getPath()) > 255)) {
            Craft::warning('Skipped adding ' . $key . ' to template cache table because the path is > 255 characters: ' . $path, __METHOD__);

            return;
        }

        if (Craft::$app->getDb()->getIsMysql()) {
            // Encode any 4-byte UTF-8 characters
            $body = StringHelper::encodeMb4($body);
        }

        // Figure out the expiration date
        if ($duration !== null) {
            $expiration = new DateTime($duration);
        }

        if (!$expiration) {
            $cacheDuration = Craft::$app->getConfig()->getGeneral()->cacheDuration;

            if ($cacheDuration <= 0) {
                $cacheDuration = 31536000; // 1 year
            }

            $cacheDuration += time();

            $expiration = new DateTime('@' . $cacheDuration);
        }

        // Save it
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            Craft::$app->getDb()->createCommand()
                ->insert(
                    self::$_templateCachesTable,
                    [
                        'cacheKey' => $key,
                        'siteId' => Craft::$app->getSites()->getCurrentSite()->id,
                        'path' => $global ? null : $this->_getPath(),
                        'expiryDate' => Db::prepareDateForDb($expiration),
                        'body' => $body
                    ],
                    false)
                ->execute();

            if ($flags) {

                $cacheId = Craft::$app->getDb()->getLastInsertID(self::$_templateCachesTable);

                // Sanitize flags
                if (\is_array($flags)) {
                    $flags = \implode(',', \array_map(function ($flag) {
                        return \preg_replace('/\s+/', '', $flag);
                    }, $flags));
                } else {
                    $flags = \preg_replace('/\s+/', '', $flags);
                }

                $flags = \implode(',', \explode('|', $flags));

                Craft::$app->getDb()->createCommand()
                    ->insert(
                        Flagged::tableName(),
                        [
                            'cacheId' => $cacheId,
                            'flags' => $flags
                        ],
                        false
                    )
                    ->execute();
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    // Private Methods
    // =========================================================================
    /**
     * Returns whether template caching is enabled, based on the 'enableTemplateCaching' config setting.
     *
     * @return bool Whether template caching is enabled
     */
    private function _isTemplateCachingEnabled(): bool
    {
        return !!Craft::$app->getConfig()->getGeneral()->enableTemplateCaching;
    }

    /**
     * Returns the current request path, including a "site:" or "cp:" prefix.
     *
     * @return string
     */
    private function _getPath(): string
    {
        if ($this->_path !== null) {
            return $this->_path;
        }
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_path = 'cp:';
        } else {
            $this->_path = 'site:';
        }
        $this->_path .= Craft::$app->getRequest()->getPathInfo();
        if (($pageNum = Craft::$app->getRequest()->getPageNum()) != 1) {
            $this->_path .= '/' . Craft::$app->getConfig()->getGeneral()->pageTrigger . $pageNum;
        }
        return $this->_path;
    }

}
