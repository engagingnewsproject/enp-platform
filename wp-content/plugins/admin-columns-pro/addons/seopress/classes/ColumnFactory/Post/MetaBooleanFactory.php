<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactory\Post;

use AC\Formatter\Post\Meta;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Type\ToggleOptions;
use ACP;

class MetaBooleanFactory extends MetaFactory
{

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Toggle(
                ToggleOptions::create_from_array([
                    'yes' => 'Yes',
                    ''    => 'no',
                ])
            ),
            new ACP\Editing\Storage\Post\Meta($this->get_meta_key())
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Meta($this->get_meta_key()));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Checkmark($this->get_meta_key());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta($this->get_meta_key());
    }

}