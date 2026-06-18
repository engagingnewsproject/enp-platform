<?php

declare(strict_types=1);

namespace ACA\ACF\Formatter\Media;

use AC;
use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use AC\Type\ValueCollection;
use AC\Value\Extended\ExtendedValue;

class PostsContainingImageInAcfExtendedLink implements AC\CollectionFormatter
{

    private ExtendedValue $extended_value;

    public function __construct(ExtendedValue $extended_value)
    {
        $this->extended_value = $extended_value;
    }

    public function format(ValueCollection $value): Value
    {
        if ($value->count() === 0) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $count = $value->count();
        $id = $value->get_id();

        $label = sprintf(
            _n('%d post', '%d posts', $count, 'codepress-admin-columns'),
            $count
        );

        $link = $this->extended_value
            ->get_link($id, $label)
            ->with_edit_link((string)get_edit_post_link($id))
            ->with_title(strip_tags(get_the_title($id)) ?: (string)$id);

        return new Value($id, $link->render());
    }

}
