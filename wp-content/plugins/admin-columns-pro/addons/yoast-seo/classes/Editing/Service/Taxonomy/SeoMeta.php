<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Editing\Service\Taxonomy;

use AC\Type\TaxonomySlug;
use ACP\Editing;
use ACP\Editing\View;

class SeoMeta implements Editing\Service
{

    private string $meta_key;

    private string $taxonomy;

    private Editing\View $view;

    public function __construct(TaxonomySlug $taxonomy, string $meta_key, Editing\View $view = null)
    {
        $this->meta_key = $meta_key;
        $this->taxonomy = (string)$taxonomy;
        $this->view = $view ?: new Editing\View\Text();
    }

    public function get_view(string $context): ?View
    {
        return $this->view;
    }

    public function get_value(int $id)
    {
        $meta = get_option('wpseo_taxonomy_meta');

        if ( ! is_array($meta)) {
            return false;
        }

        return $meta[$this->taxonomy][$id][$this->meta_key] ?? false;
    }

    public function update(int $id, $data): void
    {
        $meta = get_option('wpseo_taxonomy_meta');

        if ( ! isset($meta[$this->taxonomy])) {
            $meta[$this->taxonomy] = [];
        }

        if ( ! isset($meta[$this->taxonomy][$id])) {
            $meta[$this->taxonomy][$id] = [];
        }

        $meta[$this->taxonomy][$id][$this->meta_key] = $data;

        update_option('wpseo_taxonomy_meta', $meta);
    }

}