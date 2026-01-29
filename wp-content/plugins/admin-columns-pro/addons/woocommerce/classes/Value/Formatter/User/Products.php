<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use ACA\WC\Value\ExtendedValue;
use WP_User;

class Products implements Formatter
{

    private ExtendedValue\User\Products $extended_value;

    public function __construct(ExtendedValue\User\Products $extended_value)
    {
        $this->extended_value = $extended_value;
    }

    public function format(Value $value)
    {
        $user_id = (int)$value->get_id();
        $label = $value->get_value();

        if ( ! $label) {
            throw ValueNotFoundException::from_id($user_id);
        }

        $user = get_userdata($user_id);

        if ( ! $user instanceof WP_User) {
            throw ValueNotFoundException::from_id($user_id);
        }

        $link = $this->extended_value->get_link($user_id, (string)$label)
                                     ->with_class('-nopadding -w-large')
                                     ->with_title(
                                         sprintf(
                                             __('Recent purchases by %s', 'codepress-admin-columns'),
                                             sprintf('”%s”', ac_helper()->user->get_formatted_name($user)),
                                         )
                                     );

        return $value->with_value(
            $link->render()
        );
    }

}