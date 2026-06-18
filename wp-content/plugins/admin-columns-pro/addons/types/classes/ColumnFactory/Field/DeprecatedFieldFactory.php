<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactory\Field;

use AC\Column;
use AC\Column\Base;
use AC\Column\ColumnFactory;
use AC\Column\ColumnIdGenerator;
use AC\Setting;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\Message;
use AC\Setting\Config;
use AC\Type\Url\Documentation;

final class DeprecatedFieldFactory extends ColumnFactory
{

    private Setting\ComponentFactory\Name $name_factory;

    private Setting\ComponentFactory\HiddenLabel $label_factory;

    public function __construct(
        Setting\ComponentFactory\Name $name_factory,
        Setting\ComponentFactory\HiddenLabel $label_factory
    ) {
        $this->name_factory = $name_factory;
        $this->label_factory = $label_factory;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        $components = new ComponentCollection([
            $this->name_factory->create($config),
            $this->label_factory->create($config),
            (new Message('', $this->get_message($config)))->create($config),
        ]);

        foreach ($config->all() as $key => $value) {
            $components->add(
                (new Setting\ComponentFactory\HiddenInput($key))->create($config)
            );
        }

        return $components;
    }

    private function get_message(Config $config): string
    {
        $url = new Documentation(Documentation::ARTICLE_UPGRADE_V6_TO_V7);

        return sprintf(
            '<strong>%s</strong><br>%s<br/><br/><strong>Field</strong>: %s',
            'This Types column could not be updated from V6 to V7 version.',
            sprintf(
                __('Read more about this in %s.'),
                sprintf('<a target="_blank" href="%s">%s</a>', $url, __('our documentation', 'codepress-admin-column'))
            ),
            $config->get('types_field', '')
        );
    }

    public function get_column_type(): string
    {
        return 'column-types';
    }

    public function get_label(): string
    {
        return 'column-types';
    }

    public function create(Config $config): Column
    {
        $column_id_generator = new ColumnIdGenerator();

        return new Base(
            $this->get_column_type(),
            $this->get_label(),
            $this->get_settings($config),
            $column_id_generator->from_config($config),
            $this->get_context($config),
            $this->get_formatters($config),
            'custom'
        );
    }

}