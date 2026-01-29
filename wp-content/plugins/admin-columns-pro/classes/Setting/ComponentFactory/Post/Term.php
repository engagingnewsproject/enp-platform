<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory\Post;

use AC;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;
use AC\Setting\Control\Type\Option;
use AC\Type\TaxonomySlug;

class Term extends AC\Setting\ComponentFactory\BaseComponentFactory
{

    public const NAME = 'term_id';

    private TaxonomySlug $taxonomy;

    public function __construct(TaxonomySlug $taxonomy)
    {
        $this->taxonomy = $taxonomy;
    }

    protected function get_label(Config $config): ?string
    {
        return __('Term', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            $this->get_term_options(),
            $config->get(self::NAME, '')
        );
    }

    private function get_term_options(): OptionCollection
    {
        $terms = get_terms((string)$this->taxonomy);
        $options = new OptionCollection();

        if ($terms && is_array($terms)) {
            foreach ($terms as $term) {
                $options->add(new Option((string)$term->name, (string)$term->term_id));
            }
        }

        return $options;
    }
}