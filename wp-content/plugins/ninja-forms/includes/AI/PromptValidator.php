<?php

namespace NinjaForms\Includes\AI;

/**
 * Validates AI prompt boundaries without modifying the submitted text.
 */
class PromptValidator
{
    /**
     * Return an error when a prompt exceeds its supported character count.
     *
     * @param string $prompt Sanitized prompt.
     * @param int    $limit  Maximum Unicode character count.
     * @return null|\WP_Error
     */
    public static function lengthError(string $prompt, int $limit): ?\WP_Error
    {
        $prompt_length = mb_strlen($prompt);
        if ($limit >= $prompt_length) {
            return null;
        }

        return new \WP_Error(
            'nf_ai_prompt_too_long',
            sprintf(
                /* translators: 1: characters over the limit, 2: maximum character count. */
                esc_html__(
                    'Your description exceeds the %2$d-character limit by %1$d. Shorten it and try again.',
                    'ninja-forms'
                ),
                $prompt_length - $limit,
                $limit
            )
        );
    }
}
