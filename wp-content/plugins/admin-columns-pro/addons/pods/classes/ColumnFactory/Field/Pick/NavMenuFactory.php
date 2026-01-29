<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field\Pick;

use AC\Formatter\Term\TermProperty;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\Pods\Editing;
use ACA\Pods\Value\Formatter\IdCollection;
use ACA\Pods\Value\Formatter\PodsFieldRaw;
use ACP;
use ACP\Editing\View;

class NavMenuFactory extends BasePickFactory
{

    protected function get_options(): array
    {
        $menus = get_terms('nav_menu', ['hide_empty' => false]);

        if (empty($menus) || is_wp_error($menus)) {
            return [];
        }

        $options = [];

        foreach ($menus as $menu) {
            $options[$menu->term_id] = $menu->name;
        }

        return $options;
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\FieldStorage(
            new Editing\Storage\Field(
                $this->field,
                new Editing\Storage\Read\DbRaw($this->field->get_name(), $this->field->get_meta_type())
            ),
            (new View\AdvancedSelect())
                ->set_options($this->get_options())
                ->set_multiple($this->is_multiple())
        );
    }

    protected function get_base_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new PodsFieldRaw($this->field),
            new IdCollection('term_id'),
            new TermProperty('name'),
        ]);
    }

}