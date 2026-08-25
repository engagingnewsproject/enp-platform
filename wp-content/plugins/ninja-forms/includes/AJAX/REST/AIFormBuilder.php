<?php

if (! defined('ABSPATH')) {
    exit;
}

use NinjaForms\Includes\AI\ProviderManager;
use NinjaForms\Includes\AI\PromptValidator;
use NinjaForms\Includes\AI\ConversationStore;
use NinjaForms\Includes\AI\FeaturePolicy;
use NinjaForms\Includes\AI\FormGenerationService;
use NinjaForms\Includes\AI\FormLookup;
use NinjaForms\Includes\AI\ModelAttachment;

require_once dirname(__DIR__, 2) . '/AI/ProviderManager.php';
require_once dirname(__DIR__, 2) . '/AI/PromptValidator.php';
require_once dirname(__DIR__, 2) . '/AI/ConversationStore.php';
require_once dirname(__DIR__, 2) . '/AI/FeaturePolicy.php';
require_once dirname(__DIR__, 2) . '/AI/FormGenerationService.php';
require_once dirname(__DIR__, 2) . '/AI/FormLookup.php';
require_once dirname(__DIR__, 2) . '/AI/GenerationUi.php';
require_once dirname(__DIR__, 2) . '/AI/GenerationAdminHooks.php';
require_once dirname(__DIR__, 2) . '/AI/ModelAttachment.php';

/**
 * AI Form Builder endpoint.
 *
 * Generates a Ninja Forms form from a plain-language description using the
 * WordPress AI Client (WP 7.0+, Settings → Connectors) and the
 * ninjaforms/create-form ability, then returns the new form ID so the
 * dashboard can open it in the builder.
 *
 * The site owner's connected AI provider handles the model call — Ninja Forms
 * holds no API keys and sends only the description the user typed.
 */
class NF_AJAX_REST_AIFormBuilder extends NF_AJAX_REST_Controller
{
    protected $action = 'nf_ai_generate_form';

    public const PROMPT_MAX_LENGTH = 2000;

