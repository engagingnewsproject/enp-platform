<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\Activity;

use AC;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\UserLink;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\BP\Value\Formatter;
use ACP\Setting\ComponentFactory\UserProperty;

class UserFactory extends AC\Column\BaseColumnFactory
{

    private UserProperty $user_property;

    private UserLink $user_link;

    public function __construct(
        DefaultSettingsBuilder $default_settings_builder,
        UserProperty $user_property,
        UserLink $user_link
    ) {
        parent::__construct($default_settings_builder);
        $this->user_property = $user_property;
        $this->user_link = $user_link;
    }

    public function get_column_type(): string
    {
        return 'column-activity_user';
    }

    public function get_label(): string
    {
        return __('User', 'codepress-admin-columns');
    }

    protected function get_group(): ?string
    {
        return 'buddypress';
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->user_property->create($config),
            $this->user_link->create($config),
        ]);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return (new FormatterCollection([
            new Formatter\Activity\UserId(),
        ]))->merge(parent::get_formatters($config));
    }

}