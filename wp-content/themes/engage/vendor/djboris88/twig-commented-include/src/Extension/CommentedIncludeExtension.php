<?php
/*
 * Commented Include Twig Extension Component
 *
 * Copyright (C) Boris Đemrovski <djboris88@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Djboris88\Twig\Extension;

use Djboris88\Twig\TokenParser\CommentedIncludeTokenParser;
use Twig_Extension;

/**
 * @author Boris Đemrovski <djboris88@gmail.com>
 */
class CommentedIncludeExtension extends Twig_Extension
{

	/**
	 * @return string
	 */
	public function getName() {
		return 'commented-input';
	}

	/**
	 * @return array|\Twig_TokenParserInterface[]
	 */
	public function getTokenParsers()
	{
		return array(new CommentedIncludeTokenParser());
	}
}
