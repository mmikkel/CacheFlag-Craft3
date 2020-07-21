<?php
/**
 * Created by PhpStorm.
 * User: mmikkel
 * Date: 14/07/2018
 * Time: 15:44
 */

namespace mmikkel\cacheflag\twigextensions;

use mmikkel\cacheflag\CacheFlag;

use Craft;
use craft\helpers\StringHelper;

use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class CacheFlagNode
 * @package mmikkel\cacheflag\twigextensions
 */
class CacheFlagNode extends Node
{

    // Properties
    // =========================================================================

    /**
     * @var int
     */
    private static $_cacheCount = 1;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler)
    {
        $n = self::$_cacheCount++;

        $flags = $this->hasNode('flags') ? $this->getNode('flags') : null;

        $conditions = $this->hasNode('conditions') ? $this->getNode('conditions') : null;
        $ignoreConditions = $this->hasNode('ignoreConditions') ? $this->getNode('ignoreConditions') : null;
        $key = $this->hasNode('key') ? $this->getNode('key') : null;
        $expiration = $this->hasNode('expiration') ? $this->getNode('expiration') : null;

        $durationNum = $this->getAttribute('durationNum');
        $durationUnit = $this->getAttribute('durationUnit');
        $global = $this->getAttribute('global') ? 'true' : 'false';
        $elements = $this->getAttribute('elements') ? 'true' : 'false';

        $compiler
            ->addDebugInfo($this)
            ->write('$cacheService = ' . CacheFlag::class . "::getInstance()->templateCaches;\n")
            ->write('$request = ' . Craft::class . "::\$app->getRequest();\n")
            ->write("\$ignoreCache{$n} = (\$request->getIsLivePreview() || \$request->getToken()");

        if ($conditions) {
            $compiler
                ->raw(' || !(')
                ->subcompile($conditions)
                ->raw(')');
        } else if ($ignoreConditions) {
            $compiler
                ->raw(' || (')
                ->subcompile($ignoreConditions)
                ->raw(')');
        }

        $compiler
            ->raw(");\n")
            ->write("if (!\$ignoreCache{$n}) {\n")
            ->indent()
            ->write("\$cacheKey{$n} = ");

        if ($key) {
            $compiler->subcompile($key);
        } else {
            $compiler->raw('"' . StringHelper::randomString() . '"');
        }

        $compiler
            ->raw(";\n")
            ->write("\$cacheBody{$n} = \$cacheService->getTemplateCache(\$cacheKey{$n}, ");

        if ($flags) {
            $compiler->subcompile($flags);
        } else {
            $compiler->raw('null');
        }

        $compiler
            ->raw(", {$elements}, {$global});\n")
            ->outdent()
            ->write("} else {\n")
            ->indent()
            ->write("\$cacheBody{$n} = null;\n")
            ->outdent()
            ->write("}\n")
            ->write("if (\$cacheBody{$n} === null) {\n")
            ->indent()
            ->write("if (!\$ignoreCache{$n}) {\n")
            ->indent()
            ->write("\$cacheService->startTemplateCache(\$cacheKey{$n});\n")
            ->outdent()
            ->write("}\n")
            ->write("ob_start();\n")
            ->subcompile($this->getNode('body'))
            ->write("\$cacheBody{$n} = ob_get_clean();\n")
            ->write("if (!\$ignoreCache{$n}) {\n")
            ->indent()
            ->write("\$cacheService->endTemplateCache(\$cacheKey{$n}, ");

        if ($flags) {
            $compiler->subcompile($flags);
        } else {
            $compiler->raw('null');
        }

        $compiler->raw(", {$global}, ");

        if ($durationNum) {
            // So silly that PHP doesn't support "+1 week" http://www.php.net/manual/en/datetime.formats.relative.php

            if ($durationUnit === 'week') {
                if ($durationNum == 1) {
                    $durationNum = 7;
                    $durationUnit = 'days';
                } else {
                    $durationUnit = 'weeks';
                }
            }

            $compiler->raw("'+{$durationNum} {$durationUnit}'");
        } else {
            $compiler->raw('null');
        }

        $compiler->raw(', ');

        if ($expiration) {
            $compiler->subcompile($expiration);
        } else {
            $compiler->raw('null');
        }

        $compiler
            ->raw(", \$cacheBody{$n});\n")
            ->outdent()
            ->write("}\n")
            ->outdent()
            ->write("}\n")
            ->write("echo \$cacheBody{$n};\n");
    }

}
