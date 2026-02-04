<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Value\Formatter\UserSubscription;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use DateTime;
use WC_Subscription;

class InactiveSubscriptions implements Formatter
{

    public function format(Value $value)
    {
        if (wcs_user_has_subscription($value->get_id(), '', 'active')) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $values = [];
        $subscriptions = wcs_get_users_subscriptions($value->get_id());

        foreach ($subscriptions as $subscription) {
            $status = $subscription->get_status();

            $values[] = sprintf(
                '<div class="subscription subscription-%s" %s>%s <small>%s</small></div>'
                ,
                esc_attr($status)
                ,
                ac_helper()->html->get_tooltip_attr($this->get_order_tooltip($subscription))
                ,
                ac_helper()->html->link(
                    get_edit_post_link($subscription->get_id()),
                    wcs_get_subscription_status_name($subscription->get_status())
                )
                ,
                esc_attr($this->get_subscription_description($subscription))
            );
        }

        return $value->with_value(implode(', ', $values));
    }

    private function get_inactive_subscription_date(WC_Subscription $subscription): ?DateTime
    {
        switch ($subscription->get_status()) {
            case 'on-hold' :
            case 'refunded' :
            case 'cancelled' :
            case 'expired':
                $date = DateTime::createFromFormat(
                    'Y-m-d H:i:s',
                    (string)$subscription->get_date('date_created')
                );

                return $date ?: null;
            case 'active':
            case 'switched':
            case 'pending-cancel':
            default :
                return null;
        }
    }

    private function get_order_tooltip(WC_Subscription $subscription): string
    {
        $date = $this->get_inactive_subscription_date($subscription);

        $status = wcs_get_subscription_status_name($subscription->get_status());

        return $date
            ? sprintf(__('%s since %s', 'codepress-admin-columns'), $status, $date->format('Y-m-d H:i:s'))
            : $status;
    }

    private function format_date(DateTime $date): string
    {
        return ac_format_date(wc_date_format(), $date->getTimestamp());
    }

    private function get_subscription_description(WC_Subscription $subscription): ?string
    {
        $date = $this->get_inactive_subscription_date($subscription);

        return $date
            ? sprintf(__('since %s', 'codepress-admin-columns'), $this->format_date($date))
            : null;
    }

}