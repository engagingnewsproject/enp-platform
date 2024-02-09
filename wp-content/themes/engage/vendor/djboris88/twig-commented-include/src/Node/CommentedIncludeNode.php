<?php
/*
 * Commented Include Twig Extension Component
 *
 * Copyright (C) Boris Đemrovski <djboris88@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Djboris88\Twig\Node;

use Twig\Node\IncludeNode;
use Twig\Node\NodeOutputInterface;
use Twig_Compiler;

/**
 * @author Boris Đemrovski <djboris88@gmail.com>
 */
class CommentedIncludeNode extends IncludeNode implements NodeOutputInterface
{

	/**
	 * @param \Twig_Compiler $compiler
	 */
	public function compile(Twig_Compiler $compiler)
	{
		$compiler->addDebugInfo($this);

		$compiler
			->raw("echo '<!-- Begin output of " )
			->subcompile($this->getNode('expr'))
			->raw(" -->';\n\n")
		;

		if ($this->getAttribute('ignore_missing')) {
			$compiler
				->write("try {\n")
				->indent()
			;
		}

		$this->addGetTemplate($compiler);

		$compiler->raw('->display(');

		$this->addTemplateArguments($compiler);

		$compiler->raw(");\n");

		if ($this->getAttribute('ignore_missing')) {
			$compiler
				->outdent()
				->write("} catch (Twig_Error_Loader \$e) {\n")
				->indent()
				->write("// ignore missing template\n")
				->outdent()
				->write("}\n\n")
			;
		}

		$compiler
			->raw("echo '<!-- / End output of " )
			->subcompile($this->getNode('expr'))
			->raw(" -->';\n\n")
		;
	}
}
