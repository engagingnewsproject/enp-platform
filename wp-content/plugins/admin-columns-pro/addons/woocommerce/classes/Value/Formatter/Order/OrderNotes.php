<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class OrderNotes implements Formatter
{

    public const SYSTEM_NOTE = 'system';
    public const PRIVATE_NOTE = 'private';
    public const CUSTOMER_NOTE = 'customer';

    private $type;

    public function __construct(?string $type = null)
    {
        $this->type = $type;
    }

    private function filter_notes(array $notes): array
    {
        switch ($this->type) {
            case 'system':
                return array_filter($notes, [$this, 'is_system_note']);
            case 'customer':
                return array_filter($notes, [$this, 'is_customer_note']);
            case 'private':
                return array_filter($notes, [$this, 'is_private_note']);
            default:
                return $notes;
        }
    }

    private function is_private_note($note): bool
    {
        return ! $this->is_customer_note($note) && ! $this->is_system_note($note);
    }

    private function is_system_note($note): bool
    {
        return 'system' === $note->added_by;
    }

    private function is_customer_note($note): bool
    {
        return (bool)$note->customer_note;
    }

    public function format(Value $value)
    {
        $args = [
            'order_id' => $value->get_id(),
        ];

        $notes = wc_get_order_notes($args);

        if (empty($notes)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $notes = $this->filter_notes($notes);
        $collection = new ValueCollection($value->get_id());

        foreach ($notes as $note) {
            $collection->add(
                new Value(
                    $note->id,
                    $note
                )
            );
        }

        return $collection;
    }

}