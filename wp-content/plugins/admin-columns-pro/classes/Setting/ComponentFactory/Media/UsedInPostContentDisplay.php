<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory\Media;

use AC;
use AC\Setting\Config;
use AC\Setting\Control\Input;

class UsedInPostContentDisplay extends AC\Setting\ComponentFactory\BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Display', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'used_in_post_content_display',
            AC\Setting\Control\OptionCollection::from_array([
                'true_false' => __('True / False', 'codepress-admin-columns'),
                'count'      => _x('Count', 'Number/count of items', 'codepress-admin-columns'),
            ]),
            $config->get('used_in_post_content_display', 'true_false')
        );
    }

}
