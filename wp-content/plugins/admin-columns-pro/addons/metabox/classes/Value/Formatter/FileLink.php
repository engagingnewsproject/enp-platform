<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Helper;
use AC\Type\Value;

class FileLink implements Formatter
{

    private string $link_to;

    public function __construct(string $link_to = '')
    {
        $this->link_to = $link_to;
    }

    public function format(Value $value): Value
    {
        $data = $value->get_value();

        if ( ! is_array($data)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $url = (string)($data['url'] ?? '');
        $label = esc_html((string)($data['name'] ?? ''));

        if ($this->link_to === 'download') {
            return $value->with_value(
                Helper\Html::create()->link($url, $label, ['download' => ''])
            );
        }

        if ($this->link_to === 'edit') {
            $attachment_id = attachment_url_to_postid($url);
            $edit_url = $attachment_id ? (get_edit_post_link($attachment_id) ?: '') : '';

            return $value->with_value(
                Helper\Html::create()->link($edit_url ?: $url, $label)
            );
        }

        return $value->with_value(
            Helper\Html::create()->link($url, $label, ['target' => '_blank'])
        );
    }

}
