<?php

if (! defined('ABSPATH')) {
    exit;
}

use NinjaForms\Includes\AI\ChatAgent;
use NinjaForms\Includes\AI\ConversationStore;
use NinjaForms\Includes\AI\FeaturePolicy;
use NinjaForms\Includes\AI\FormLookup;
use NinjaForms\Includes\AI\ModelAttachment;
use NinjaForms\Includes\AI\ProviderManager;

require_once dirname(__DIR__, 2) . '/AI/ProviderManager.php';
require_once dirname(__DIR__, 2) . '/AI/MessageReplay.php';
require_once dirname(__DIR__, 2) . '/AI/DraftForm.php';
require_once dirname(__DIR__, 2) . '/AI/ConversationStore.php';
require_once dirname(__DIR__, 2) . '/AI/ChatAgent.php';
require_once dirname(__DIR__, 2) . '/AI/FeaturePolicy.php';
require_once dirname(__DIR__, 2) . '/AI/FormLookup.php';
require_once dirname(__DIR__, 2) . '/AI/ModelAttachment.php';
require_once dirname(__DIR__, 2) . '/AI/ChatUiBootstrap.php';

/**
 * Authenticated browser transport for the builder AI assistant.
 */
class NF_AJAX_REST_AIChat extends NF_AJAX_REST_Controller
{
    protected $action = 'nf_ai_chat';

    public const MESSAGE_MAX_LENGTH = 2000;
    public const MAX_HISTORY = 12;

    /** @var ChatAgent */
    private ChatAgent $agent;

    /**
     * Register the endpoint.
     *
     * The builder chat bootstrap is mounted from the plugin bootstrap
     * (ninja-forms.php) alongside the other feature integrations, so this
     * transport registers nothing beyond its own admin-ajax action.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->agent = new ChatAgent();
    }

    /**
     * Handle chat and conversation lifecycle requests.
     *
     * @param array $requestData Sanitized request data.
     * @return array
     */
    public function post($requestData): array
    {
        try {
            return $this->handle($requestData);
        } catch (\Throwable $error) {
            /*
             * NF_AJAX_REST_Controller catches Exception and puts its raw
             * getMessage() straight into the response. For most endpoints that
             * text is ours; here it can be the provider SDK's, which is exactly
             * what normalizeError() exists to keep off the screen — an
             * unauthenticated provider surfaced "RequestAuthenticationInterface
             * instance not set" to the administrator through that path. Convert
             * to product language before the base class can echo it.
             */
            $safe = ProviderManager::normalizeError(
                new \WP_Error('nf_ai_provider_exception', $error->getMessage()),
                $this->providerNameForError($requestData)
            );
            $this->_errors[] = $safe->get_error_message();

            return array();
        }
    }

    /**
     * Name the provider a failure should be attributed to.
     *
     * @param array $requestData Sanitized request data.
     * @return string Display name, or a neutral fallback.
     */
    private function providerNameForError($requestData): string
    {
        $providerId = isset($requestData['provider']) ? (string) $requestData['provider'] : '';
        if ('' === $providerId) {
            $formId = (int) ($requestData['form_id'] ?? 0);
            $providerId = $formId ? ConversationStore::getChoice($formId)['provider'] : '';
        }

        return '' !== $providerId
            ? ProviderManager::getProviderName($providerId)
            : __('The AI provider', 'ninja-forms');
    }

