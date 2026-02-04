<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory;

use AC;
use AC\Expression\StringComparisonSpecification;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\Control\OptionCollection;

class UserProperty extends AC\Setting\ComponentFactory\UserProperty
{

    public const PROPERTY_CUSTOM_FIELD = 'custom_field';

    private AC\Setting\ComponentFactory\RelatedUserMetaField $user_meta_field;

    public function __construct(AC\Setting\ComponentFactory\RelatedUserMetaField $user_meta_field)
    {
        $this->user_meta_field = $user_meta_field;
    }

    protected function get_input_options(): OptionCollection
    {
        $options = parent::get_input_options();

        $options->add(
            new AC\Setting\Control\Type\Option(
                __('Custom Field', 'codepress-admin-columns'),
                self::PROPERTY_CUSTOM_FIELD,
                __('Other', 'codepress-admin-columns')
            )
        );

        return $options;
    }

    protected function add_formatters(Config $config, FormatterCollection $formatters): void
    {
        if ($config->get('display_author_as') === self::PROPERTY_CUSTOM_FIELD) {
            $formatters->add(new AC\Formatter\User\Meta($config->get('field', '')));

            return;
        }

        parent::add_formatters($config, $formatters);
    }

    protected function get_children_component_collection(Config $config): ComponentCollection
    {
        $collection = parent::get_children_component_collection($config);

        $collection->add(
            $this->user_meta_field->create($config, StringComparisonSpecification::equal(self::PROPERTY_CUSTOM_FIELD))
        );

        return $collection;
    }

}