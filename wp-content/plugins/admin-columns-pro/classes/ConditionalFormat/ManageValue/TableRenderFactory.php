<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\ManageValue;

use AC;
use AC\Formatter;
use AC\Formatter\Aggregate;
use AC\FormatterCollection;
use AC\ListScreen;
use AC\Type\ColumnId;
use ACP;
use ACP\ConditionalFormat\Entity\Rules;
use ACP\ConditionalFormat\Formatter\TableRender;
use ACP\ConditionalFormat\Operators;

class TableRenderFactory extends AC\Table\ManageValue\TableRenderFactory
{

    private ListScreen $list_screen;

    private Operators $operators;

    private Rules $rules;

    public function __construct(
        ListScreen $list_screen,
        Operators $operators,
        Rules $rules
    ) {
        parent::__construct($list_screen);

        $this->list_screen = $list_screen;
        $this->operators = $operators;
        $this->rules = $rules;
    }

    public function create(ColumnId $columnId): ?Formatter
    {
        $column = $this->list_screen->get_column($columnId);

        if ( ! $column) {
            return null;
        }

        $formatter = parent::create($columnId);

        if ( ! $formatter) {
            return null;
        }

        if ( ! $column instanceof ACP\Column) {
            return $formatter;
        }

        $formatters = new FormatterCollection();

        $formatters->add($formatter);
        $formatters->add(new TableRender($column, $this->operators, $this->rules));

        return new Aggregate($formatters);
    }

}