<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Helper;
use AC\Type\Value;

class Iconfy implements Formatter
{

    private string $dashicon;

    private string $class;

    public function __construct(string $dashicon = 'media-text', string $class = 'gray')
    {
        $this->dashicon = $dashicon;
        $this->class = $class;
    }

    public function format(Value $value)
    {
        if ( ! $value->get_value()) {
            throw ValueNotFoundException::from_id($value->get_id());
        }
        $icon = Helper\Icon::create()->dashicon(['icon' => $this->dashicon, 'class' => $this->class]);

        return $value->with_value(Helper\Html::create()->tooltip($icon, $value->get_value()));
    }

}