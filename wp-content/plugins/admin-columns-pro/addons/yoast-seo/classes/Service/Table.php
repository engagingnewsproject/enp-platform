<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Service;

use AC\Registerable;
use AC\TableScreen;

class Table implements Registerable
{

    public function register(): void
    {
        add_action('ac/table/admin_footer', [$this, 'fix_yoast_heading_tooltips']);
        add_action('ac/table/screen', [$this, 'remove_link_column_on_ajax']);
    }

    public function remove_link_column_on_ajax(TableScreen $table_screen): void
    {
        $screen_id = $table_screen->get_screen_id();

        /**
         * Quickfix for Yoast SEO Link column, that gives an error on the/our Ajax call
         * We unset this column on our Ajax Request so
         */
        add_filter("manage_{$screen_id}_columns", function ($headings) {
            if (filter_input(INPUT_POST, 'ac_action') && is_array($headings)) {
                $headings = $this->replace_key_maintain_order($headings, 'wpseo-links', 'wpseo-links_empty');
                $headings = $this->replace_key_maintain_order($headings, 'wpseo-linked', 'wpseo-linked_empty');
            }

            return $headings;
        }, 201);
    }

    /**
     * Replace key & Maintain Order
     */
    private function replace_key_maintain_order(array $array, string $oldkey, string $newkey): array
    {
        if (array_key_exists($oldkey, $array)) {
            $keys = array_keys($array);
            $keys[array_search($oldkey, $keys)] = $newkey;

            return array_combine($keys, $array);
        }

        return $array;
    }

    public function fix_yoast_heading_tooltips(): void
    {
        ?>
		<style>
			.wp-list-table th > a.yoast-tooltip::before,
			.wp-list-table th > a.yoast-tooltip::after {
				display: none !important;
			}
		</style>
        <?php
    }
}