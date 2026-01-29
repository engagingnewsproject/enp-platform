<?php

declare(strict_types=1);

namespace ACA\GravityForms\ColumnFactory;

use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\GravityForms\Editing\EntryServiceFactory;
use ACA\GravityForms\Export;
use ACA\GravityForms\Field\Field;
use ACA\GravityForms\Search;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class EntryFactory extends ACP\Column\AdvancedColumnFactory
{

    private string $type;

    private string $label;

    private Field $field;

    private Search\Comparison\EntryFactory $search_factory;

    private EntryServiceFactory $editing_factory;

    private Export\Formatter\EntryFactory $export_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        Search\Comparison\EntryFactory $search_factory,
        EntryServiceFactory $editing_factory,
        Export\Formatter\EntryFactory $export_factory,
        string $type,
        string $label,
        Field $field
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->search_factory = $search_factory;
        $this->editing_factory = $editing_factory;
        $this->export_factory = $export_factory;
        $this->type = $type;
        $this->label = $label;
        $this->field = $field;
    }

    protected function get_group(): ?string
    {
        return 'default';
    }

    public function get_column_type(): string
    {
        return $this->type;
    }

    public function get_label(): string
    {
        return $this->label;
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return $this->search_factory->create($this->field);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return $this->editing_factory->create($this->field);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return $this->export_factory->create($this->field);
    }

}