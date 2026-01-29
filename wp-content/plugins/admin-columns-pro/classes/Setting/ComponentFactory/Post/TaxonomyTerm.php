<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory\Post;

use AC;
use AC\Setting\Children;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Type\TaxonomySlug;

class TaxonomyTerm extends AC\Setting\ComponentFactory\BaseComponentFactory
{

    public const NAME = 'taxonomy';

    private AC\Type\PostTypeSlug $post_type;

    public function __construct(AC\Type\PostTypeSlug $post_type)
    {
        $this->post_type = $post_type;
    }

    protected function get_children(Config $config): ?Children
    {
        $components = new ComponentCollection([]);

        $taxonomy = $config->get(self::NAME, '');

        if ($taxonomy !== '') {
            $components->add(
                (new Term(new TaxonomySlug($taxonomy)))->create($config)
            );
        }

        return new Children($components);
    }

    protected function get_label(Config $config): ?string
    {
        return __('Taxonomy', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            $this->get_option_collection(),
            $config->get(self::NAME, ''),
            null,
            null,
            new AC\Setting\AttributeCollection([
                AC\Setting\AttributeFactory::create_refresh(),
            ])
        );
    }

    protected function get_option_collection(): AC\Setting\Control\OptionCollection
    {
        return AC\Setting\Control\OptionCollection::from_array(
            ac_helper()->taxonomy->get_taxonomy_selection_options((string)$this->post_type)
        );
    }

}