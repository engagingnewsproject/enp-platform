<?php

namespace NinjaForms\Includes\AI;

/**
 * Provider discovery and selection for Ninja Forms AI features.
 *
 * Credentials remain owned by WordPress Connectors. This class exposes only
 * provider IDs and display names, validates an explicit choice, and pins that
 * choice on every prompt built for a form.
 */
class ProviderManager
{
    public const CACHE_KEY = 'nf_ai_connected_providers';
    public const MODEL_CACHE_PREFIX = 'nf_ai_model_catalog_';
    public const LAST_MODEL_OPTION = 'nf_ai_last_model';
    public const AUTH_FAILURE_PREFIX = 'nf_ai_auth_failed_';
    public const CACHE_VERSION = 2;

    /**
     * Used only when the Connectors discovery API is unavailable.
     */
    public const AUTO_PROVIDER = '__auto__';

    /**
     * Providers Ninja Forms withholds regardless of connection state.
     *
     * The AI Provider for Google plugin drops the thought signature Gemini
     * returns alongside each function call, and Gemini rejects the following
     * turn without it, so a conversation cannot continue past its first reply.
     * Generation on its own would still work, but offering it would hand the
     * owner a form that chat can never edit. Withheld here only: the connector
     * stays registered with WordPress and usable by everything else on the
     * site. Drop the ID once that plugin round-trips the signature.
     *
     * @see https://github.com/WordPress/ai-provider-for-google/pull/36
     */
    public const UNSUPPORTED_PROVIDERS = array('google');

    /**
     * Connected, text-capable providers.
     *
     * @param bool $refresh Ignore the transient and discover providers again.
     * @return array<int,array{id:string,name:string,models:array,default_model:string}> Provider rows.
     */
    public static function getAvailableProviders(bool $refresh = false): array
    {
        if (! function_exists('wp_ai_client_prompt')) {
            return array();
        }

        if (! $refresh) {
            $cached = get_transient(self::CACHE_KEY);
            if (
                is_array($cached)
                && isset($cached['version'], $cached['providers'])
                && self::CACHE_VERSION === (int) $cached['version']
                && is_array($cached['providers'])
            ) {
                return $cached[ 'providers' ];
            }
        }

        $providers = self::discoverProviders();

        /**
         * Filter the providers Ninja Forms can offer.
         *
         * Primarily useful for controlled tests and hosts that bridge a
         * custom connector into the WordPress AI Client.
         *
         * @param array $providers Provider rows with ID, name, and models.
         */
        $providers = apply_filters('ninja_forms_ai_connected_providers', $providers);
        $providers = self::normalizeProviders($providers);

        set_transient(
            self::CACHE_KEY,
            array('version' => self::CACHE_VERSION, 'providers' => $providers),
            empty($providers) ? MINUTE_IN_SECONDS : HOUR_IN_SECONDS
        );

        return $providers;
    }

    /**
     * Clear provider discovery caches after connector changes or failures.
     *
     * @return void
     */
    public static function flushCache(): void
    {
        delete_transient(self::CACHE_KEY);
        delete_transient('nf_ai_provider_available');
    }

