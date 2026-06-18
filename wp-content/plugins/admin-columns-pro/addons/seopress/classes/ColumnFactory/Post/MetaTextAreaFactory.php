<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactory\Post;

use AC\Formatter\StripTags;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;

class MetaTextAreaFactory extends MetaFactory
{

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new StripTags());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\TextArea(),
            new ACP\Editing\Storage\Post\Meta($this->get_meta_key())
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text($this->get_meta_key());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta($this->get_meta_key());
    }

}