<?php

namespace NinjaForms\Includes\AI;

/**
 * Persists checked provider/model choices through one shared boundary.
 */
class ModelAttachment
{
    /**
     * Remember a resolved choice as the global default.
     *
     * @param array $choice Resolved provider/model choice.
     * @return true|\WP_Error
     */
    public static function remember(array $choice)
    {
        $result = ProviderManager::rememberModelChoice($choice);

        return is_wp_error($result) ? $result : true;
    }

    /**
     * Attach a resolved choice to a form and remember it as the global default.
     *
     * @param int   $formId Form ID.
     * @param array $choice Resolved provider/model choice.
     * @return true|\WP_Error
     */
    public static function attach(int $formId, array $choice)
    {
        if (empty($choice['provider']) || empty($choice['model'])) {
            return new \WP_Error(
                'nf_ai_model_unavailable',
                __('Choose an available AI model to continue.', 'ninja-forms')
            );
        }

        $previous = ConversationStore::getChoice($formId);
        $stored = ConversationStore::setChoice(
            $formId,
            (string) $choice['provider'],
            (string) $choice['model']
        );
        if (is_wp_error($stored)) {
            return $stored;
        }
        if (false === $stored) {
            return new \WP_Error(
                'nf_ai_model_save_failed',
                __('That AI model could not be attached to this form.', 'ninja-forms')
            );
        }

        $remembered = self::remember($choice);
        if (! is_wp_error($remembered)) {
            return true;
        }

        $rolledBack = ! empty($previous['provider']) && ! empty($previous['model'])
            ? ConversationStore::setChoice($formId, $previous['provider'], $previous['model'])
            : ConversationStore::deleteChoice($formId);
        if (is_wp_error($rolledBack)) {
            return $rolledBack;
        }

        return $remembered;
    }
}
