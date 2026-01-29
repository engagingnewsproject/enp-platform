<?php

declare(strict_types=1);

namespace ACP\Service\Storage;

use AC\Form\PreviewNonce;
use AC\ListScreen;
use AC\Message;
use AC\Message\Notice;
use AC\Registerable;
use AC\Request;
use AC\TableScreen;
use ACP\ListScreenRepository\TemplateJsonFile;
use ACP\Request\Middleware\TemplatePreview;

final class Template implements Registerable
{

    private TemplateJsonFile $template_storage;

    public function __construct(TemplateJsonFile $template_storage)
    {
        $this->template_storage = $template_storage;
    }

    public function register(): void
    {
        add_action('ac/table/request', [$this, 'modify_table_request'], 10, 2);
        add_action('ac/table/list_screen', [$this, 'register_preview_notice']);
    }

    public function modify_table_request(Request $request, TableScreen $table_screen): void
    {
        $request->add_middleware(
            new TemplatePreview($this->template_storage, $table_screen)
        );
    }

    public function register_preview_notice(ListScreen $list_screen): void
    {
        if ( ! $this->template_storage->exists($list_screen->get_id())) {
            return;
        }

        $request = new Request();
        $nonce_preview = new PreviewNonce();

        $url = $list_screen->get_editor_url()
                           ->with_arg('tab', $request->get('source_tab', 'import-export'))
                           ->with_arg($nonce_preview->get_name(), $nonce_preview->create())
                           ->with_arg('menu', 'templates');

        $message = sprintf(
            '%s %s',
            sprintf(
                __('This is a preview of %s.', 'codepress-admin-columns'),
                "<strong>" . esc_html($list_screen->get_title()) . "</strong>"
            ),
            sprintf(
                '<a href="%s">%s</a>',
                esc_url($url->get_url()),
                __('Leave preview mode', 'codepress-admin-columns')
            )
        );

        $notice = new Notice(
            $message,
            Message::INFO
        );
        $notice->set_id('table-view-preview')
               ->register();
    }

}