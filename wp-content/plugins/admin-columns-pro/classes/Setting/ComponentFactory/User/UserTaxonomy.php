<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory\User;

use AC\Expression\Specification;
use AC\Setting\Component;
use AC\Setting\ComponentBuilder;
use AC\Setting\ComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input\OptionFactory;
use AC\Setting\Control\OptionCollection;

class UserTaxonomy implements ComponentFactory
{

    public function create(Config $config, ?Specification $conditions = null): Component
    {
        $taxonomies = $this->get_taxonomies();

        $default = array_key_first($taxonomies);

        $builder = (new ComponentBuilder())
            ->set_label(__('Taxonomy', 'codepress-admin-columns'))
            ->set_input(
                OptionFactory::create_select(
                    'taxonomy',
                    OptionCollection::from_array($taxonomies),
                    (string)$config->get('taxonomy', $default)
                )
            );

        if ($conditions) {
            $builder->set_conditions($conditions);
        }

        return $builder->build();
    }

    protected function get_taxonomies(): array
    {
        $taxonomies = get_object_taxonomies('user', 'objects');

        $options = [];

        foreach ($taxonomies as $taxonomy) {
            $options[$taxonomy->name] = $taxonomy->label;
        }

        natcasesort($options);

        return $options;
    }

}