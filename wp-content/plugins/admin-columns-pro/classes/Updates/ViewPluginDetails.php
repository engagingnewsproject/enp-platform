<?php

namespace ACP\Updates;

use AC\Registerable;
use ACP\AdminColumnsPro;
use ACP\API\Request\ProductInformation;
use ACP\ApiFactory;

/**
 * Show changelog when "click view details".
 */
class ViewPluginDetails implements Registerable
{

    private AdminColumnsPro $plugin;

    private ApiFactory $api_factory;

    public function __construct(AdminColumnsPro $plugin, ApiFactory $api_factory)
    {
        $this->plugin = $plugin;
        $this->api_factory = $api_factory;
    }

    public function register(): void
    {
        add_filter('plugins_api', [$this, 'get_plugin_information'], 10, 3);
    }

    public function get_plugin_information($result, $action, $args)
    {
        if ('plugin_information' !== $action) {
            return $result;
        }

        $slug = $this->plugin->get_dirname();

        if ($slug !== $args->slug) {
            return $result;
        }

        $response = $this->api_factory->create()->dispatch(
            new ProductInformation($slug)
        );

        if ($response->has_error()) {
            return $response->get_error();
        }

        return $response->get_body();
    }

}