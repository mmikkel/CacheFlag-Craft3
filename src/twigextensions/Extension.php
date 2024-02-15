<?php

namespace mmikkel\cacheflag\twigextensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

use Craft;

/**
 * @author    Mats Mikkel Rummelhoff
 * @package   CacheFlag
 * @since     1.0.0
 */
class Extension extends AbstractExtension
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'CacheFlag';
    }

    /**
     * @inheritdoc
     */
    public function getFilters(): array
    {
        return \array_filter([
            Craft::$app->getRequest()->getIsCpRequest() ? new TwigFilter('cacheFlagUnCamelCase', [$this, 'cacheFlagUnCamelCaseFilter']) : null,
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
    public function cacheFlagUnCamelCaseFilter(?string $value = null): string
    {
        if (empty($value)) {
            return '';
        }
        if (preg_match('/[A-Z]/', $value) === 0) {
            return $value;
        }
        $pattern = '/([a-z])([A-Z])/';
        $r = strtolower(preg_replace_callback($pattern, function ($a) {
            return $a[1] . ' ' . strtolower($a[2]);
        }, $value));
        return $r;
    }
}
