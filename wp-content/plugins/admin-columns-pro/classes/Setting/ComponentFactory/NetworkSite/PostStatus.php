<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory\NetworkSite;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;

class PostStatus extends BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Post Status', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select_remote(
            'post_status',
            'ac-get-network-post-statuses',
            $config->get('post_status', ''),
        );
    }

    protected function get_description(Config $config): ?string
    {
        $excluded = $this->get_exluded_post_statuses(
            $config->get('post_status', '')
        );

        return count($excluded) > 0
            ? sprintf(
                __('Does not include %s', 'codepress-admin-columns'),
                ac_helper()->string->enumeration_list($excluded)
            )
            : null;
    }

    private function get_exluded_post_statuses($post_status): array
    {
        if ('without_trash' === $post_status) {
            return get_post_stati(['show_in_admin_all_list' => false]);
        }
        if ( ! $post_status) {
            return get_post_stati(['show_in_admin_status_list' => false]);
        }

        return [];
    }

}