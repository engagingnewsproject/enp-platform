<?php

declare(strict_types=1);

namespace ACA\WC\Service;

use AC\Helper;
use AC\Registerable;

final class TableScreenProductVariations implements Registerable
{

    public function register(): void
    {
        // Add quick action to product overview
        add_filter('post_row_actions', [$this, 'add_quick_action_variation'], 10, 2);
        add_action('manage_product_posts_custom_column', [$this, 'add_quick_link_variation'], 11, 2);
    }

    private function get_list_table_link(int $product_id): ?string
    {
        $product = wc_get_product($product_id);

        if ( ! $product || 'variable' !== $product->get_type()) {
            return null;
        }

        return add_query_arg(
            [
                'post_type'   => 'product_variation',
                'post_parent' => $product_id,
            ],
            admin_url('edit.php')
        );
    }

    /**
     * Add a quick action on the product overview which links to the product variations page.
     */
    public function add_quick_action_variation($actions, $post)
    {
        if ('product' !== $post->post_type) {
            return $actions;
        }

        $link = $this->get_list_table_link($post->ID);

        if ($link) {
            $actions['variation'] = Helper\Html::create()->link($link, __('View Variations', 'codepress-admin-columns'));
        }

        return $actions;
    }

    /**
     * Display an icon on the product name column which links to the product variations page.
     */
    public function add_quick_link_variation($column, $post_id): void
    {
        if ('name' !== $column) {
            return;
        }

        $link = $this->get_list_table_link((int)$post_id);

        if ( ! $link) {
            return;
        }

        $label = Helper\Html::create()->tooltip(
            '<span class="ac-wc-view"></span>',
            __('View Variations', 'codepress-admin-columns')
        );

        echo Helper\Html::create()->link($link, $label, ['class' => 'view-variations']);
    }

}