<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Setting\ComponentFactory;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;
use AC\Type\PostTypeSlug;

class PrimaryTaxonomy extends BaseComponentFactory
{

    private PostTypeSlug $post_type_slug;

    public function __construct(PostTypeSlug $post_type_slug)
    {
        $this->post_type_slug = $post_type_slug;
    }

    protected function get_label(Config $config): ?string
    {
        return __('Taxonomy');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'primary_taxonomy',
            $this->get_taxonomies((string)$this->post_type_slug),
            $config->get('primary_taxonomy', '')
        );
    }

    private function get_taxonomies(string $post_type): OptionCollection
    {
        $taxonomies = get_object_taxonomies($post_type, 'objects');
        $options = [];

        foreach ($taxonomies as $taxonomy => $tax_object) {
            if ( ! $tax_object->hierarchical) {
                continue;
            }

            $options[$taxonomy] = $tax_object->label;
        }

        return OptionCollection::from_array($options);
    }

}