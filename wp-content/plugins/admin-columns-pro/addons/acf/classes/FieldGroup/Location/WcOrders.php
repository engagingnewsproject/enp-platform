<?php

declare(strict_types=1);

namespace ACA\ACF\FieldGroup\Location;

use ACA\ACF\FieldGroup;

class WcOrders implements FieldGroup\Query
{

    public function get_groups(): array
    {
        add_filter('acf/location/rule_match/user_type', '__return_true', 16);
        add_filter('acf/location/rule_match/page_type', '__return_true', 16);

        add_filter('acf/location/rule_match/post', '__return_true', 16);
        add_filter('acf/location/rule_match/post_category', '__return_true', 16);
        add_filter('acf/location/rule_match/post_status', '__return_true', 16);
        add_filter('acf/location/rule_match/post_taxonomy', '__return_true', 16);

        $groups = acf_get_field_groups(['post_type' => 'shop_order']);

        remove_filter('acf/location/rule_match/user_type', '__return_true', 16);
        remove_filter('acf/location/rule_match/page_type', '__return_true', 16);

        remove_filter('acf/location/rule_match/post_format', '__return_true', 16);

        remove_filter('acf/location/rule_match/page', '__return_true', 16);
        remove_filter('acf/location/rule_match/page_parent', '__return_true', 16);
        remove_filter('acf/location/rule_match/page_template', '__return_true', 16);

        remove_filter('acf/location/rule_match/post', '__return_true', 16);
        remove_filter('acf/location/rule_match/post_category', '__return_true', 16);
        remove_filter('acf/location/rule_match/post_status', '__return_true', 16);
        remove_filter('acf/location/rule_match/post_taxonomy', '__return_true', 16);
        remove_filter('acf/location/rule_match/post_template', '__return_true', 16);

        return $groups;
    }

}