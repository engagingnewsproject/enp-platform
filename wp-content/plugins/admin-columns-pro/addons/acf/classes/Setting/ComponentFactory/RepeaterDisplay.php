<?php

declare(strict_types=1);

namespace ACA\ACF\Setting\ComponentFactory;

use AC;
use AC\Setting\Children;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class RepeaterDisplay extends AC\Setting\ComponentFactory\BaseComponentFactory
{

    public const NAME = 'repeater_display';
    public const DISPLAY_SUBFIELD = 'subfield';
    public const DISPLAY_COUNT = 'count';

    private RepeaterSubField $subfield;

    public function __construct(RepeaterSubField $subfield)
    {
        $this->subfield = $subfield;
    }

    protected function get_label(Config $config): ?string
    {
        return __('Display', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            OptionCollection::from_array([
                self::DISPLAY_SUBFIELD => __('Subfield', 'codepress-admin-columns'),
                self::DISPLAY_COUNT    => __('Number of Rows', 'codepress-admin-columns'),
            ]),
            $config->get(self::NAME, self::DISPLAY_SUBFIELD)
        );
    }

    protected function get_children(Config $config): ?Children
    {
        return new Children(
            new AC\Setting\ComponentCollection([
                $this->subfield->create(
                    $config,
                    AC\Expression\StringComparisonSpecification::equal(self::DISPLAY_SUBFIELD)
                ),
            ])
        );
    }

}