<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory;

use AC;
use AC\Setting\ComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;
use AC\Type\TaxonomySlug;

class TaxonomyPostType extends ComponentFactory\BaseComponentFactory
{

    private TaxonomySlug $taxonomy;

    public function __construct(TaxonomySlug $taxonomy)
    {
        $this->taxonomy = $taxonomy;
    }

    protected function get_label(Config $config): ?string
    {
        return __('Post Type', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        $options = $this->get_select_options((string)$this->taxonomy);
        $option = $options->first();
        $default = $option
            ? $option->get_value()
            : '';

        return AC\Setting\Control\Input\OptionFactory::create_select(
            'taxonomy_post_type',
            $options,
            (string)$config->get('taxonomy_post_type') ?: $default
        );
    }

    private function get_select_options(string $taxonomy): OptionCollection
    {
        $options = new OptionCollection();
        $tax_object = get_taxonomy($taxonomy);

        if (empty($tax_object)) {
            return $options;
        }

        foreach ($tax_object->object_type as $post_type) {
            $post_type_object = get_post_type_object($post_type);

            if ( ! $post_type_object) {
                continue;
            }

            $options->add(
                new AC\Setting\Control\Type\Option(
                    (string)$post_type_object->label,
                    (string)$post_type_object->name
                )
            );
        }

        return $options;
    }
}