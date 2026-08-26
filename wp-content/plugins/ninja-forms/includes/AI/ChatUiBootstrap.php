<?php

namespace NinjaForms\Includes\AI;

/**
 * Builder UI bootstrap and lifecycle hooks for the AI assistant.
 */
class ChatUiBootstrap
{
    /**
     * Register builder and form-lifecycle hooks.
     *
     * @return void
     */
    public function register(): void
    {
        add_action('nf_admin_enqueue_scripts', array($this, 'enqueue'), 10, 0);
        add_action('ninja_forms_after_form_delete', array($this, 'deleteFormData'), 10, 1);
        add_action(
            ConversationStore::PENDING_CLEANUP_HOOK,
            array(ConversationStore::class, 'expirePending'),
            10,
            2
        );
        add_action(
            ConversationStore::GENERATION_CLEANUP_HOOK,
            array(ConversationStore::class, 'expireGeneration'),
            10,
            1
        );
    }

    /**
     * Delete assistant state attached to a deleted form.
     *
     * @param int $formId Form ID.
     * @return void
     */
    public function deleteFormData(int $formId): void
    {
        $result = ConversationStore::deleteForm($formId);
        if (is_wp_error($result) && defined('WP_DEBUG') && WP_DEBUG) {
            error_log($result->get_error_message());
        }
    }