    /**
     * Resolve an exact provider/model pair. A form preference wins, then the
     * last model used anywhere in Ninja Forms, then the first connected
     * provider's newest stable compatible model.
     *
     * @param array  $providers          Available provider rows.
     * @param string $requested_provider Explicit provider ID.
     * @param string $requested_model    Explicit model ID.
     * @param array  $preferred          Attached provider/model preference.
     * @return array|\WP_Error Exact provider/model choice, or an availability error.
     */
    public static function resolveModelChoice(
        array $providers,
        string $requested_provider = '',
        string $requested_model = '',
        array $preferred = array()
    ) {
        $providers = self::normalizeProviders($providers);
        if ('' !== $requested_provider || '' !== $requested_model) {
            $choice = self::findModelChoice($providers, (string) $requested_provider, (string) $requested_model);
            return $choice ?: new \WP_Error(
                'nf_ai_model_unavailable',
                __('That AI model is no longer available. Choose another model to continue.', 'ninja-forms')
            );
        }
        if (is_array($preferred) && ! empty($preferred['provider'])) {
            $choice = self::findModelChoice(
                $providers,
                $preferred['provider'],
                isset($preferred['model']) ? $preferred['model'] : ''
            );
            return $choice ?: new \WP_Error(
                'nf_ai_model_unavailable',
                __(
                    'The AI model attached to this form is no longer available. Choose another model to continue.',
                    'ninja-forms'
                )
            );
        }
        $last = get_option(self::LAST_MODEL_OPTION, array());
        if (is_array($last) && ! empty($last['provider'])) {
            $choice = self::findModelChoice(
                $providers,
                $last['provider'],
                isset($last['model']) ? $last['model'] : ''
            );
            if ($choice) {
                return $choice;
            }
        }

        foreach ($providers as $provider) {
            if (! empty($provider['default_model'])) {
                return self::findModelChoice($providers, $provider['id'], $provider['default_model']);
            }
        }

        return new \WP_Error(
            'nf_ai_provider_missing',
            __('No connected AI provider has a compatible model available.', 'ninja-forms')
        );
    }

    /**
     * Get the unselected model choice used when resolution fails.
     *
     * Lives beside resolveModelChoice() because every caller that has to cope
     * with its WP_Error needs the same four empty keys to render an
     * unselected picker.
     *
     * @return array<string,string>
     */
    public static function emptyChoice(): array
    {
        return array(
            'provider' => '',
            'model' => '',
            'provider_name' => '',
            'model_name' => '',
        );
    }

    /**
     * Remember the most recently used exact model as the cross-form default.
     *
     * @param array $choice Resolved provider/model choice.
     * @return true|\WP_Error
     */
    public static function rememberModelChoice(array $choice)
    {
        if (empty($choice['provider']) || empty($choice['model'])) {
            return new \WP_Error(
                'nf_ai_model_unavailable',
                __('Choose an available AI model to continue.', 'ninja-forms')
            );
        }

        $stored = array(
            'provider' => sanitize_key($choice['provider']),
            'model' => sanitize_text_field($choice['model']),
        );
        $updated = update_option(self::LAST_MODEL_OPTION, $stored, false);
        if (! $updated && $stored !== get_option(self::LAST_MODEL_OPTION, array())) {
            return new \WP_Error(
                'nf_ai_model_save_failed',
                __('The default AI model could not be saved.', 'ninja-forms')
            );
        }

        return true;
    }

    /**
     * Temporarily hide a provider after an authentication failure.
     *
     * @param string $provider_id Provider ID.
     * @return void
     */
    public static function markAuthenticationFailed(string $provider_id): void
    {
        set_transient(self::AUTH_FAILURE_PREFIX . sanitize_key($provider_id), 1, 5 * MINUTE_IN_SECONDS);
        self::flushCache();
    }

    /**
     * Clear authentication-failure markers after connector edits.
     *
     * @return void
     */
    public static function clearAuthenticationFailures(): void
    {
        if (! function_exists('wp_get_connectors')) {
            return;
        }
        foreach (array_keys(wp_get_connectors()) as $provider_id) {
            delete_transient(self::AUTH_FAILURE_PREFIX . sanitize_key($provider_id));
        }
    }

    /**
     * Start a prompt pinned to the selected provider.
     *
     * @param mixed  $input Prompt text or message objects.
     * @param string $provider_id Connected provider ID.
     * @param string $model_id Exact model ID.
     * @return mixed WP AI Client prompt builder (or a filtered test double).
     */
    public static function prompt($input, string $provider_id, string $model_id = '')
    {
        $builder = wp_ai_client_prompt($input);

        if (self::AUTO_PROVIDER !== $provider_id && '' !== $model_id && class_exists('WordPress\\AiClient\\AiClient')) {
            $model = \WordPress\AiClient\AiClient::defaultRegistry()->getProviderModel($provider_id, $model_id);
            $builder = $builder->using_model($model);
        } elseif (self::AUTO_PROVIDER !== $provider_id) {
            $builder = $builder->using_provider($provider_id);
        }

        /**
         * Filter the pinned prompt builder.
         *
         * This seam lets automated tests exercise the full Ninja Forms loop
         * with deterministic provider doubles and no API credentials.
         *
         * @param mixed  $builder     Prompt builder.
         * @param string $provider_id Selected provider ID.
         * @param mixed  $input       Original prompt input.
         * @param string $model_id    Selected model ID.
         */
        return apply_filters('ninja_forms_ai_prompt_builder', $builder, $provider_id, $input, $model_id);
    }

