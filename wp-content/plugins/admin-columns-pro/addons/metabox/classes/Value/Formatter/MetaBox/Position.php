<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter\MetaBox;

use AC;
use AC\Type\Value;

class Position implements AC\Formatter
{

    public function format(Value $value)
    {
        $data = get_post_meta($value->get_id(), 'settings', true);

        if (empty($data) || ! isset($data['context'])) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        switch ($data['context']) {
            case'side':
                $position = __('Side', 'codepress-admin-columns');
                break;
            default:
                $position = __('After content', 'codepress-admin-columns');
        }

        return $value->with_value($position);
    }

}