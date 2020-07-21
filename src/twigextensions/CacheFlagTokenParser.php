<?php
/**
 * Created by PhpStorm.
 * User: mmikkel
 * Date: 14/07/2018
 * Time: 15:43
 */

namespace mmikkel\cacheflag\twigextensions;

use mmikkel\cacheflag\twigextensions\CacheFlagNode;

use Craft;

use Twig\Parser;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class CacheFlagTokenParser
 * @package mmikkel\cacheflag\twigextensions
 */
class CacheFlagTokenParser extends AbstractTokenParser
{
    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public function getTag(): string
    {
        return 'cacheflag';
    }

    /**
     * @inheritdoc
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        /** @var Parser $parser */
        $parser = $this->parser;
        $stream = $parser->getStream();

        $nodes = [];

        $attributes = [
            'global' => false,
            'durationNum' => null,
            'durationUnit' => null,
            'elements' => false,
        ];

        if ($stream->test(Token::NAME_TYPE, 'flagged')) {
            $stream->next();
            $nodes['flags'] = $parser->getExpressionParser()->parseExpression();
        }

        if ($stream->test(Token::NAME_TYPE, 'with')) {
            $stream->next();
            $stream->expect(Token::NAME_TYPE, 'elements');
            $attributes['elements'] = true;
        }

        if ($stream->test(Token::NAME_TYPE, 'globally')) {
            $attributes['global'] = true;
            $stream->next();
        }

        if ($stream->test(Token::NAME_TYPE, 'using')) {
            $stream->next();
            $stream->expect(Token::NAME_TYPE, 'key');
            $nodes['key'] = $parser->getExpressionParser()->parseExpression();
        }

        if ($stream->test(Token::NAME_TYPE, 'for')) {
            $stream->next();
            $attributes['durationNum'] = $stream->expect(Token::NUMBER_TYPE)->getValue();
            $attributes['durationUnit'] = $stream->expect(Token::NAME_TYPE,
                [
                    'sec',
                    'secs',
                    'second',
                    'seconds',
                    'min',
                    'mins',
                    'minute',
                    'minutes',
                    'hour',
                    'hours',
                    'day',
                    'days',
                    'fortnight',
                    'fortnights',
                    'forthnight',
                    'forthnights',
                    'month',
                    'months',
                    'year',
                    'years',
                    'week',
                    'weeks'
                ])->getValue();
        } else if ($stream->test(Token::NAME_TYPE, 'until')) {
            $stream->next();
            $nodes['expiration'] = $parser->getExpressionParser()->parseExpression();
        }

        if ($stream->test(Token::NAME_TYPE, 'if')) {
            $stream->next();
            $nodes['conditions'] = $parser->getExpressionParser()->parseExpression();
        } else if ($stream->test(Token::NAME_TYPE, 'unless')) {
            $stream->next();
            $nodes['ignoreConditions'] = $parser->getExpressionParser()->parseExpression();
        }

        $stream->expect(Token::BLOCK_END_TYPE);
        $nodes['body'] = $parser->subparse([
            $this,
            'decideCacheEnd'
        ], true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new CacheFlagNode($nodes, $attributes, $lineno, $this->getTag());
    }

    /**
     * @param Token $token
     * @return bool
     */
    public function decideCacheEnd(Token $token): bool
    {
        return $token->test('endcacheflag');
    }
}
