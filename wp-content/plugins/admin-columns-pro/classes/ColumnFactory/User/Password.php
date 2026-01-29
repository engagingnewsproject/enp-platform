<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User;

use AC\Formatter\Message;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\FeatureSettingBuilder;
use ACP\Editing;

class Password extends ACP\Column\AdvancedColumnFactory
{

    public function get_column_type(): string
    {
        return 'column-user_password';
    }

    public function get_label(): string
    {
        return __('Password', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $message = 'on' === $config->get('edit', '')
            ? __('Set new password', 'codepress-admin-columns')
            : __('Enable Inline Edit to change password', 'codepress-admin-columns');

        return new FormatterCollection([
            new Message($message),
        ]);
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        return parent::get_feature_settings_builder($config)
                     ->set_bulk_edit();
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\User\Password();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return null;
    }
}