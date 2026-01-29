<?php

declare(strict_types=1);

namespace ACP\Admin\ScriptFactory;

use AC\Asset;
use AC\Asset\Script;
use ACP\AdminColumnsPro;
use ACP\Settings\Option;

class GeneralSettingsFactory
{

    public const HANDLE = 'acp-page-settings';

    private $location;

    private Option\LayoutStyle $layout_style;

    public function __construct(AdminColumnsPro $plugin, Option\LayoutStyle $layout_style)
    {
        $this->location = $plugin->get_location();
        $this->layout_style = $layout_style;
    }

    public function create(): Script
    {
        $script = new Asset\Script(
            self::HANDLE,
            $this->location->with_suffix('assets/core/js/admin-page-settings.js'),
            [
                'ac-admin-page-settings',
                Script\GlobalTranslationFactory::HANDLE,
            ]
        );

        $script->add_inline_variable('ACP_SETTINGS', [
            'view_style_value'   => $this->layout_style->get_style(),
            'view_style_options' => [
                [
                    'value' => Option\LayoutStyle::OPTION_TABS,
                    'label' => _x('Tabs', 'table view display', 'codepress-admin-columns'),
                ],
                [
                    'value' => Option\LayoutStyle::OPTION_DROPDOWN,
                    'label' => _x('Dropdown', 'table view display', 'codepress-admin-columns'),
                ],
            ],
        ]);

        $script->localize('acp_settings_i18n', new Script\Localize\Translation([
            'sorting_preference'             => __('Sorting Preference', 'codepress-admin-columns'),
            'sorting_preference_description' => __(
                'Reset the sorting preference for all users.',
                'codepress-admin-columns'
            ),
            'reset'                          => __('Reset', 'codepress-admin-columns'),
            'view_selector'                  => __('View Selector', 'codepress-admin-columns'),
            'view_selector_description'      => __(
                'Select the style for the "Table View" selector on the table screen.',
                'codepress-admin-columns'
            ),
        ]));

        return $script;
    }

}