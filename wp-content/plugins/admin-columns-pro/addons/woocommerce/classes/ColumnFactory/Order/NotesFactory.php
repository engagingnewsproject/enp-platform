<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\Order\NoteProperty;
use ACA\WC\Setting\ComponentFactory\Order\NoteType;
use ACA\WC\Sorting;
use ACA\WC\Value\ExtendedValue\Order\Notes;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class NotesFactory extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    private NoteType $note_type;

    private NoteProperty $note_property;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        NoteType $note_type,
        NoteProperty $note_property
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->note_type = $note_type;
        $this->note_property = $note_property;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->note_type->create($config))
                     ->add($this->note_property->create($config));
    }

    public function get_label(): string
    {
        return __('Order Notes', 'woocommerce');
    }

    public function get_column_type(): string
    {
        return 'column-order_note';
    }

    private function get_note_type(Config $config): string
    {
        return $config->get(NoteType::NAME, '');
    }

    private function get_note_display(Config $config): string
    {
        return $config->get(NoteProperty::NAME, NoteProperty::PROPERTY_COUNT);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config)
                            ->add(new Formatter\Order\OrderNotes($this->get_note_type($config)))
                            ->add(new Formatter\Order\OrderNote\NoteDisplay());

        if ($this->get_note_display($config) === NoteProperty::PROPERTY_COUNT) {
            $formatters->add(
                new Formatter\Order\OrderNote\LinkableCount(
                    new Notes()
                )
            );
        }

        return $formatters;
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        switch ($this->get_note_type($config)) {
            case NoteType::PRIVATE_NOTE :
                return new Editing\ShopOrder\NotesPrivate();
            case NoteType::CUSTOMER_NOTE :
                return new Editing\ShopOrder\NotesToCustomer();
            case NoteType::SYSTEM_NOTE :
                return new Editing\ShopOrder\NotesSystem();
            default:
                return null;
        }
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        switch ($this->get_note_type($config)) {
            case NoteType::PRIVATE_NOTE :
                return new Search\Order\Notes\PrivateNotes();
            case NoteType::CUSTOMER_NOTE :
                return new Search\Order\Notes\CustomerNotes();
            case NoteType::SYSTEM_NOTE :
                return new Search\Order\Notes\SystemNotes();
            default:
                return null;
        }
    }

}