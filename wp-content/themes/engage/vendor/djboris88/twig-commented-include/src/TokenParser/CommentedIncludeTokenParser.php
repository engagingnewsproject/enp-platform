<?php
/*
 * Commented Include Twig Extension Component
 *
 * Copyright (C) Boris Đemrovski <djboris88@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Djboris88\Twig\TokenParser;

use Djboris88\Twig\Node\CommentedIncludeNode;
use Twig\TokenParser\IncludeTokenParser;
use Twig_Token;

/**
 * @author Boris Đemrovski <djboris88@gmail.com>
 */
class CommentedIncludeTokenParser extends IncludeTokenParser
{
	/**
	 * @param \Twig_Token $token
	 *
	 * @return \Djboris88\Twig\Node\CommentedIncludeNode
	 */
	public function parse(Twig_Token $token)
	{
		$expr = $this->parser->getExpressionParser()->parseExpression();

		list($variables, $only, $ignoreMissing) = $this->parseArguments();

		return new CommentedIncludeNode($expr, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
	}
}
