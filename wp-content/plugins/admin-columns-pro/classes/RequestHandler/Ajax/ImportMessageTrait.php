<?php

namespace ACP\RequestHandler\Ajax;

use AC\ListScreenCollection;

trait ImportMessageTrait
{

    public function create_success_message(ListScreenCollection $list_screens): string
    {
        $grouped = [];

        foreach ($list_screens as $list_screen) {
            $grouped[$list_screen->get_label()][] = sprintf(
                '<a href="%s"><strong>%s</strong></a>',
                esc_url((string)$list_screen->get_editor_url()),
                esc_html($list_screen->get_title())
            );
        }

        $messages = [];

        foreach ($grouped as $label => $links) {
            $messages[] = sprintf(
                __('Successfully imported %s for %s.', 'codepress-admin-columns'),
                ac_helper()->string->enumeration_list($links, 'and'),
                "<strong>" . $label . "</strong>"
            );
        }

        return implode('<br>', $messages);
    }

}