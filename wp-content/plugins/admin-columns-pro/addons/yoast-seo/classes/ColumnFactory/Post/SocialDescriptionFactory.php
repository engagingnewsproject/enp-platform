<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class SocialDescriptionFactory extends ACP\Column\AdvancedColumnFactory
{

    protected function get_group(): ?string
    {
        return 'yoast-seo';
    }

    public function get_column_type(): string
    {
        return 'column-yoast_facebook_description';
    }

    public function get_label(): string
    {
        return __('Social Description', 'codepress-admin-columns');
    }

    private function get_meta_key(): string
    {
        return '_yoast_wpseo_opengraph-description';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new AC\Formatter\Post\Meta('_yoast_wpseo_opengraph-description'));
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Text())->set_clear_button(true),
            new ACP\Editing\Storage\Meta($this->get_meta_key(), new AC\MetaType(AC\MetaType::POST))
        );
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text($this->get_meta_key());
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta($this->get_meta_key());
    }

}