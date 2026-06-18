<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User\Original;

use AC\Formatter\User\Property;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\OriginalColumnFactory;
use ACP\Editing;
use ACP\Search;

class Email extends OriginalColumnFactory
{

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        return parent::get_feature_settings_builder($config)
                     ->set_bulk_edit();
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\User\Email($this->get_label());
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Property('user_email'));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\User\Email();
    }

}