    /**
     * Register the endpoint.
     *
     * The dashboard integration is mounted from the plugin bootstrap
     * (ninja-forms.php) alongside the other feature integrations, so this
     * transport registers nothing beyond its own admin-ajax action.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * POST nf_ai_generate_form
     *
     * @param array $request_data Generation request data.
     * @return array Generated form ID payload.
     */
    public function post($request_data): array
    {
        // Does the current user have admin privileges
        if (! FeaturePolicy::currentUserCanManageForms()) {
            $this->_errors[] = esc_html__(
                'Access denied. You must have admin privileges to generate forms.',
                'ninja-forms'
            );
            $this->_respond();
        }

        // If we don't have a nonce...
        // OR if the nonce is invalid...
        if (! check_ajax_referer('ninja_forms_dashboard_nonce', 'security', false)) {
            $this->_errors[] = esc_html__('Request forbidden.', 'ninja-forms');
            $this->_respond();
        }

        if (! FeaturePolicy::isEnabled()) {
            $this->_errors[] = esc_html__('AI features are disabled on this site.', 'ninja-forms');
            $this->_respond();
        }

        $requestToken = (string) ($request_data['request_token'] ?? '');
        if ('' === $requestToken) {
            $this->_errors[] = esc_html__('A form-generation request token is required.', 'ninja-forms');
            $this->_respond();
        }
        $requestState = ConversationStore::getGenerationState($requestToken);
        if (is_wp_error($requestState)) {
            $this->_errors[] = esc_html($requestState->get_error_message());
            $this->_respond();
        }
        if ('complete' === $requestState['status'] && $this->formExists($requestState['form_id'])) {
            return array('form_id' => $requestState['form_id'], 'recovered' => true);
        }
        if ('complete' === $requestState['status']) {
            ConversationStore::releaseGeneration($requestToken);
            $requestState = array('status' => 'missing', 'form_id' => 0, 'expired' => false);
        }
        if ('processing' === $requestState['status'] && ! empty($requestState['expired'])) {
            $cleanup = $this->cleanupIncompleteGeneration(
                (int) $requestState['form_id'],
                $requestToken
            );
            if (is_wp_error($cleanup)) {
                $this->_errors[] = esc_html($cleanup->get_error_message());
                $this->_respond();
            }
            $requestState = array('status' => 'missing', 'form_id' => 0, 'expired' => false);
        }
        if (! empty($request_data['request_status'])) {
            return $requestState;
        }

        if (empty($request_data[ 'prompt' ])) {
            $this->_errors[] = esc_html__('Please describe the form you would like to create.', 'ninja-forms');
            $this->_respond();
        }

        $prompt_length_error = PromptValidator::lengthError(
            $request_data[ 'prompt' ],
            self::PROMPT_MAX_LENGTH
        );
        if (is_wp_error($prompt_length_error)) {
            $this->_errors[] = $prompt_length_error->get_error_message();
            $this->_respond();
        }

        $providers = ProviderManager::getAvailableProviders();
        if (empty($providers)) {
            $this->_errors[] = esc_html__(
                'No AI provider is connected. Connect one under Settings → Connectors and try again.',
                'ninja-forms'
            );
            $this->_respond();
        }

        $choice = ProviderManager::resolveModelChoice(
            $providers,
            isset($request_data[ 'provider' ]) ? $request_data[ 'provider' ] : '',
            isset($request_data[ 'model' ]) ? $request_data[ 'model' ] : ''
        );
        if (is_wp_error($choice)) {
            $this->_errors[] = esc_html($choice->get_error_message());
            $this->_respond();
        }
        $claim = ConversationStore::claimGeneration($requestToken);
        if (is_wp_error($claim)) {
            $this->_errors[] = esc_html($claim->get_error_message());
            $this->_respond();
        }
        if (empty($claim['claimed'])) {
            if ('complete' === $claim['status'] && $this->formExists($claim['form_id'])) {
                return array('form_id' => $claim['form_id'], 'recovered' => true);
            }

            return array('request_pending' => true);
        }
        $remembered = ModelAttachment::remember($choice);
        if (is_wp_error($remembered)) {
            ConversationStore::releaseGeneration($requestToken);
            $this->_errors[] = esc_html($remembered->get_error_message());
            $this->_respond();
        }

        try {
            $result = (new FormGenerationService())->generate($request_data['prompt'], $choice, $providers);
        } catch (\Throwable $error) {
            ConversationStore::releaseGeneration($requestToken);
            /*
             * A provider that raised rather than returned used to be reported
             * as a safety failure, which sends the reader to rewrite a prompt
             * that was never the problem: an unauthenticated provider read as
             * "the generated form could not be completed safely". Classify
             * first and keep the safety wording only for failures that are not
             * a recognized provider condition.
             */
            $wrapped = new \WP_Error('nf_ai_generation_exception', $error->getMessage());
            $this->_errors[] = 'unknown' === ProviderManager::classifyError($wrapped)
                ? esc_html__(
                    'The generated form could not be completed safely. Please try again.',
                    'ninja-forms'
                )
                : esc_html(
                    ProviderManager::normalizeError($wrapped, $choice['provider_name'])
                        ->get_error_message()
                );
            $this->_respond();
        }
        if (is_wp_error($result)) {
            ConversationStore::releaseGeneration($requestToken);
            $this->_errors[] = esc_html($result->get_error_message());
            $this->_respond();
        }

        $tracked = ConversationStore::trackGenerationForm(
            $requestToken,
            (int) $result['form_id']
        );
        if (is_wp_error($tracked)) {
            $this->failIncompleteGeneration((int) $result['form_id'], $requestToken, $tracked);
        }

        // Seed the form's assistant conversation with the creation exchange,
        // so the in-builder chat opens with the original prompt as its first
        // turn — one continuous history of the AI's decisions for this form.
        $attached = ModelAttachment::attach((int) $result['form_id'], $choice);
        if (is_wp_error($attached)) {
            $this->failIncompleteGeneration((int) $result['form_id'], $requestToken, $attached);
        }

        // The ability's count includes the auto-added submit button.
        $field_count = isset($result['field_count']) ? max(0, (int) $result['field_count'] - 1) : 0;
        $title = isset($result['title']) ? $result['title'] : __('your form', 'ninja-forms');
        $stored = ConversationStore::appendAccepted(
            $result['form_id'],
            array(
                array(
                    'role'    => 'user',
                    'content' => $request_data['prompt'],
                    'user_id' => get_current_user_id(),
                    'time'    => time(),
                ),
                array(
                    'role'    => 'assistant',
                    'content' => sprintf(
                        /* translators: 1: form title, 2: number of fields */
                        __(
                            'I built “%1$s” from your description — %2$d fields plus a submit button, '
                            . 'with starting actions to match. Ask me here any time you want to change it.',
                            'ninja-forms'
                        ),
                        $title,
                        $field_count
                    ),
                    'user_id' => 0,
                    'time'    => time(),
                ),
            )
        );
        if (is_wp_error($stored)) {
            $this->failIncompleteGeneration((int) $result['form_id'], $requestToken, $stored);
        }

        $completed = ConversationStore::completeGeneration(
            $requestToken,
            (int) $result['form_id']
        );
        if (is_wp_error($completed)) {
            $this->failIncompleteGeneration((int) $result['form_id'], $requestToken, $completed);
        }

        return array( 'form_id' => $result[ 'form_id' ] );
    }

