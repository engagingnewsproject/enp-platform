<?php

namespace ACP\Column\Media;

use AC;
use ACP\ConditionalFormat;
use ACP\Export;

class Orientation extends AC\Column\Media\Meta implements Export\Exportable
{

    use ConditionalFormat\ConditionalFormatTrait;

    public function __construct()
    {
        $this->set_type('column-orientation')
             ->set_group('media')
             ->set_label(__('Orientation', 'codepress-admin-columns'));
    }

    public function get_value($id)
    {
        $meta_data = $this->get_raw_value($id);
        $width = $meta_data['width'] ?? null;
        $height = $meta_data['height'] ?? null;

        if ( ! $width || ! $height) {
            return $this->get_empty_char();
        }

        if ($height === $width) {
            return _x('Square', 'image orientation', 'codepress-admin-columns');
        }

        return $width > $height
            ? _x('Landscape', 'image orientation', 'codepress-admin-columns')
            : _x('Portrait', 'image orientation', 'codepress-admin-columns');
    }

    public function export()
    {
        return new Export\Model\Value($this);
    }

}