<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field;

use ACA\JetEngine\Mapping;
use Jet_Engine\Meta_Boxes\Option_Sources\Manual_Bulk_Options;

trait ManualBulkOptionsTrait
{

    public function has_manual_bulk_options(): bool
    {
        return isset($this->settings['options_source']) && $this->settings['options_source'] === 'manual_bulk';
    }

    public function get_manual_bulk_options(): array
    {
        if ( ! class_exists(Manual_Bulk_Options::class)) {
            return [];
        }

        $option_helper = new Manual_Bulk_Options();

        return Mapping\Options::from_field_options($option_helper->parse_options($this->settings));
    }

}