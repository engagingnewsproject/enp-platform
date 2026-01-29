<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User;

use AC;
use AC\FormatterCollection;
use AC\MetaType;
use AC\Setting\Config;
use ACP;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class Description extends EnhancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    public function __construct(
        AC\ColumnFactory\User\DescriptionFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
    }

    protected function get_editing(Config $config): ACP\Editing\Service
    {
        return new Editing\Service\Basic(
            (new Editing\View\TextArea())->set_clear_button(true),
            new Editing\Storage\Meta('description', new MetaType(MetaType::USER))
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AC\Formatter\User\Meta('description'));
    }

    protected function get_sorting(Config $config): Sorting\Model\QueryBindings
    {
        return new Sorting\Model\User\Meta('description');
    }

    protected function get_search(Config $config): ACP\Search\Comparison
    {
        return new Search\Comparison\Meta\Text('description');
    }

}