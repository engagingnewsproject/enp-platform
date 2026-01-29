<?php

declare(strict_types=1);

namespace ACP\TableScreen;

use AC;
use AC\ListTableFactory;
use AC\MetaType;
use AC\TableScreen;
use AC\Type\Labels;
use AC\Type\TableId;
use AC\Type\TaxonomySlug;
use AC\Type\Uri;
use WP_Taxonomy;

class Taxonomy extends TableScreen implements AC\Taxonomy, TableScreen\ListTable, TableScreen\MetaType,
                                              TableScreen\TotalItems
{

    use AC\ListTable\TotalItemsTrait;

    private WP_Taxonomy $taxonomy;

    public function __construct(WP_Taxonomy $taxonomy, Uri $url)
    {
        parent::__construct(
            new TableId('wp-taxonomy_' . $taxonomy->name),
            sprintf('edit-%s', $taxonomy->name),
            new Labels(
                $taxonomy->labels->singular_name,
                $taxonomy->labels->name
            ),
            $url
        );

        $this->taxonomy = $taxonomy;
    }

    public function list_table(): AC\ListTable
    {
        return ListTableFactory::create_taxonomy($this->screen_id, $this->taxonomy->name);
    }

    public function get_taxonomy(): TaxonomySlug
    {
        return new TaxonomySlug($this->taxonomy->name);
    }

    public function get_meta_type(): MetaType
    {
        return new MetaType(MetaType::TERM);
    }

}