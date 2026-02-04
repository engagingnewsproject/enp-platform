<?php

declare(strict_types=1);

namespace ACP\Table\ManageValue;

use AC;
use AC\TableScreen\ManageValueService;
use AC\Type\ColumnId;
use AC\Type\TaxonomySlug;
use AC\Type\Value;
use DomainException;

class Taxonomy implements ManageValueService
{

    private TaxonomySlug $taxonomy;

    private AC\Table\ManageValue\RenderFactory $factory;

    private int $priority;

    public function __construct(
        TaxonomySlug $taxonomy,
        AC\Table\ManageValue\RenderFactory $factory,
        int $priority = 100
    ) {
        $this->taxonomy = $taxonomy;
        $this->factory = $factory;
        $this->priority = $priority;
    }

    /**
     * @see WP_Terms_List_Table::column_default
     */
    public function register(): void
    {
        $action = sprintf("manage_%s_custom_column", $this->taxonomy);

        if (did_filter($action)) {
            throw new DomainException("Method should be called before the %s action.", $action);
        }

        add_filter($action, [$this, 'render_value'], $this->priority, 3);
    }

    public function render_value(...$args)
    {
        [$value, $column_id, $row_id] = $args;

        $formatter = $this->factory->create(new ColumnId((string)$column_id));

        return $formatter
            ? (string)$formatter->format(new Value((int)$row_id))
            : $value;
    }

}