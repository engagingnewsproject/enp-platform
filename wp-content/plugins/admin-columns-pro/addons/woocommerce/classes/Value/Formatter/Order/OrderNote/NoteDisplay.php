<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order\OrderNote;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use DateTime;

class NoteDisplay implements Formatter
{

    public function format(Value $value)
    {
        $note = $value->get_value();

        if ( ! property_exists($note, 'content') || ! property_exists($note, 'id')) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $message = sprintf(
            '<small>%s</small><br>%s',
            $note->date_created instanceof DateTime ? $note->date_created->format('F j, Y - H:i') : '',
            $note->content
        );

        return $value->with_value($message);
    }

}