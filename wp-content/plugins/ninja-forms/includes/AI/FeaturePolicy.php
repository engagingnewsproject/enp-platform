<?php

namespace NinjaForms\Includes\AI;

/**
 * Shared availability and authorization policy for Ninja Forms AI features.
 */
class FeaturePolicy
{
    /**
     * Whether the current user can manage every Ninja Forms form.
     *
     * @return bool
     */
    public static function currentUserCanManageForms(): bool
    {
        return current_user_can(
            apply_filters('ninja_forms_admin_all_forms_capabilities', 'manage_options')
        );
    }

    /**
     * Whether AI features are enabled for this site.
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        $enabled = '1' !== (string) Ninja_Forms()->get_setting('disable_ai_features');

        /**
         * Filter whether Ninja Forms AI features are enabled.
         *
         * @param bool $enabled Whether AI features are enabled.
         */
        return (bool) apply_filters('ninja_forms_ai_enabled', $enabled);
    }

    /**
     * Whether at least one authenticated compatible provider is available.
     *
     * @return bool
     */
    public static function hasProvider(): bool
    {
        return function_exists('wp_ai_client_prompt')
            && ! empty(ProviderManager::getAvailableProviders());
    }

    /**
     * Whether the feature can render for the current administrator.
     *
     * Deliberately does not require a connected provider. The dashboard tile
     * already renders without one and lets its modal explain how to connect;
     * the builder assistant gated its whole bootstrap on hasProvider(), so the
     * drawer's connect explanation could never be reached and the control
     * simply vanished with nothing to say why. A missing control teaches a
     * rule, and the rule it taught here was wrong.
     *
     * The AI Client itself is still required. Without it the platform cannot
     * host the feature at all, and the contract is that the tile, the sparkle
     * and the endpoints are simply absent rather than offering a dead end or
     * an upsell. Only the connected-provider requirement is lifted.
     *
     * Availability still decides what the surfaces show — see hasProvider() —
     * and the endpoints authorize on capability and the kill switch
     * independently, so rendering the entry point grants no new access.
     *
     * @return bool
     */
    public static function canRender(): bool
    {
        return function_exists('wp_ai_client_prompt')
            && self::currentUserCanManageForms()
            && self::isEnabled();
    }
}
