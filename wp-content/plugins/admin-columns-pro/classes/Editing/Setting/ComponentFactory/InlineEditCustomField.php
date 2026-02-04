<?php

namespace ACP\Editing\Setting\ComponentFactory;

use AC;
use AC\Setting\Config;

class InlineEditCustomField extends InlineEdit
{

    protected function get_description(Config $config): ?string
    {
        return sprintf(
            '<p class="help-msg">%s</p>',
            sprintf(
                __('Learn more about %s.', 'codepress-admin-columns'),
                sprintf(
                    '<a target="_blank" href="%s#%s">%s</a>',
                    esc_url(
                        AC\Type\Url\Documentation::create_with_path(
                            AC\Type\Url\Documentation::ARTICLE_CUSTOM_FIELD_EDITING
                        )
                    ),
                    'formats',
                    __('custom field save formats', 'codepress-admin-columns')
                )
            )
        );
    }

}