    /**
     * Human-readable provider name.
     *
     * @param string $provider_id Provider ID.
     * @param array  $providers   Optional provider rows.
     * @return string Provider display name.
     */
    public static function getProviderName(string $provider_id, array $providers = array()): string
    {
        if (empty($providers)) {
            $providers = self::getAvailableProviders();
        }

        foreach ($providers as $provider) {
            if (isset($provider[ 'id' ], $provider[ 'name' ]) && $provider_id === $provider[ 'id' ]) {
                return $provider[ 'name' ];
            }
        }

        return __('AI provider', 'ninja-forms');
    }

    /**
     * Does a provider error indicate missing/invalid authentication?
     *
     * Typed as mixed to match classifyError(), which deliberately tolerates
     * anything that is not a WP_Error by classifying it as 'unknown'.
     *
     * @param mixed $error Provider error.
     * @return bool Whether the error indicates an authentication failure.
     */
    public static function isAuthenticationError($error): bool
    {
        return 'authentication' === self::classifyError($error);
    }

    /**
     * Wording that identifies a failure class, in the order it is tested.
     *
     * Order is the whole design. An exhausted account and a busy minute both
     * arrive as HTTP 429 with nothing but the provider's wording to separate
     * them, so the money words are read first; otherwise an account that needs
     * paying is described as weather that will pass. Each class earns its place
     * by changing what the reader should do next: reconnect, pay, wait a
     * minute, wait longer, shorten the request, rephrase it, or look at the
     * site's own network.
     *
     * Wording only. Status numbers were matched here as substrings against the
     * serialized error, which read the AI Client's own 503-for-any-network
     * convenience as provider overload and told a site with a broken connection
     * that the provider was at fault. A bare number in a blob is a coincidence
     * waiting to happen: 503 can as easily fall inside a request id, a byte
     * count or a timeout. Status codes are read explicitly in ERROR_STATUSES.
     *
     * @var array<string,array<int,string>>
     */
    private const ERROR_MARKERS = array(
        'authentication' => array(
            'authentication',
            'requestauthenticationinterface',
            'api key',
            'api_key',
            'unauthorized',
            'forbidden',
            'invalid_api_key',
        ),
        // Anthropic says credit balance and Plans and Billing; OpenAI says
        // insufficient_quota and exceeded your current quota; Google names a
        // free tier. All mean the same thing: retrying cannot help.
        'account' => array(
            'credit balance',
            'purchase credits',
            'plans and billing',
            'insufficient_quota',
            'insufficient quota',
            'exceeded your current quota',
            'billing',
            'payment required',
            'free_tier',
            'free tier',
        ),
        'too_large' => array(
            'context length',
            'context_length_exceeded',
            'maximum context',
            'too many tokens',
            'request too large',
            'prompt is too long',
        ),
        'refused' => array(
            'content filter',
            'content_filter',
            'content policy',
            'safety',
            'blocked by',
        ),
        // Ahead of 'unavailable': the client labels every network failure 503,
        // so the provider's own wording is the only honest signal of which of
        // the two it was.
        'unreachable' => array(
            'timeout',
            'timed out',
            'curl error',
            'could not resolve',
            'connection refused',
            'network_error',
        ),
        'rate_limited' => array(
            'rate limit',
            'rate_limit',
            'too many requests',
            'retry after',
        ),
        'unavailable' => array(
            'overload',
            'high demand',
            'upstream_server_error',
            'server_error',
            'service unavailable',
        ),
    );