    /**
     * Supply the builder drawer with connected models, state, and copy.
     *
     * @return void
     */
    public function enqueue(): void
    {
        if (! FeaturePolicy::canRender()) {
            return;
        }

        /*
         * A form being built by hand has no row yet, so form_id is "new" and
         * absint gives 0. Bailing here removed the assistant from the builder
         * entirely, with nothing to say why: a reviewer who had spent eight
         * passes inside this feature concluded it worked only on AI-generated
         * forms, which is the opposite of the capability being offered. The
         * control now loads either way and asks for a name, because the
         * conversation and every edit are filed against the form's id.
         */
        $formId = isset($_GET['form_id']) ? absint($_GET['form_id']) : 0;

        $providers = ProviderManager::getAvailableProviders();
        $storedChoice = $formId
            ? ConversationStore::getChoice($formId)
            : array('provider' => '', 'model' => '');
        $choice = ProviderManager::resolveModelChoice($providers, '', '', $storedChoice);
        $disconnected = is_wp_error($choice) && '' !== $storedChoice['provider'];
        if (is_wp_error($choice)) {
            $choice = ProviderManager::emptyChoice();
        }

        wp_enqueue_style(
            'nf-ai-chat',
            \Ninja_Forms::$url . 'assets/css/nf-ai-chat.css',
            array('dashicons'),
            \Ninja_Forms::asset_version('assets/css/nf-ai-chat.css')
        );
        wp_localize_script(
            'nf-builder',
            'nfAiChat',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('nf_ai_chat'),
                'formId' => $formId,
                'formSaved' => (bool) $formId,
                'connectUrl' => admin_url('options-connectors.php'),
                'history' => $formId ? $this->displayHistory($formId) : array(),
                'currentUser' => $this->currentUserIdentity(),
                'assistant' => $this->assistantIdentity(),
                'providers' => $providers,
                'provider' => $choice['provider'],
                'model' => $choice['model'],
                'providerDisconnected' => $disconnected,
                'i18n' => $this->localizedCopy(),
            )
        );
    }

    /**
     * Enrich accepted history for display.
     *
     * @param int $formId Form ID.
     * @return array
     */
    private function displayHistory(int $formId): array
    {
        $assistant = $this->assistantIdentity();
        $users = array();
        $display = array();
        foreach (ConversationStore::getHistory($formId) as $message) {
            $row = array(
                'role' => $message['role'],
                'content' => $message['content'],
                'time' => (int) ($message['time'] ?? 0),
            );
            if ('assistant' === $message['role']) {
                $row = array_merge($row, $assistant);
            } else {
                $userId = (int) ($message['user_id'] ?? 0);
                if (! isset($users[$userId])) {
                    $user = $userId ? get_userdata($userId) : false;
                    $users[$userId] = array(
                        'name' => $user ? $user->display_name : __('User', 'ninja-forms'),
                        'avatar' => get_avatar_url($userId, array('size' => 48)),
                    );
                }
                $row = array_merge($row, $users[$userId]);
            }
            $display[] = $row;
        }

        return $display;
    }

    /**
     * Get current-user display data.
     *
     * @return array{name:string,avatar:string}
     */
    private function currentUserIdentity(): array
    {
        $user = wp_get_current_user();

        return array(
            'name' => $user->display_name,
            'avatar' => get_avatar_url($user->ID, array('size' => 48)),
        );
    }

    /**
     * Get assistant display data.
     *
     * @return array{name:string,avatar:string}
     */
    private function assistantIdentity(): array
    {
        return array(
            'name' => __('Ninja Forms', 'ninja-forms'),
            'avatar' => \Ninja_Forms::$url . 'assets/img/nf-ai-avatar.png',
        );
    }

    /**
     * Get localized strings used by the drawer module.
     *
     * @return array<string,string>
     */
    private function localizedCopy(): array
    {
        return array_merge(self::connectCopy(), array(
            'greeting' => __(
                'Tell me what to change. I use the form as shown in the builder, including unpublished edits.',
                'ninja-forms'
            ),
            'working' => __('Working…', 'ninja-forms'),
            'saveFirstTitle' => __('Save your form to use AI', 'ninja-forms'),
            'saveFirstRequired' => __('Give the form a name to continue.', 'ninja-forms'),
            'error' => __('Something went wrong. Please try again.', 'ninja-forms'),
            'applyError' => __(
                'AI finished, but the draft could not be applied. No AI changes were published; please try again.',
                'ninja-forms'
            ),
            'undo' => __('Undo', 'ninja-forms'),
            'undoRequest' => __('Undo the last AI change.', 'ninja-forms'),
            'undoComplete' => __('Done — I reverted my last AI change.', 'ninja-forms'),
            'undoApplyError' => __(
                'Undo could not restore the form exactly. Your current draft was left intact; try again.',
                'ninja-forms'
            ),
            'undoPersistError' => __(
                'Undo could not be recorded, so I kept the AI change. Start a new chat before making another AI edit.',
                'ninja-forms'
            ),
            'exchangeCommitError' => __(
                'The chat was not saved, so I restored the form to its earlier state. Please try again.',
                'ninja-forms'
            ),
            'recoveryRequired' => __(
                'The form could not be restored exactly. Reload the builder before making or publishing changes.',
                'ninja-forms'
            ),
            'providerChoose' => __('Choose a model', 'ninja-forms'),
            'providerRequired' => __('Choose an AI model before sending your message.', 'ninja-forms'),
            'providerDisconnected' => __(
                'The model selected for this form is no longer available. Choose another model to continue.',
                'ninja-forms'
            ),
            'selectionPersistError' => __(
                'That model could not be attached to this form. Your previous model is still selected.',
                'ninja-forms'
            ),
            'showAllModels' => __('Show all compatible models', 'ninja-forms'),
            'startNewChatConfirm' => __(
                'This clears the conversation history and AI Undo. Your form and selected AI model will not change.',
                'ninja-forms'
            ),
            'startNewChatButton' => __('Start new chat', 'ninja-forms'),
            'cancel' => __('Cancel', 'ninja-forms'),
            'startNewChatError' => __('The chat could not be cleared. Please try again.', 'ninja-forms'),
            'changedWhileWorking' => __(
                'The form changed while AI was working. Send again to use the latest draft.',
                'ninja-forms'
            ),
        ));
    }

    /**
     * Get the copy shown wherever AI is offered without a connected provider.
     *
     * One source for two surfaces. The drawer renders these from the localized
     * bundle and the dashboard generation modal renders the same words as
     * server-side markup (GenerationUi::getModalContent), so keeping the
     * strings here stops the two explanations drifting apart and stops the
     * same paragraph reaching translators twice.
     *
     * @return array<string,string>
     */
    public static function connectCopy(): array
    {
        return array(
            'connectTitle' => __('Connect an AI provider', 'ninja-forms'),
            'connectBody' => __(
                'Building a form with AI uses the AI provider connected to your WordPress site, under your own account. Connect Anthropic or OpenAI in Settings → Connectors, then come back here.',
                'ninja-forms'
            ),
            'connectGoogle' => __(
                'Google is not available yet. The AI Provider for Google plugin cannot carry a conversation beyond one reply. We will add Google.',
                'ninja-forms'
            ),
            'connectAction' => __('Connect a provider', 'ninja-forms'),
        );
    }
}