    /**
     * Remove a form whose post-creation bootstrap did not finish.
     *
     * @param int      $formId Generated form ID.
     * @param string   $requestToken Generation request token.
     * @param WP_Error $error        Bootstrap failure.
     * @return void
     */
    private function failIncompleteGeneration(int $formId, string $requestToken, WP_Error $error): void
    {
        $cleanup = $this->cleanupIncompleteGeneration($formId, $requestToken);
        $this->_errors[] = is_wp_error($cleanup)
            ? esc_html($cleanup->get_error_message())
            : esc_html($error->get_error_message());
        $this->_respond();
    }

    /**
     * Remove and verify every durable row from an incomplete generation.
     *
     * @param int    $formId       Generated form ID, or zero before creation.
     * @param string $requestToken Generation request token.
     * @return true|WP_Error Cleanup result.
     */
    private function cleanupIncompleteGeneration(int $formId, string $requestToken)
    {
        $conversationDeleted = true;
        $children = array();
        if ($formId) {
            $children = ninja_forms_ability_capture_generated_form_children($formId);
            $conversationDeleted = ConversationStore::deleteForm($formId);
            $form = Ninja_Forms()->form($formId)->get();
            if ($form && $form->get_id()) {
                $form->delete();
            }
        }
        $requestReleased = ConversationStore::releaseGeneration($requestToken);
        $verified = $formId
            ? ninja_forms_ability_verify_generated_form_absent($formId, $children)
            : true;
        if (is_wp_error($conversationDeleted) || is_wp_error($requestReleased) || is_wp_error($verified)) {
            return new WP_Error(
                'nf_ai_generation_cleanup_failed',
                __('The incomplete generated form could not be removed safely.', 'ninja-forms')
            );
        }

        return true;
    }

    /**
     * Confirm that a completed request still points to a durable form.
     *
     * @param int $formId Form ID.
     * @return bool Whether the form row exists.
     */
    private function formExists(int $formId): bool
    {
        return FormLookup::exists($formId);
    }

    /**
     * Get sanitized request data.
     *
     * @return array Sanitized generation request data.
     */
    protected function get_request_data(): array
    {
        $request_data = array();

        if (isset($_REQUEST[ 'prompt' ])) {
            $prompt = sanitize_textarea_field(wp_unslash($_REQUEST[ 'prompt' ]));
            $request_data[ 'prompt' ] = trim($prompt);
        }

        if (isset($_REQUEST[ 'provider' ])) {
            $request_data[ 'provider' ] = sanitize_key(wp_unslash($_REQUEST[ 'provider' ]));
        }

        if (isset($_REQUEST['model'])) {
            $request_data['model'] = sanitize_text_field(wp_unslash($_REQUEST['model']));
        }

        if (isset($_REQUEST['request_token'])) {
            $request_data['request_token'] = sanitize_text_field(
                wp_unslash($_REQUEST['request_token'])
            );
        }
        $request_data['request_status'] = ! empty($_REQUEST['request_status']);

        return $request_data;
    }
}