    /**
     * Handle chat and conversation lifecycle requests.
     *
     * @param array $requestData Sanitized request data.
     * @return array
     */
    private function handle($requestData): array
    {
        $this->authorizeRequest();
        $formId = (int) ($requestData['form_id'] ?? 0);
        $this->assertFormExists($formId);

        if (! empty($requestData['exchange_status'])) {
            return ConversationStore::getExchangeState(
                $formId,
                $requestData['exchange_status']
            );
        }
        if (! empty($requestData['accept_exchange'])) {
            $accepted = ConversationStore::accept($formId, $requestData['accept_exchange']);
            $this->requireStorageSuccess($accepted);

            return array(
                'accepted' => $accepted,
            );
        }
        if (! empty($requestData['discard_exchange'])) {
            $discarded = ConversationStore::discard($formId, $requestData['discard_exchange']);
            $this->requireStorageSuccess($discarded);

            return array(
                'discarded' => $discarded,
            );
        }
        if (! empty($requestData['revert_exchange'])) {
            $reverted = ConversationStore::recordReversion(
                $formId,
                $requestData['revert_exchange'],
                get_current_user_id()
            );
            $this->requireStorageSuccess($reverted);

            return array(
                'reverted' => $reverted,
            );
        }
        if (! empty($requestData['clear_history'])) {
            $this->requireStorageSuccess(ConversationStore::clearContext($formId));

            return array('cleared' => true);
        }
        if (! empty($requestData['select_model'])) {
            return $this->persistModelChoice($formId, $requestData);
        }

        if (empty($requestData['message'])) {
            $this->fail(__('A message is required.', 'ninja-forms'));
        }
        if (empty($requestData['draft']) || ! is_array($requestData['draft'])) {
            $this->fail(__('The current form draft is required.', 'ninja-forms'));
        }

        $providers = ProviderManager::getAvailableProviders();
        $choice = $this->resolveChoice($formId, $providers, $requestData);
        if (is_wp_error($choice)) {
            return $this->providerSelectionResponse($choice, $providers);
        }
        $attached = ModelAttachment::attach($formId, $choice);
        if (is_wp_error($attached)) {
            return $this->providerSelectionResponse($attached, $providers);
        }

        $result = $this->agent->run(
            $formId,
            $requestData['message'],
            array_slice(ConversationStore::getHistory($formId), -self::MAX_HISTORY),
            $choice['provider'],
            $choice['provider_name'],
            $requestData['draft'],
            $choice['model']
        );
        if (is_wp_error($result)) {
            return $this->handleProviderError($formId, $choice['provider'], $result);
        }

        $exchangeToken = ConversationStore::stage(
            $formId,
            array(
                array(
                    'role'    => 'user',
                    'content' => $requestData['message'],
                    'user_id' => get_current_user_id(),
                    'time'    => time(),
                ),
                array(
                    'role'    => 'assistant',
                    'content' => $result['reply'],
                    'user_id' => 0,
                    'time'    => time(),
                ),
            )
        );
        $this->requireStorageSuccess($exchangeToken);
        $result['exchange_token'] = $exchangeToken;
        $result['provider'] = $choice['provider'];
        $result['model'] = $choice['model'];

        return $result;
    }

    /**
     * Validate capability, nonce, and feature state.
     *
     * @return void
     */
    private function authorizeRequest(): void
    {
        if (! FeaturePolicy::currentUserCanManageForms()) {
            $this->fail(__('Access denied.', 'ninja-forms'));
        }
        if (! check_ajax_referer('nf_ai_chat', 'security', false)) {
            $this->fail(__('Request forbidden.', 'ninja-forms'));
        }
        if (! FeaturePolicy::isEnabled()) {
            $this->fail(__('AI features are disabled on this site.', 'ninja-forms'));
        }
    }

    /**
     * Confirm the requested form exists in storage.
     *
     * @param int $formId Form ID.
     * @return void
     */
    private function assertFormExists(int $formId): void
    {
        if (! $formId) {
            $this->fail(__('A form is required.', 'ninja-forms'));
        }
        if (! FormLookup::exists($formId)) {
            $this->fail(__('Form not found.', 'ninja-forms'));
        }
    }

    /**
     * Resolve an exact provider/model selection.
     *
     * @param int   $formId     Form ID.
     * @param array $providers  Available provider rows.
     * @param array $requestData Request values.
     * @return array|WP_Error
     */
    private function resolveChoice(int $formId, array $providers, array $requestData)
    {
        return ProviderManager::resolveModelChoice(
            $providers,
            (string) ($requestData['provider'] ?? ''),
            (string) ($requestData['model'] ?? ''),
            ConversationStore::getChoice($formId)
        );
    }

