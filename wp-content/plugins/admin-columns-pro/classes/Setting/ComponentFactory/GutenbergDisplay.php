<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory;

use AC;
use AC\FormatterCollection;
use AC\Setting\Children;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use ACP\Formatter\Post\BlockCount;
use ACP\Formatter\Post\BlockStructure;
use ACP\Formatter\Post\ParsedGutenbergBlocks;

class GutenbergDisplay extends AC\Setting\ComponentFactory\BaseComponentFactory
{

    private $number_of_items;

    public function __construct(AC\Setting\ComponentFactory\NumberOfItems $number_of_items)
    {
        $this->number_of_items = $number_of_items;
    }

    protected function get_label(Config $config): ?string
    {
        return __('Display', 'codepress-admin-columns');
    }

    protected function add_formatters(Config $config, FormatterCollection $formatters): void
    {
        $formatters->add(new ParsedGutenbergBlocks());

        if ($config->get('gutenberg_display') === 'count') {
            $formatters->add(new BlockCount());
        }

        if ($config->get('gutenberg_display') === 'structure') {
            $formatters->add(
                new BlockStructure($config->has('number_of_items') ? (int)$config->get('number_of_items') : null)
            );
        }
    }

    protected function get_children(Config $config): ?Children
    {
        return new Children(
            new AC\Setting\ComponentCollection([
                $this->number_of_items->create(
                    $config,
                    AC\Expression\StringComparisonSpecification::equal('structure')
                ),
            ])
        );
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'gutenberg_display',
            AC\Setting\Control\OptionCollection::from_array([
                'count'     => __('Block Count', 'codepress-admin-columns'),
                'structure' => __('Block Structure', 'codepress-admin-columns'),
            ]),
            $config->get('gutenberg_display') ?: 'count'
        );
    }

}