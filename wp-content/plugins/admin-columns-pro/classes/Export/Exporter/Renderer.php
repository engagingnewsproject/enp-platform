<?php

declare(strict_types=1);

namespace ACP\Export\Exporter;

use AC\Column\Context;
use AC\Formatter;
use AC\Formatter\Aggregate;
use AC\Formatter\Collection\Implode;
use AC\FormatterCollection;
use AC\TableScreen;
use AC\Type\Value;
use AC\Type\ValueCollection;
use ACP\Export;
use ACP\Export\Formatter\ExportFilter;

class Renderer implements Formatter
{

    private TableScreen $table_screen;

    private FormatterCollection $formatters;

    private Context $context;

    private ?Export\Formatter\EscapeData $escaper;

    public function __construct(
        FormatterCollection $formatters,
        Context $context,
        TableScreen $table_screen,
        ?Export\Formatter\EscapeData $escaper = null
    ) {
        $this->table_screen = $table_screen;
        $this->formatters = $formatters;
        $this->context = $context;
        $this->escaper = $escaper;
    }

    /**
     * Formats a single cell value for export by running it through the pipeline:
     *  1. Aggregate: runs the configured FormatterCollection in order, feeding each formatter's
     *     output into the next one. This is the same pattern used for on-screen rendering.
     *  2. Implode: when the pipeline produces a ValueCollection (e.g. multiple taxonomy terms,
     *     repeater fields), collapse it into a single delimited string so the result fits one
     *     CSV cell.
     *  3. ExportFilter: exposes the 'ac/export/value' hook so integrators can override the final
     *     value based on column context and table screen.
     *  4. Escape: optionally applies CSV-injection protection (see constructor).
     */
    public function format(Value $value): Value
    {
        $value = (new Aggregate($this->formatters))->format($value);

        if ($value instanceof ValueCollection) {
            $value = (new Implode())->format($value);
        }

        $formatter = new ExportFilter(
            $this->context,
            $this->table_screen
        );

        $value = $formatter->format($value);

        if ($this->escaper) {
            $value = $this->escaper->format($value);
        }

        return $value;
    }

}