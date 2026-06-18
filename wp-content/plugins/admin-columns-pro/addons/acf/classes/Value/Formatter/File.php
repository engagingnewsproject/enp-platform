<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\ApplyFilter\ValidAudioMimetypes;
use AC\ApplyFilter\ValidVideoMimetypes;
use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Helper;
use AC\Type\Value;

class File implements Formatter
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
        $attachment_id = $value->get_value();

        if ( ! is_numeric($attachment_id)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $attachment = get_attached_file($attachment_id);

        if ( ! $attachment) {
            return $value->with_value('<em>' . __('Invalid attachment', 'codepress-admin-columns') . '</em>');
        }

        $attachment_id = (int)$attachment_id;
        $mime_type = (string)get_post_field('post_mime_type', $attachment_id);
        $url = wp_get_attachment_url($attachment_id) ?: '';

        return $value->with_value($this->get_media_html($attachment_id, $mime_type, $url, $attachment));
    }

    private function get_media_html(int $attachment_id, string $mime_type, string $url, string $attachment): string
    {
        if (in_array($mime_type, (new ValidVideoMimetypes())->apply_filters(), true)) {
            [$width, $height] = $this->get_dimensions();

            return sprintf(
                '<video controls preload="none" src="%s" style="max-width:%dpx;max-height:%dpx;">%s</video>',
                esc_url($url),
                $width,
                $height,
                __('No support for video player', 'codepress-admin-columns')
            );
        }

        if (in_array($mime_type, (new ValidAudioMimetypes())->apply_filters(), true)) {
            return sprintf(
                '<audio controls preload="none" src="%s">%s</audio>',
                esc_url($url),
                __('No support for audio player', 'codepress-admin-columns')
            );
        }

        if (strpos($mime_type, 'image/') === 0) {
            $image = Helper\Image::create()->get_image_by_id($attachment_id, $this->size);

            if ($image !== null) {
                return $image;
            }
        }

        return Helper\Html::create()->link(
            $url,
            esc_html(basename($attachment)),
            ['target' => '_blank']
        );
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
