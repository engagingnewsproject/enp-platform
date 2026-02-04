<?php

declare(strict_types=1);

namespace ACA\WC\Asset\Script;

use AC;
use AC\Preferences\SiteFactory;

class TableVariation extends AC\Asset\Script
{

    private const TABLE = 'product_variation';

    public function __construct(string $handle, AC\Asset\Location\Absolute $location)
    {
        parent::__construct($handle, $location->with_suffix('assets/js/table-variation.js'), ['jquery']);
    }

    public function register(): void
    {
        parent::register();

        wp_localize_script($this->handle, 'aca_wc_table_variation', [
            'button_back_label' => __('Back to products', 'codepress-admin-columns'),
            'button_back_link'  => $this->get_referer_link(),
        ]);
    }

    private function get_referer_link(): string
    {
        $preference = (new SiteFactory())->create('referer');

        $referer = $this->check_referer('product');

        if ($referer) {
            $preference->save(
                self::TABLE,
                $referer
            );
        } elseif ( ! $this->check_referer(self::TABLE)) {
            // Remove preference link when referer is neither from product nor product_variation
            $preference->delete(self::TABLE);
        }

        $link = $preference->find(self::TABLE);

        if ( ! $link) {
            $link = add_query_arg(['post_type' => 'product'], admin_url('edit.php'));
        }

        return $link;
    }

    /**
     * Checks if the referer came from another list table
     */
    private function check_referer(string $post_type): ?string
    {
        $referer = wp_get_referer();

        if ( ! $referer) {
            return null;
        }

        if (false === strpos($referer, admin_url('edit.php'))) {
            return null;
        }

        $parts = parse_url($referer);

        if ( ! isset($parts['query'])) {
            return null;
        }

        parse_str($parts['query'], $query);

        if ( ! isset($query['post_type']) || $post_type !== $query['post_type']) {
            return null;
        }

        return $referer;
    }

}