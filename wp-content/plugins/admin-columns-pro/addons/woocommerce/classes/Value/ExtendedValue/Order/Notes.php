<?php

declare(strict_types=1);

namespace ACA\WC\Value\ExtendedValue\Order;

use AC;
use AC\Column;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;

class Notes implements ExtendedValue
{

    private const NAME = 'order-notes';

    public function can_render(string $view): bool
    {
        return $view === self::NAME;
    }

    public function render($id, array $params, Column $column, ListScreen $list_screen): string
    {
        $order = wc_get_order($id);

        if ( ! $order) {
            return __('No order found', 'codepress-admin-columns');
        }

        $items = [];
        foreach ($this->get_order_notes($id, (string)$params['type']) as $note) {
            $type = $class = null;

            if ($this->is_customer_note($note)) {
                $class = '-customer-note';
                $type = __('Note To Customer', 'codepress-admin-columns');
            } elseif ($this->is_system_note($note)) {
                $class = '-system-note';
                $type = __('System', 'codepress-admin-columns');
            } elseif ($this->is_private_note($note)) {
                $class = '-private-note';
                $type = __('Private', 'codepress-admin-columns');
            }

            $items[] = [
                'date'  => $note->date_created->format('F j, Y - H:i'),
                'note'  => $note->content,
                'type'  => $type,
                'class' => $class,
            ];
        }

        if (empty($items)) {
            return __('No notes found', 'codepress-admin-columns');
        }

        $view = new AC\View([
            'items' => $items,
        ]);

        return $view->set_template('modal-value/order-notes')->render();
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

    private function get_order_notes(int $order_id, string $note_type): array
    {
        $notes = wc_get_order_notes([
            'order_id' => $order_id,
        ]);

        switch ($note_type) {
            case 'customer':
                return array_filter($notes, [$this, 'is_customer_note']);
            case 'private':
                return array_filter($notes, [$this, 'is_private_note']);
            case 'system':
                return array_filter($notes, [$this, 'is_system_note']);
            default :
                return $notes;
        }
    }

    public function get_link($id, string $label): ExtendedValueLink
    {
        return (new ExtendedValueLink($label, $id, self::NAME))
            ->with_class('-nopadding -w-large');
    }

}