    /**
     * Status codes that identify a class when no wording did.
     *
     * Read from the error data's own status field rather than found in text, so
     * a number appearing inside some unrelated value cannot be mistaken for the
     * response code. Consulted only after every wording test has failed, which
     * keeps the client's blanket 503 for network failures from outranking the
     * "cURL error" sitting in the same message.
     *
     * @var array<string,array<int,int>>
     */
    private const ERROR_STATUSES = array(
        'authentication' => array(401, 403),
        'account' => array(402),
        'too_large' => array(413),
        'rate_limited' => array(429),
        'unavailable' => array(500, 502, 503, 504, 529),
    );

    /**
     * Classify a provider failure by what the reader should do about it.
     *
     * @param mixed $error Provider error.
     * @return string Class key, or 'unknown'.
     */
    public static function classifyError($error): string
    {
        if (! is_wp_error($error)) {
            return 'unknown';
        }

        $haystack = strtolower(
            $error->get_error_code() . ' ' . $error->get_error_message() . ' '
            . wp_json_encode($error->get_error_data())
        );
        foreach (self::ERROR_MARKERS as $class => $markers) {
            foreach ($markers as $needle) {
                if (false !== strpos($haystack, $needle)) {
                    return $class;
                }
            }
        }

        $status = self::statusCode($error);
        if (0 !== $status) {
            foreach (self::ERROR_STATUSES as $class => $codes) {
                if (in_array($status, $codes, true)) {
                    return $class;
                }
            }
        }

        return 'unknown';
    }

    /**
     * Read the HTTP status the client recorded, if it recorded one.
     *
     * @param \WP_Error $error Provider error.
     * @return int Status code, or 0 when absent.
     */
    private static function statusCode($error): int
    {
        $data = $error->get_error_data();

        return is_array($data) && isset($data['status']) && is_numeric($data['status'])
            ? (int) $data['status']
            : 0;
    }

    /**
     * Replace raw SDK/provider errors with safe product language.
     *
     * The provider's own text never reaches the reader: it carries SDK
     * internals and, in the authentication case, credential detail. It is
     * written to the debug log instead when the site has debug logging on, so
     * an owner chasing a failure can see exactly what the provider said
     * without that text being published into the interface.
     *
     * @param \WP_Error $error         Provider error.
     * @param string   $provider_name Display name.
     * @return \WP_Error Safe product-facing error.
     */
    public static function normalizeError($error, string $provider_name): \WP_Error
    {
        self::flushCache();

        $class = self::classifyError($error);
        self::logProviderError($error, $provider_name, $class);

        switch ($class) {
            case 'authentication':
                return new \WP_Error(
                    'nf_ai_provider_authentication',
                    sprintf(
                        /* translators: %s: AI provider name. */
                        __('%s could not authenticate. Reconnect it under Settings → Connectors or choose another provider.', 'ninja-forms'), // phpcs:ignore Generic.Files.LineLength.TooLong -- Translator-safe single literal.
                        $provider_name
                    )
                );
            case 'account':
                return new \WP_Error(
                    'nf_ai_provider_account',
                    sprintf(
                        /* translators: %s: AI provider name. */
                        __('%s declined the request because of your account. Check your plan or billing with them. Trying again will not help.', 'ninja-forms'), // phpcs:ignore Generic.Files.LineLength.TooLong -- Translator-safe single literal.
                        $provider_name
                    )
                );
            case 'too_large':
                return new \WP_Error(
                    'nf_ai_provider_request_too_large',
                    __('The request was too large for this model. Shorten it, or choose a model with more room.', 'ninja-forms') // phpcs:ignore Generic.Files.LineLength.TooLong -- Translator-safe single literal.
                );
            case 'refused':
                return new \WP_Error(
                    'nf_ai_provider_refused',
                    sprintf(
                        /* translators: %s: AI provider name. */
                        __('%s refused this request. Rephrase it and try again.', 'ninja-forms'),
                        $provider_name
                    )
                );
            case 'rate_limited':
                return new \WP_Error(
                    'nf_ai_provider_rate_limited',
                    sprintf(
                        /* translators: %s: AI provider name. */
                        __(
                            '%s is taking too many requests right now. Wait a minute and try again.',
                            'ninja-forms'
                        ),
                        $provider_name
                    )
                );
            case 'unavailable':
                return new \WP_Error(
                    'nf_ai_provider_temporarily_unavailable',
                    sprintf(
                        /* translators: %s: AI provider name. */
                        __('%s is having trouble right now. Try again in a few minutes or choose another model.', 'ninja-forms'), // phpcs:ignore Generic.Files.LineLength.TooLong -- Translator-safe single literal.
                        $provider_name
                    )
                );
            case 'unreachable':
                return new \WP_Error(
                    'nf_ai_provider_unreachable',
                    sprintf(
                        /* translators: %s: AI provider name. */
                        __('Your site could not reach %s. This is usually a network or firewall problem on your site.', 'ninja-forms'), // phpcs:ignore Generic.Files.LineLength.TooLong -- Translator-safe single literal.
                        $provider_name
                    )
                );
        }

        return new \WP_Error(
            'nf_ai_provider_request_failed',
            sprintf(
                /* translators: %s: AI provider name. */
                __(
                    '%s could not complete the request. Try again or choose another model.',
                    'ninja-forms'
                ),
                $provider_name
            )
        );
    }

