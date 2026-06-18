<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Service;

use AC\PostType;
use AC\Registerable;
use AC\Table\Screen;
use AC\TableScreen;
use ACA\YoastSeo\Settings\ListScreen\TableElement\FilterReadabilityScore;
use ACA\YoastSeo\Settings\ListScreen\TableElement\FilterSeoScores;
use ACP\Settings\ListScreen\TableElements;
use WPSEO_Metabox_Analysis_Readability;
use WPSEO_Metabox_Analysis_SEO;
use WPSEO_Post_Type;

final class HideFilters implements Registerable
{

    private FilterSeoScores $seo_scores;

    private FilterReadabilityScore $readability_score;

    public function __construct(FilterSeoScores $seo_scores, FilterReadabilityScore $readability_score)
    {
        $this->seo_scores = $seo_scores;
        $this->readability_score = $readability_score;
    }

    public function register(): void
    {
        add_action('ac/table', [$this, 'hide_filter']);
        add_action('ac/admin/settings/table_elements', [$this, 'add_hide_filter'], 10, 2);
    }

    public function hide_filter(Screen $table): void
    {
        global $wpseo_meta_columns;

        if ( ! $wpseo_meta_columns) {
            return;
        }

        $list_screen = $table->get_list_screen();

        if ( ! $list_screen) {
            return;
        }

        if ( ! $this->seo_scores->is_enabled($list_screen)) {
            remove_action('restrict_manage_posts', [$wpseo_meta_columns, 'posts_filter_dropdown']);
        }
        if ( ! $this->readability_score->is_enabled($list_screen)) {
            remove_action('restrict_manage_posts', [$wpseo_meta_columns, 'posts_filter_dropdown_readability']);
        }
    }

    private function is_post_type_supported(string $post_type): bool
    {
        return class_exists('WPSEO_Post_Type') && WPSEO_Post_Type::is_post_type_accessible($post_type);
    }

    private function is_analysis_enabled(string $post_type): bool
    {
        return $this->is_post_type_supported($post_type) && (new WPSEO_Metabox_Analysis_SEO())->is_globally_enabled();
    }

    private function is_readability_enabled(string $post_type): bool
    {
        return $this->is_post_type_supported($post_type)
               && (new WPSEO_Metabox_Analysis_Readability())->is_globally_enabled();
    }

    public function add_hide_filter(TableElements $collection, TableScreen $table_screen): void
    {
        if ( ! $table_screen instanceof PostType) {
            return;
        }

        if ($this->is_analysis_enabled((string)$table_screen->get_post_type())) {
            $collection->add($this->seo_scores, 38);
        }
        if ($this->is_readability_enabled((string)$table_screen->get_post_type())) {
            $collection->add($this->readability_score, 38);
        }
    }

}