<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\Control\OptionCollection;
use ACA\YoastSeo\Value\Formatter\OptionLabel;
use ACP;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;
use Yoast;
use Yoast\WP\SEO\Config\Schema_Types;

class SchemaPageTypeFactory extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY = '_yoast_wpseo_schema_page_type';

    protected function get_group(): ?string
    {
        return 'yoast-seo';
    }

    public function get_column_type(): string
    {
        return 'column-yoast_page_type';
    }

    public function get_label(): string
    {
        return __('Schema Page type', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new AC\Formatter\Post\Meta(self::META_KEY))
                     ->add(new OptionLabel(OptionCollection::from_array($this->get_page_types())));
    }

    private function get_page_types(): array
    {
        $options = [];

        if (class_exists(Schema_Types::class)) {
            foreach ((new Yoast\WP\SEO\Config\Schema_Types())->get_page_type_options() as $option) {
                $options[$option['value']] = $option['name'];
            }
        }

        natcasesort($options);

        return $options;
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Select($this->get_page_types()),
            new ACP\Editing\Storage\Post\Meta(self::META_KEY)
        );
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Select(self::META_KEY, $this->get_page_types());
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        $article_types = $this->get_page_types();
        natcasesort($article_types);

        return (new ACP\Sorting\Model\MetaMappingFactory())->create(
            AC\MetaType::POST,
            self::META_KEY,
            array_keys($article_types)
        );
    }

}