    /**
     * Record what the provider actually said, for a site with debug logging on.
     *
     * A whole retest pass produced not one byte of log, so a failure left
     * nobody anything to read: an account decline dressed as weather cost an
     * hour of chasing a form-state bug that did not exist.
     *
     * @param mixed  $error         Provider error.
     * @param string $provider_name Display name.
     * @param string $class         Resolved failure class.
     * @return void
     */
    private static function logProviderError($error, string $provider_name, string $class): void
    {
        if (! defined('WP_DEBUG_LOG') || ! WP_DEBUG_LOG || ! is_wp_error($error)) {
            return;
        }

        error_log(
            sprintf(
                'Ninja Forms AI: %1$s request failed (%2$s). Provider reported [%3$s] %4$s',
                $provider_name,
                $class,
                $error->get_error_code(),
                $error->get_error_message()
            )
        );
    }

    /**
     * Does an error describe a failure that may pass on its own?
     *
     * @param \WP_Error $error Provider error.
     * @return bool Whether waiting could plausibly resolve the failure.
     */
    public static function isTemporaryError($error): bool
    {
        return in_array(
            self::classifyError($error),
            array('rate_limited', 'unavailable', 'unreachable'),
            true
        );
    }

    /**
     * Discover providers from the public WordPress Connectors API.
     *
     * @return array Discovered provider rows.
     */
    private static function discoverProviders(): array
    {
        if (! function_exists('wp_get_connectors')) {
            $supported = wp_ai_client_prompt('capability check')->is_supported_for_text_generation();

            return $supported ? array(
                array(
                    'id'   => self::AUTO_PROVIDER,
                    'name' => __('Connected provider', 'ninja-forms'),
                ),
            ) : array();
        }

        $providers = array();
        foreach (wp_get_connectors() as $provider_id => $connector) {
            if (! is_array($connector) || 'ai_provider' !== ($connector[ 'type' ] ?? '')) {
                continue;
            }

            if (in_array((string) $provider_id, self::UNSUPPORTED_PROVIDERS, true)) {
                continue;
            }

            if (! self::connectorIsUsable($provider_id, $connector)) {
                continue;
            }

            $supported = wp_ai_client_prompt('capability check')
                ->using_provider($provider_id)
                ->is_supported_for_text_generation();

            if (! $supported) {
                continue;
            }

            $models = self::discoverModels($provider_id);
            if (empty($models)) {
                continue;
            }

            $default_model = '';
            foreach ($models as $model) {
                if (! empty($model['recommended'])) {
                    $default_model = $model['id'];
                    break;
                }
            }
            if ('' === $default_model) {
                $default_model = $models[0]['id'];
            }

            $providers[] = array(
                'id'   => (string) $provider_id,
                'name' => isset($connector[ 'name' ])
                    ? sanitize_text_field($connector[ 'name' ])
                    : ucfirst((string) $provider_id),
                'models' => $models,
                'default_model' => $default_model,
            );
        }

        return $providers;
    }

