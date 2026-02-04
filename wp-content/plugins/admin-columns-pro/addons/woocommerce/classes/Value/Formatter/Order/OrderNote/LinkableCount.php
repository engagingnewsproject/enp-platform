<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order\OrderNote;

use AC\CollectionFormatter;
use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use AC\Type\ValueCollection;
use ACA\WC\Value\ExtendedValue\Order\Notes;

class LinkableCount implements CollectionFormatter
{

    private Notes $extended_value;

    private string $note_type;

    public function __construct(Notes $extended_value, string $note_type = '')
    {
        $this->extended_value = $extended_value;
        $this->note_type = $note_type;
    }

    public function format(ValueCollection $value): Value
    {
        $count = $value->count();

        if ($count === 0) {
            throw ValueNotFoundException::from_id($value->get_id());
        }
        $label = sprintf(_n('%d note', '%d notes', $count, 'codepress-admin-columns'), $count);

        $link = $this->extended_value
            ->get_link($value->get_id(), $label)
            ->with_params(['type' => $this->note_type]);

        return new Value(
            $link->render()
        );
    }

}