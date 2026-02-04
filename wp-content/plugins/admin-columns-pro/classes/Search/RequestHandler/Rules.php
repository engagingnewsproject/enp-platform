<?php

namespace ACP\Search\RequestHandler;

use AC;
use AC\Type\ColumnId;
use ACP\Column;
use ACP\Query\QueryRegistry;
use ACP\Search\Value;
use LogicException;

/**
 * Handles rules request. Converts the request to a QueryBinding and registers it with WordPress.
 */
class Rules
{

    use AC\Column\ColumnLabelTrait;

    private AC\ListScreen $list_screen;

    public function __construct(AC\ListScreen $list_screen)
    {
        $this->list_screen = $list_screen;
    }

    public function handle(AC\Request $request): void
    {
        $rules = $request->filter('ac-rules', [], FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

        if ( ! $rules) {
            return;
        }

        $bindings = [];

        foreach ($rules as $rule) {
            $column = $this->list_screen->get_column(new ColumnId((string)$rule['name']));

            if ( ! $column instanceof Column) {
                continue;
            }

            $comparison = $column->search();

            if ( ! $comparison) {
                continue;
            }

            // Skip unsupported operators
            if (false === $comparison->get_operators()->search($rule['operator'])) {
                continue;
            }

            try {
                $bindings[] = $comparison->get_query_bindings(
                    $rule['operator'],
                    new Value($rule['value'], $comparison->get_value_type())
                );
            } catch (LogicException $e) {
                // Error message
                $message = sprintf(
                    __('Smart filter for %s could not be applied.', 'codepress-admin-columns'),
                    sprintf('<strong>%s</strong>', $this->get_column_label($column))
                );
                $message = sprintf('%s %s', $message, __('Try to re-apply the filter.', 'codepress-admin-columns'));

                (new AC\Message\Notice($message))
                    ->set_type(AC\Message::WARNING)
                    ->register();

                continue;
            }
        }

        QueryRegistry::create(
            $this->list_screen->get_table_screen(),
            $bindings
        )->register();
    }

}