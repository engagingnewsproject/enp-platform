<?php
/*
 * Commented Include Twig Extension Component
 *
 * Copyright (C) Boris Đemrovski <djboris88@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Djboris88\Twig\Extension\SymfonyBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Boris Đemrovski <djboris88@gmail.com>
 */
class CommentedIncludeTwigExtension extends Extension
{
	/**
	 * @param array $config
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 *
	 * @throws \Exception
	 */
    public function load(array $config, ContainerBuilder $container)
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('twig.xml');
    }
}