    /**
     * Validate and immediately persist a picker selection.
     *
     * @param int   $formId     Form ID.
     * @param array $requestData Request values.
     * @return array
     */
    private function persistModelChoice(int $formId, array $requestData): array
    {
        $providers = ProviderManager::getAvailableProviders();
        $choice = ProviderManager::resolveModelChoice(
            $providers,
            (string) ($requestData['provider'] ?? ''),
            (string) ($requestData['model'] ?? '')
        );
        if (is_wp_error($choice)) {
            return $this->providerSelectionResponse($choice, $providers);
        }

        $attached = ModelAttachment::attach($formId, $choice);
        if (is_wp_error($attached)) {
            return $this->providerSelectionResponse($attached, $providers);
        }

        return array(
            'selected' => true,
            'provider' => $choice['provider'],
            'model'    => $choice['model'],
        );
    }

    /**
     * Normalize provider failures and recover from authentication loss.
     *
     * @param int      $formId    Form ID.
     * @param string   $providerId Provider ID.
     * @param WP_Error $error      Normalized provider error.
     * @return array
     */
    private function handleProviderError(int $formId, string $providerId, WP_Error $error): array
    {
        if ('nf_ai_provider_authentication' === $error->get_error_code()) {
            ProviderManager::markAuthenticationFailed($providerId);
            $this->requireStorageSuccess(ConversationStore::deleteChoice($formId));

            return $this->providerSelectionResponse(
                $error,
                ProviderManager::getAvailableProviders(true)
            );
        }

        $this->fail($error->get_error_message());

        return array();
    }

    /**
     * Build a response that refreshes the model picker.
     *
     * @param WP_Error $error     Selection error.
     * @param array    $providers Available providers.
     * @return array
     */
    private function providerSelectionResponse(WP_Error $error, array $providers): array
    {
        return array(
            'provider_required' => true,
            'provider'          => '',
            'model'             => '',
            'providers'         => $providers,
            'message'           => $error->get_error_message(),
            'changed'           => false,
        );
    }

    /**
     * Stop request processing with a safe endpoint error.
     *
     * @param string $message Error message.
     * @return void
     */
    private function fail(string $message): void
    {
        $this->_errors[] = esc_html($message);
        $this->_respond();
    }

    /**
     * Stop request processing when conversation storage did not complete.
     *
     * @param mixed $result Storage operation result.
     * @return void
     */
    private function requireStorageSuccess($result): void
    {
        if (is_wp_error($result)) {
            $this->fail($result->get_error_message());
        }
    }

    /**
     * Get sanitized request data.
     *
     * @return array<string,mixed>
     */
    protected function get_request_data(): array
    {
        $data = array(
            'form_id' => isset($_REQUEST['form_id']) ? absint($_REQUEST['form_id']) : 0,
            'message' => '',
        );
        if (isset($_REQUEST['message'])) {
            $message = sanitize_textarea_field(wp_unslash($_REQUEST['message']));
            $data['message'] = mb_substr(trim($message), 0, self::MESSAGE_MAX_LENGTH);
        }
        if (isset($_REQUEST['provider'])) {
            $data['provider'] = sanitize_key(wp_unslash($_REQUEST['provider']));
        }
        if (isset($_REQUEST['model'])) {
            $data['model'] = sanitize_text_field(wp_unslash($_REQUEST['model']));
        }
        if (isset($_REQUEST['draft'])) {
            $draft = json_decode(wp_unslash($_REQUEST['draft']), true);
            if (is_array($draft)) {
                $data['draft'] = $draft;
            }
        }
        foreach (
            array('exchange_status', 'accept_exchange', 'discard_exchange', 'revert_exchange') as $key
        ) {
            if (isset($_REQUEST[$key])) {
                $data[$key] = sanitize_text_field(wp_unslash($_REQUEST[$key]));
            }
        }
        $data['clear_history'] = ! empty($_REQUEST['clear_history']);
        $data['select_model'] = ! empty($_REQUEST['select_model']);

        return $data;
    }
}
