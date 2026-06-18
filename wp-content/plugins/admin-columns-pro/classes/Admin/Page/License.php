<?php

declare(strict_types=1);

namespace ACP\Admin\Page;

use AC\Admin\RenderableHead;
use AC\Asset;
use AC\Asset\Assets;
use AC\Asset\Style;
use AC\Renderable;
use ACP;
use ACP\AdminColumnsPro;
use ACP\Type\Url\AccountFactory;

class License implements Asset\Enqueueables, Renderable, RenderableHead
{

    public const NAME = 'license';

    private Renderable $head;

    private AdminColumnsPro $plugin;

    private AccountFactory $url_factory;

    public function __construct(
        Renderable $head,
        AdminColumnsPro $plugin,
        AccountFactory $url_factory

    ) {
        $this->head = $head;
        $this->plugin = $plugin;
        $this->url_factory = $url_factory;
    }

    public function render_head(): Renderable
    {
        return $this->head;
    }

    public function get_assets(): Assets
    {
        return new Assets([
            new Style(
                'acp-license-manager',
                $this->plugin->get_location()->with_suffix('assets/core/css/license-manager.css')
            ),
            new ACP\Asset\Script\LicenseManager(
                $this->plugin->get_location()->with_suffix('assets/core/js/admin-page-license.js'),
                $this->plugin->is_network_active(),
                $this->url_factory
            ),
        ]);
    }

    public function render(): string
    {
        return '';
    }

}