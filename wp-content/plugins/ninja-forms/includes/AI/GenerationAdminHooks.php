<?php

namespace NinjaForms\Includes\AI;

/**
 * Dashboard integration and provider-cache warming for AI generation.
 */
class GenerationAdminHooks
{
    /** @var int Maximum prompt characters. */
    private int $promptLimit;

    /** @var bool|null Memoized provider availability. */
    private ?bool $available = null;

    /**
     * Initialize the dashboard hook subscriber.
     *
     * @param int $promptLimit Maximum prompt characters.
     */
    public function __construct(int $promptLimit)
    {
        $this->promptLimit = $promptLimit;
    }

    /**
     * Register dashboard and connector hooks.
     *
     * @return void
     */
    public function register(): void
    {
        add_filter('ninja_forms_new_form_templates', array($this, 'addTemplate'), 10, 1);
        add_action('added_option', array($this, 'flushProviderCache'), 10, 1);
        add_action('updated_option', array($this, 'flushProviderCache'), 10, 1);
        add_action('deleted_option', array($this, 'flushProviderCache'), 10, 1);
        add_action('wp_ajax_nf_ai_warm_provider', array($this, 'warmProvider'), 10, 0);
        add_action('admin_print_footer_scripts', array($this, 'enqueueWarmer'), 5, 0);
    }

    /**
     * Add the first-class AI generation tile.
     *
     * @param array $templates Existing dashboard templates.
     * @return array
     */
    public function addTemplate(array $templates): array
    {
        if (! function_exists('wp_ai_client_prompt') || ! FeaturePolicy::isEnabled()) {
            return $templates;
        }

        return (new GenerationUi($this->isAvailable(), $this->promptLimit))->addTile($templates);
    }

    /**
     * Populate provider discovery caches in a background request.
     *
     * @return void
     */
    public function warmProvider(): void
    {
        if (! FeaturePolicy::currentUserCanManageForms()) {
            wp_die('', '', array('response' => 403));
        }
        if (! check_ajax_referer('ninja_forms_dashboard_nonce', 'security', false)) {
            wp_die('', '', array('response' => 403));
        }
        if (FeaturePolicy::isEnabled()) {
            $this->isAvailable();
        }
        wp_die();
    }

    /**
     * Enqueue the dashboard's idle provider-cache warmer.
     *
     * @return void
     */
    public function enqueueWarmer(): void
    {
        if (! function_exists('wp_ai_client_prompt') || ! FeaturePolicy::isEnabled()) {
            return;
        }
        if (! wp_script_is('nf-dashboard', 'enqueued')) {
            return;
        }
        if (false !== get_transient(ProviderManager::CACHE_KEY)) {
            return;
        }

        $javascript = "( function() {"
            . "if ( typeof nfAdmin === 'undefined' ) { return; }"
            . "var warm = function() {"
            . "try {"
            . "var body = new URLSearchParams();"
            . "body.append( 'action', 'nf_ai_warm_provider' );"
            . "body.append( 'security', nfAdmin.ajaxNonce );"
            . "fetch( nfAdmin.ajax_url, { method: 'POST', credentials: 'same-origin', body: body } );"
            . "} catch ( e ) {}"
            . "};"
            . "if ( window.requestIdleCallback ) { requestIdleCallback( warm ); } else { setTimeout( warm, 1500 ); }"
            . "} )();";

        wp_add_inline_script('nf-dashboard', $javascript);
    }

    /**
     * Flush provider discovery when connector settings change.
     *
     * @param string $option Option name.
     * @return void
     */
    public function flushProviderCache(string $option): void
    {
        if (0 === strpos($option, 'connectors_')) {
            ProviderManager::clearAuthenticationFailures();
            ProviderManager::flushCache();
            $this->available = null;
        }
    }

    /**
     * Memoize provider availability for this request.
     *
     * @return bool
     */
    private function isAvailable(): bool
    {
        if (null === $this->available) {
            $this->available = FeaturePolicy::hasProvider();
        }

        return $this->available;
    }
}
