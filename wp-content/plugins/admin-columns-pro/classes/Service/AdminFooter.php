<?php

declare(strict_types=1);

namespace ACP\Service;

use AC\Registerable;
use AC\Screen;
use AC\Type\Url\Documentation;
use AC\Type\Url\Site;
use ACP\AdminColumnsPro;

class AdminFooter implements Registerable
{

    private AdminColumnsPro $plugin;

    public function __construct(AdminColumnsPro $plugin)
    {
        $this->plugin = $plugin;
    }

    public function register(): void
    {
        add_action('ac/screen', [$this, 'init']);
    }

    public function init(Screen $screen): void
    {
        if ( ! $screen->is_admin_screen()) {
            return;
        }

        add_filter('update_footer', [$this, 'render'], 11);
    }

    public function render($html)
    {
        global $current_screen;

        $urls = [
            sprintf(
                '<a href="%s" style="font-style:italic" target="_blank">%s</a>',
                new Documentation(),
                __('Documentation', 'codepress-admin-columns')
            ),
            sprintf(
                '<a href="%s" style="font-style:italic" target="_blank">%s</a>',
                Site::create_support(),
                __('Support', 'codepress-admin-columns')
            ),
            sprintf(
                '<a href="%s" style="font-style:italic" target="_blank">%s %s</a>',
                Site::create_changelog(),
                __('Admin Columns Pro', 'codepress-admin-columns'),
                $this->plugin->get_version()
            ),
        ];

        return implode(' &#8729 ', $urls);
    }

}