    /**
     * Normalize and deduplicate provider rows.
     *
     * @param mixed $providers Provider rows.
     * @return array Normalized provider rows.
     */
    private static function normalizeProviders($providers): array
    {
        if (! is_array($providers)) {
            return array();
        }

        $normalized = array();
        foreach ($providers as $provider) {
            if (! is_array($provider) || empty($provider[ 'id' ]) || empty($provider[ 'name' ])) {
                continue;
            }

            $id = (string) $provider[ 'id' ];
            if (self::AUTO_PROVIDER !== $id && ! preg_match('/^[a-z0-9_-]+$/', $id)) {
                continue;
            }

            $models = array();
            if (isset($provider['models']) && is_array($provider['models'])) {
                foreach ($provider['models'] as $model) {
                    if (! is_array($model) || empty($model['id'])) {
                        continue;
                    }
                    $models[] = array(
                        'id'          => sanitize_text_field($model['id']),
                        'name'        => sanitize_text_field(isset($model['name']) ? $model['name'] : $model['id']),
                        'recommended' => ! empty($model['recommended']),
                    );
                }
            }

            $normalized[ $id ] = array(
                'id'   => $id,
                'name' => sanitize_text_field($provider[ 'name' ]),
                'models' => $models,
                'default_model' => isset($provider['default_model'])
                    ? sanitize_text_field($provider['default_model'])
                    : (! empty($models) ? $models[0]['id'] : ''),
            );
        }

        return array_values($normalized);
    }

