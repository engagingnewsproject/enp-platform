<?php

namespace ACP\Formatter\NetworkSite;

use AC\Formatter;
use AC\Type\Value;
use WP_Theme;

class Themes implements Formatter
{

    private string $theme_status;

    public function __construct(string $theme_status)
    {
        $this->theme_status = $theme_status;
    }

    public function format(Value $value): Value
    {
        $blog_id = $value->get_id();
        $active_theme = ac_helper()->network->get_active_theme($blog_id);

        switch ($this->theme_status) {
            case 'active' :
                $themes = [$active_theme];

                break;
            case 'allowed' :
                $themes = wp_get_themes(['blog_id' => $blog_id, 'allowed' => 'site']);

                break;
            case 'available' :
                $themes = wp_get_themes(['blog_id' => $blog_id, 'allowed' => true]);

                break;
            default:
                $themes = [];
        }

        // Add Tooltip
        foreach ($themes as $k => $theme) {
            $tooltip = [];

            /* @var WP_Theme $theme */
            if ($theme->get_stylesheet() === $active_theme->get_stylesheet()) {
                $tooltip[] = __('Active', 'codepress-admin-columns');
            }

            if ($theme->is_allowed('network', $blog_id)) {
                $tooltip[] = __('Network Enabled', 'codepress-admin-columns');
            } elseif ($theme->is_allowed('site', $blog_id)) {
                $tooltip[] = __('Site Enabled', 'codepress-admin-columns');
            }

            unset($themes[$k]);

            $themes[$theme->get_stylesheet()] = ac_helper()->html->tooltip(
                $theme->get('Name'),
                implode(' | ', $tooltip)
            );
        }

        natcasesort($themes);

        $active_stylesheet = $active_theme->get_stylesheet();

        if (isset($themes[$active_stylesheet]) && count($themes) > 1) {
            // Active first
            $theme = [$active_stylesheet => $themes[$active_stylesheet]];
            unset($themes[$active_stylesheet]);
            $themes = $theme + $themes;

            // Suffix with active
            $themes[$active_stylesheet] = '<strong>' . $themes[$active_stylesheet] . '</strong>';
        }

        return $value->with_value(implode("<br>", $themes));
    }

}