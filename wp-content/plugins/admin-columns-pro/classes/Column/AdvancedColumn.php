<?php

declare(strict_types=1);

namespace ACP\Column;

use AC\Column\Base;
use AC\Column\Context;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Type\ColumnId;
use ACP\Column;
use ACP\ConditionalFormat\FormattableConfig;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting\Model\QueryBindings;

final class AdvancedColumn extends Base implements Column
{

    private ?Editing\Service $editing;

    private ?Search\Comparison $search;

    private ?FormatterCollection $export;

    private ?FormattableConfig $conditional_format;

    private ?QueryBindings $sorting;

    public function __construct(
        string $type,
        string $label,
        ComponentCollection $settings,
        ColumnId $id,
        Context $context,
        ?FormatterCollection $formatters = null,
        ?string $group = null,
        ?QueryBindings $sorting = null,
        ?Editing\Service $editing = null,
        ?Search\Comparison $search = null,
        ?FormatterCollection $export = null,
        ?FormattableConfig $conditional_format = null
    ) {
        parent::__construct($type, $label, $settings, $id, $context, $formatters, $group);

        $this->editing = $editing;
        $this->search = $search;
        $this->export = $export;
        $this->conditional_format = $conditional_format;
        $this->sorting = $sorting;
    }

    public function sorting(): ?QueryBindings
    {
        return $this->sorting;
    }

    public function editing(): ?Editing\Service
    {
        return $this->editing;
    }

    public function search(): ?Search\Comparison
    {
        return $this->search;
    }

    public function export(): ?FormatterCollection
    {
        return $this->export;
    }

    public function conditional_format(): ?FormattableConfig
    {
        return $this->conditional_format;
    }

}