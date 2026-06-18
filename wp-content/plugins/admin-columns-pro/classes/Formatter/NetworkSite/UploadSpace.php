<?php

namespace ACP\Formatter\NetworkSite;

use AC\Formatter;
use AC\Type\Value;
use AC\View;

class UploadSpace implements Formatter
{

    public function format(Value $value): Value
    {
        switch_to_blog($value->get_id());

        $used = get_space_used();
        $quota = get_space_allowed();

        restore_current_blog();

        $display_used = '&ndash;';
        $display_quota = '';

        $has_upload_restrictions = $this->upload_restrictions();

        if ($used) {
            $display_used = sprintf(
                __('%s MB', 'codepress-admin-columns'),
                $this->trim_zeros(number_format_i18n($used, 2))
            );
        }

        if ($has_upload_restrictions) {
            $display_quota = sprintf(__('%s MB', 'codepress-admin-columns'), number_format_i18n($quota));
        } else {
            $display_used .= ' / &#x221e;'; // infinitive symbol
        }

        $percentused = 0;

        if ($has_upload_restrictions) {
            if ($used > $quota) {
                $percentused = 100;
            } elseif ($quota) {
                $percentused = round(($used / $quota) * 100);
            }
        }

        $class = '';
        if ($percentused >= 70) {
            $class = ' warning';
        }
        if ($percentused >= 100) {
            $class = ' full';
        }

        if ($percentused) {
            $display_used .= ' (' . $percentused . '%)';
        }

        $view = new View([
            'attr_class'                => $class,
            'has_storage_restrictions'  => $has_upload_restrictions,
            'storage_in_use_absolute'   => $display_used,
            'storage_in_use_percentage' => $percentused,
            'storage_max'               => $display_quota,
        ]);

        return $value->with_value(
            $view->set_template('column/upload-space')->render()
        );
    }

    private function upload_restrictions(): bool
    {
        return '1' !== get_site_option('upload_space_check_disabled');
    }

    private function trim_zeros(string $number): string
    {
        global $wp_locale;

        $decimal_separator = '.';

        if ($wp_locale) {
            $decimal_separator = $wp_locale->number_format['decimal_point'];
        }

        return preg_replace('/' . preg_quote($decimal_separator, '/') . '0++$/', '', $number);
    }

}