<?php

declare(strict_types=1);

namespace ACA\WC\ListTable;

use AC;
use ACA\WC\Asset\Script\TableVariation;
use WC_Admin_List_Table;
use WC_Product;
use WC_Product_Variation;

if ( ! class_exists('\WC_Admin_List_Table', false) && defined('WC_ABSPATH')) {
    include_once(constant('WC_ABSPATH') . 'includes/admin/list-tables/abstract-class-wc-admin-list-table.php');
}

class ProductVariation extends WC_Admin_List_Table
{

    protected $list_table_type = 'product_variation';

    private AC\Asset\Location\Absolute $location;

    /**
     * Constructor.
     */
    public function __construct(AC\Asset\Location\Absolute $location)
    {
        parent::__construct();

        add_filter('disable_months_dropdown', '__return_true');
        add_filter('views_edit-' . $this->list_table_type, [$this, 'get_views']);
        add_filter('query_vars', [$this, 'add_custom_query_var']);

        add_action('admin_enqueue_scripts', [$this, 'woocommerce_scripts'], 11);
        add_action('ac/table_scripts', [$this, 'table_scripts']);

        $this->location = $location;
    }

    private function get_requested_parent_product(): ?WC_Product
    {
        $request = new AC\Request();

        $current = $request->get('post_parent');

        $current_variation = $current
            ? wc_get_product($current)
            : null;

        return $current_variation instanceof WC_Product && 'variable' === $current_variation->get_type()
            ? $current_variation
            : null;
    }

    protected function render_filters(): void
    {
        $requested_parent_product = $this->get_requested_parent_product();

        /**
         * @var WC_Product_Variation[] $variations
         */
        $variations = wc_get_products([
            'type'  => 'variable',
            'limit' => 300,
        ]);

        $options = [
        ];

        foreach ($variations as $variation) {
            $options[$variation->get_id()] = $variation->get_name();
        }

        if ($requested_parent_product) {
            $options[$requested_parent_product->get_id()] = $requested_parent_product->get_name();
        }

        natcasesort($options);

        if ($options) {
            $options = ['' => 'Select product'] + $options;
        }

        $select = new AC\Form\Element\Select('post_parent', $options);
        $select->set_attribute('title', __('Select product', 'codepress-admin-columns'))
               ->set_attribute('id', 'ac_parent_product');

        if ($requested_parent_product) {
            $select->set_value($requested_parent_product->get_id());
        }

        echo $select->render();
    }

    public function define_bulk_actions($actions): array
    {
        return [];
    }

    public function table_scripts(AC\ListScreen $list_screen): void
    {
        $table_screen = $list_screen->get_table_screen();

        if ( ! $table_screen->get_id()->equals(new AC\Type\TableId('product_variation'))) {
            return;
        }

        $script = new TableVariation('aca-wc-table-' . $this->list_table_type, $this->location);
        $script->enqueue();
    }

    public function add_custom_query_var($public_query_vars): array
    {
        $public_query_vars[] = 'post_parent';

        return $public_query_vars;
    }

    public function get_views($views): array
    {
        $num_posts = wp_count_posts($this->list_table_type, 'readable');

        $statuses = [
            'publish' => __('Enabled'),
            'private' => __('Disabled'),
        ];

        foreach ($statuses as $status => $label) {
            if ($num_posts->$status > 0) {
                $views[$status] = sprintf(
                    '<a href="%s" class="%s">%s</a>(%s)',
                    add_query_arg(['post_status' => $status]),
                    ($status === get_query_var('post_status') ? 'current' : ''),
                    $label,
                    $num_posts->$status
                );
            }
        }

        return $views;
    }

    public function woocommerce_scripts(): void
    {
        wp_enqueue_style('select2');
        wp_enqueue_script('select2');

        wp_enqueue_style('jquery-ui-style');
        wp_enqueue_style('woocommerce_admin_styles');
    }

    protected function get_row_actions($actions, $post): array
    {
        unset($actions['inline hide-if-no-js']);

        if (isset($actions['edit'])) {
            $actions['edit'] = ac_helper()->html->link(
                get_edit_post_link(get_post_field('post_parent', $post->ID)) . '#variation_' . $post->ID,
                __('Edit')
            );
        }

        return $actions;
    }

    /**
     * Pre-fetch any data for the row each column has access to it. the_product global is there for bw compat.
     *
     * @param int $post_id Post ID being shown.
     */
    protected function prepare_row_data($post_id)
    {
        global $the_product;

        if (empty($this->object) || $this->object->get_id() !== $post_id) {
            $this->object = $the_product = new WC_Product_Variation($post_id);
        }
    }

}