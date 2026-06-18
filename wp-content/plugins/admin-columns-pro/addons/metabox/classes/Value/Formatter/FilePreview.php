<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter;

use AC\ApplyFilter\ValidAudioMimetypes;
use AC\ApplyFilter\ValidVideoMimetypes;
use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Helper;
use AC\Type\Value;

class FilePreview implements Formatter
{

    /**
     * @var string|int[]
     */
    private $size;

    /**
     * @param string|int[] $size
     */
    public function __construct($size = [80, 80])
    {
        $this->size = $size;
    }

    public function format(Value $value): Value
    {
        $data = $value->get_value();

        if ( ! is_array($data)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $url = (string)($data['url'] ?? '');
        $name = (string)($data['name'] ?? '');
        $filetype = wp_check_filetype($name);
        $mime = (string)($filetype['type'] ?: '');

        return $value->with_value($this->get_media_html($url, $name, $mime));
    }

    private function get_media_html(string $url, string $name, string $mime): string
    {
        [$width, $height] = $this->get_dimensions();

        if (in_array($mime, (new ValidVideoMimetypes())->apply_filters(), true)) {
            return sprintf(
                '<video controls preload="none" src="%s" style="max-width:%dpx;max-height:%dpx;">%s</video>',
                esc_url($url),
                $width,
                $height,
                __('No support for video player', 'codepress-admin-columns')
            );
        }

        if (in_array($mime, (new ValidAudioMimetypes())->apply_filters(), true)) {
            return sprintf(
                '<audio controls preload="none" src="%s">%s</audio>',
                esc_url($url),
                __('No support for audio player', 'codepress-admin-columns')
            );
        }

        if (strpos($mime, 'image/') === 0) {
            return sprintf(
                '<img src="%s" style="max-width:%dpx;max-height:%dpx;" alt="%s">',
                esc_url($url),
                $width,
                $height,
                esc_attr($name)
            );
        }

        return Helper\Html::create()->link($url, esc_html($name), ['target' => '_blank']);
    }

    /**
     * @return int[]
     */
    private function get_dimensions(): array
    {
        if (is_array($this->size)) {
            return $this->size;
        }

        global $_wp_additional_image_sizes;

        $width = $_wp_additional_image_sizes[$this->size]['width'] ?? get_option("{$this->size}_size_w");
        $height = $_wp_additional_image_sizes[$this->size]['height'] ?? get_option("{$this->size}_size_h");

        return [(int)($width ?: 80), (int)($height ?: 80)];
    }

}
