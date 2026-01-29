<?php

declare(strict_types=1);

namespace ACP\Value\ExtendedValue\Post;

use AC\Column;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;
use AC\View;

class PostImages implements ExtendedValue
{

    private const NAME = 'post-images';

    public function can_render(string $view): bool
    {
        return $view === self::NAME;
    }

    public function get_link($id, string $label): ExtendedValueLink
    {
        return new ExtendedValueLink($label, $id, self::NAME);
    }

    public function render($id, array $params, Column $column, ListScreen $list_screen): string
    {
        $view = new View([
            'title' => get_the_title($id),
            'items' => $this->get_image_items($id),
        ]);

        return $view->set_template('modal-value/images')->render();
    }

    private function get_image_urls(int $id): array
    {
        $string = ac_helper()->post->get_raw_field('post_content', $id);
        $string = (string)apply_filters('ac/column/images/content', $string, $id, $this);

        return array_unique(ac_helper()->image->get_image_urls_from_string($string));
    }

    private function get_image_items(int $id): array
    {
        $items = [];

        foreach ($this->get_image_urls($id) as $url) {
            $size = ac_helper()->image->get_local_image_size($url);

            if (null === $size || $size <= 0) {
                continue;
            }

            $dimensions = null;
            $extension = null;
            $edit_url = null;
            $filename = basename($url);
            $alt = $filename;
            $image_src = $url;

            $info = ac_helper()->image->get_local_image_info($url);

            if ($info) {
                $dimensions = $info[0] . ' x ' . $info[1];
                $extension = image_type_to_extension($info[2], false);
            }

            $attachment_id = ac_helper()->media->get_attachment_id_by_url($url, true);

            if ($attachment_id) {
                $edit_url = get_edit_post_link($attachment_id);
                $alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            }

            $items[] = [
                'img_src'    => $image_src,
                'alt'        => $alt,
                'filename'   => $filename,
                'filetype'   => $extension,
                'filesize'   => ac_helper()->file->get_readable_filesize($size),
                'dimensions' => $dimensions,
                'edit_url'   => $edit_url,
            ];
        }

        return $items;
    }

}