<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\User;

use AC\Formatter\Collection\Separator;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\BP\Editing;
use ACA\BP\Search;
use ACA\BP\Settings\ComponentFactory;
use ACA\BP\Value\Formatter\User\GroupCollection;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class GroupsFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;

    private ComponentFactory\Group $group;

    private ComponentFactory\GroupLink $group_link;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ComponentFactory\Group $group,
        ComponentFactory\GroupLink $group_link
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->group = $group;
        $this->group_link = $group_link;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->group->create($config),
            $this->group_link->create($config),
        ]);
    }

    protected function get_group(): ?string
    {
        return 'buddypress';
    }

    public function get_column_type(): string
    {
        return 'column-buddypress_user_groups';
    }

    public function get_label(): string
    {
        return __('Groups', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = new FormatterCollection([
            new GroupCollection(),
        ]);
        $formatters->merge(parent::get_formatters($config));
        $formatters->add(new Separator());

        return $formatters;
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\User\Groups();
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\User\Groups();
    }

}