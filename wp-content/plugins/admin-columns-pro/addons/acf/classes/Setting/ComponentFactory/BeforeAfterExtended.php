<?php

declare(strict_types=1);

namespace ACA\ACF\Setting\ComponentFactory;

use AC;
use AC\Expression\StringComparisonSpecification;
use AC\FormatterCollection;
use AC\Setting;
use AC\Setting\Children;
use AC\Setting\Component;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\Input\Open;

class BeforeAfterExtended extends BaseComponentFactory
{

    private const BEFORE = 'before';
    private const AFTER = 'after';

    private string $before;

    private string $after;

    public function __construct(?string $before = null, ?string $after = null)
    {
        $this->before = $before;
        $this->after = $after;
    }

    protected function get_input(Config $config): ?Input
    {
        return Setting\Control\Input\OptionFactory::create_toggle(
            'apply_before_after',
            null,
            $config->has('apply_before_after') ? $config->get('apply_before_after') : 'off'
        );
    }

    protected function get_label(Config $config): ?string
    {
        return __('Before / After', 'codepress-admin-columns');
    }

    protected function get_children(Config $config): ?Children
    {
        return new Children(
            new ComponentCollection([
                new Component(
                    __('Prepend', 'codepress-admin-columns'),
                    __('Appears before the rendered column value', 'codepress-admin-columns'),
                    new Open(self::BEFORE, null, $config->get(self::BEFORE)),
                    StringComparisonSpecification::equal('on')
                ),
                new Component(
                    __('Append', 'codepress-admin-columns'),
                    __('Appears after the rendered column value', 'codepress-admin-columns'),
                    new Open(self::AFTER, null, $config->get(self::AFTER)),
                    StringComparisonSpecification::equal('on')
                ),
            ])
            , true
        );
    }

    protected function add_formatters(Config $config, FormatterCollection $formatters): void
    {
        if ('off' === $config->get('apply_before_after')) {
            return;
        }

        $formatters->add(
            new AC\Formatter\BeforeAfter(
                $config->get(self::BEFORE) ?: $this->before,
                $config->get(self::AFTER) ?: $this->after
            )
        );
    }

}