    /**
     * Determine whether a connector is installed and authenticated.
     *
     * @param string $provider_id Provider ID.
     * @param array  $connector   Connector metadata.
     * @return bool Whether the connector can make authenticated requests.
     */
    private static function connectorIsUsable(string $provider_id, array $connector): bool
    {
        if (get_transient(self::AUTH_FAILURE_PREFIX . sanitize_key($provider_id))) {
            return false;
        }
        $hasActivePlugin = isset($connector['plugin']['is_active'])
            && is_callable($connector['plugin']['is_active']);
        if ($hasActivePlugin && ! call_user_func($connector['plugin']['is_active'])) {
            return false;
        }

        // Prefer the same signal the Connectors admin page uses for its
        // "Connected" badge. has_connector_authentication() only recognizes
        // a stored API key (env var, constant, or option) and misses
        // providers like a local Ollama instance, which authenticate via a
        // runtime-only fallback credential that is never written to those
        // sources.
        if (function_exists('WordPress\\AI\\is_connector_configured')) {
            return (bool) \WordPress\AI\is_connector_configured($provider_id);
        }

        if (function_exists('WordPress\\AI\\has_connector_authentication')) {
            return (bool) \WordPress\AI\has_connector_authentication($provider_id);
        }

        $auth = isset($connector['authentication']) && is_array($connector['authentication'])
            ? $connector['authentication'] : array();
        if ('api_key' !== (isset($auth['method']) ? $auth['method'] : '')) {
            return true;
        }

        if (! empty($auth['setting_name']) && '' !== trim((string) get_option($auth['setting_name'], ''))) {
            return true;
        }
        $hasConstant = ! empty($auth['constant_name'])
            && defined($auth['constant_name'])
            && '' !== trim((string) constant($auth['constant_name']));
        if ($hasConstant) {
            return true;
        }
        if (! empty($auth['env_var_name'])) {
            $value = getenv($auth['env_var_name']);
            if (false !== $value && '' !== trim((string) $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Discover compatible text/function models for a provider.
     *
     * @param string $provider_id Provider ID.
     * @return array Compatible model rows.
     */
    private static function discoverModels(string $provider_id): array
    {
        $cache_key = self::MODEL_CACHE_PREFIX . sanitize_key($provider_id);
        $cached = get_transient($cache_key);

        try {
            if (! class_exists('WordPress\\AiClient\\AiClient')) {
                wp_ai_client_prompt('load model catalog');
            }
            $requirements = new \WordPress\AiClient\Providers\Models\DTO\ModelRequirements(array(), array());
            $metadata = \WordPress\AiClient\AiClient::defaultRegistry()
                ->findProviderModelsMetadataForSupport($provider_id, $requirements);
            $models = array();
            $recommended_count = 0;
            foreach ($metadata as $model_metadata) {
                $data = $model_metadata->toArray();
                if (! self::modelSupportsFormBuilding($data)) {
                    continue;
                }
                $stable = self::isStableGeneralModel($data['id']);
                $recommended = $stable && $recommended_count < 5;
                if ($recommended) {
                    $recommended_count++;
                }
                $models[] = array(
                    'id'          => (string) $data['id'],
                    'name'        => ! empty($data['name']) ? (string) $data['name'] : (string) $data['id'],
                    'recommended' => $recommended,
                );
            }
            if (! empty($models)) {
                set_transient($cache_key, $models, WEEK_IN_SECONDS);
                return $models;
            }
        } catch (\Throwable $e) {
            // A provider catalog endpoint can be unavailable while an already
            // selected model still works. Keep the last known list visible.
        }

        return is_array($cached) ? $cached : array();
    }

    /**
     * Check the model capabilities required for form building.
     *
     * @param array $data Model metadata.
     * @return bool Whether the model has every required capability.
     */
    private static function modelSupportsFormBuilding(array $data): bool
    {
        $capabilities = isset($data['supportedCapabilities']) && is_array($data['supportedCapabilities'])
            ? $data['supportedCapabilities'] : array();
        if (! in_array('text_generation', $capabilities, true) || ! in_array('chat_history', $capabilities, true)) {
            return false;
        }

        $options = array();
        $supportedOptions = isset($data['supportedOptions']) && is_array($data['supportedOptions'])
            ? $data['supportedOptions']
            : array();
        foreach ($supportedOptions as $option) {
            if (is_array($option) && isset($option['name'])) {
                $options[] = $option['name'];
            }
        }
        return in_array('systemInstruction', $options, true)
            && in_array('functionDeclarations', $options, true)
            && in_array('outputMimeType', $options, true);
    }

    /**
     * Exclude specialized, preview, and dated variants from defaults.
     *
     * @param string $model_id Model ID.
     * @return bool Whether the model is suitable as a stable default.
     */
    private static function isStableGeneralModel(string $model_id): bool
    {
        $id = strtolower((string) $model_id);
        $specializedMarkers = array(
            'preview', 'experimental', '-exp', 'realtime', 'audio', 'image', 'vision',
            'transcribe', 'tts', 'embedding', 'moderation', 'search', 'codex', 'nano',
        );
        foreach ($specializedMarkers as $marker) {
            if (false !== strpos($id, $marker)) {
                return false;
            }
        }
        return ! preg_match('/(?:^|[-_])20\d{2}[-_]?(?:0[1-9]|1[0-2])[-_]?(?:0[1-9]|[12]\d|3[01])(?:$|[-_])/', $id);
    }

    /**
     * Find one exact choice in normalized provider rows.
     *
     * @param array  $providers   Provider rows.
     * @param string $provider_id Provider ID.
     * @param string $model_id    Model ID, or empty for the default.
     * @return array|false Exact provider/model choice, or false when not found.
     */
    private static function findModelChoice(array $providers, string $provider_id, string $model_id)
    {
        foreach ($providers as $provider) {
            if ($provider['id'] !== $provider_id) {
                continue;
            }
            if ('' === $model_id) {
                $model_id = $provider['default_model'];
            }
            foreach ($provider['models'] as $model) {
                if ($model['id'] === $model_id) {
                    return array(
                        'provider'      => $provider['id'],
                        'provider_name' => $provider['name'],
                        'model'         => $model['id'],
                        'model_name'    => $model['name'],
                    );
                }
            }
        }
        return false;
    }
}
