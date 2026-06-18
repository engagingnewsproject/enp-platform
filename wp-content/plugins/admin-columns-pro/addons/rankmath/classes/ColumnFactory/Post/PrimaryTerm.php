<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactory\Post;

use AC;
use AC\Formatter\ForeignId;
use AC\Formatter\Post\Meta;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\PostTypeSlug;
use ACA\RankMath\ColumnFactory\GroupTrait;
use ACA\RankMath\Editing;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;
use RankMath\Helper;

class PrimaryTerm extends ACP\Column\AdvancedColumnFactory
{

    use ConditionalFormatTrait;
    use GroupTrait;

    private PostTypeSlug $post_type;

    private ComponentFactory\TermProperty $term_property;

    private ComponentFactory\TermLink $term_link;

    public function __construct(
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        PostTypeSlug $post_type,
        ComponentFactory\TermProperty $term_property,
        ComponentFactory\TermLink $term_link
    ) {
        $this->post_type = $post_type;
        $this->term_property = $term_property;
        $this->term_link = $term_link;

        parent::__construct($feature_setting_builder_factory, $default_settings_builder);
    }

    public function get_label(): string
    {
        return __('Primary Term', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-rankmath-primary_term';
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->term_property->create($config))
                     ->add($this->term_link->create($config));
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return (new ACP\Sorting\Model\Post\RelatedMeta\TaxonomyFactory())
            ->create($config->get('term_property', ''), $this->get_meta_key());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Post\PrimaryTerm(
            $this->get_meta_key(),
            $this->get_taxonomy(),
            (new AC\Meta\QueryMetaFactory())->create_with_post_type($this->get_meta_key(), (string)$this->post_type)
        );
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Post\PrimaryTerm($this->get_meta_key(), $this->get_taxonomy());
    }

    private function get_taxonomy(): string
    {
        return (string)Helper::get_settings('titles.pt_' . $this->post_type . '_primary_taxonomy');
    }

    protected function get_meta_key(): string
    {
        return 'rank_math_primary_' . $this->get_taxonomy();
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = new FormatterCollection([
            new Meta($this->get_meta_key()),
            new ForeignId(),
        ]);

        return $formatters->merge(parent::get_formatters($config));
    }

}