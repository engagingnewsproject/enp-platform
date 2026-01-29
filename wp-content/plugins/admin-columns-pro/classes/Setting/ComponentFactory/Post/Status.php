<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory\Post;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class Status extends BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Post Status', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'post_status',
            $this->get_post_statuses(),
            $config->get('post_status', '')
        );
    }

    private function get_post_statuses(): OptionCollection
    {
        $options = [
            '' => __('Any', 'codepress-admin-columns'),
        ];

        foreach (get_post_stati(['show_in_admin_status_list' => true]) as $name) {
            $options[(string)$name] = sprintf('%s (%s)', get_post_status_object($name)->label, $name);
        }

        natcasesort($options);

        return OptionCollection::from_array($options);
    }
}