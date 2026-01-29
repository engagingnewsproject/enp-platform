<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class BackordersFormatted implements Formatter
{

    public function format(Value $value)
    {
        switch ($value->get_value()) {
            case 'no' :
                return $value->with_value(
                    ac_helper()->icon->no(__('Do not allow backorders', 'codepress-admin-columns'))
                );
            case 'yes' :
                return $value->with_value(ac_helper()->icon->yes(__('Allow backorders', 'codepress-admin-columns')));
            case 'notify' :
                $icon_email = ac_helper()->icon->dashicon(['icon' => 'email-alt']);

                return $value->with_value(
                    ac_helper()->html->tooltip(
                        ac_helper()->icon->yes() . $icon_email,
                        __('Yes, but notify customer', 'woocommerce')
                    )
                );
            default :
                throw ValueNotFoundException::from_id($value->get_id());
        }
    }

}