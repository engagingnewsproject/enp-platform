<?php

namespace ACP\Formatter\Post;

use AC;
use AC\Type\Value;

class ImagesExtendedLink implements AC\CollectionFormatter
{

    private AC\Value\Extended\ExtendedValue $extended_value;

    public function __construct(AC\Value\Extended\ExtendedValue $extended_value)
    {
        $this->extended_value = $extended_value;
    }

    public function format(AC\Type\ValueCollection $value): Value
    {
        if ($value->count() === 0) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        $count = $value->count();
        $id = $value->get_id();

        $total_size = 0;

        foreach ($value as $image_url) {
            $total_size += (int)ac_helper()->image->get_local_image_size($image_url->get_value());
        }

        $total_size = ac_helper()->file->get_readable_filesize($total_size);

        $label = sprintf(_n('%d image', '%d images', $count, 'codepress-admin-columns'), $count);
        $link = $this->extended_value->get_link($value->get_id(), $label)
                                     ->with_edit_link(get_edit_post_link($id))
                                     ->with_title(strip_tags(get_the_title($id)) ?: $id)
                                     ->with_class('-image-container');

        return new Value(
            $value->get_id(),
            $link->render() . ac_helper()->html->rounded($total_size)
        );
    }

}