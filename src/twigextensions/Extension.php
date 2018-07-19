<?php
/**
 * Cache Flag plugin for Craft CMS 3.x
 *
 * Flag and clear template caches.
 *
 * @link      https://vaersaagod.no
 * @copyright Copyright (c) 2018 Mats Mikkel Rummelhoff
 */

namespace mmikkel\cacheflag\twigextensions;

use mmikkel\cacheflag\CacheFlag;

use Craft;

/**
 * @author    Mats Mikkel Rummelhoff
 * @package   CacheFlag
 * @since     1.0.0
 */
class Extension extends \Twig_Extension
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'CacheFlag';
    }

    /**
     * @inheritdoc
     */
    public function getFilters()
    {
        return \array_filter([
            Craft::$app->getRequest()->getIsCpRequest() ? new \Twig_SimpleFilter('cacheFlagUnCamelCase', [$this, 'cacheFlagUnCamelCaseFilter']) : null,
        ]);
    }

    /**
     * @return array
     */
    public function getTokenParsers(): array
    {
        return [
            new CacheFlagTokenParser(),
        ];
    }

    /**
     * @param string|null $value
     *
     * @return string
     */
    public function cacheFlagUnCamelCaseFilter($value = null): string
    {
        if (!$value) {
            return '';
        }
        if (\preg_match('/[A-Z]/', $value) === 0) {
            return $value;
        }
        $pattern = '/([a-z])([A-Z])/';
        $r = \strtolower(\preg_replace_callback($pattern, function ($a) {
            return $a[1] . ' ' . \strtolower($a[2]);
        }, $value));
        return $r;
    }
}
