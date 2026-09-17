<?php

namespace NinjaForms\Includes\AI;

use WP_Error;

/**
 * Converts a plain-language request into a persisted form through create-form.
 */
class FormGenerationService
{
    /**
     * Generate and persist one form.
     *
     * @param string $prompt    User description.
     * @param array  $choice    Resolved provider/model choice.
     * @param array  $providers Available providers for display names.
     * @return array|WP_Error
     */
    public function generate(string $prompt, array $choice, array $providers)
    {
        $ability = function_exists('wp_get_ability')
            ? wp_get_ability('ninjaforms/create-form')
            : null;
        if (! $ability) {
            return new WP_Error(
                'nf_ai_generation_unavailable',
                __('Form generation is unavailable on this site.', 'ninja-forms')
            );
        }

        $schema = $this->prepareSchema($ability->get_input_schema());
        $json = ProviderManager::prompt($prompt, $choice['provider'], $choice['model'])
            ->using_system_instruction($this->getSystemInstruction($schema))
            ->as_json_response()
            ->generate_text();
        if (is_wp_error($json)) {
            $error = ProviderManager::normalizeError(
                $json,
                ProviderManager::getProviderName($choice['provider'], $providers)
            );
            if ('nf_ai_provider_authentication' === $error->get_error_code()) {
                ProviderManager::markAuthenticationFailed($choice['provider']);
            }

            return $error;
        }

        $spec = $this->decodeSpec((string) $json);
        if (! is_array($spec) || empty($spec['title'])) {
            return new WP_Error(
                'nf_ai_generation_invalid',
                // phpcs:ignore Generic.Files.LineLength.TooLong -- Translator-safe string.
                __('The AI response was not a valid form definition. Please try rephrasing your description.', 'ninja-forms')
            );
        }

        $result = $ability->execute($spec);
        if (is_wp_error($result)) {
            return new WP_Error(
                'nf_ai_generation_save_failed',
                sprintf(
                    /* translators: %s: form save error. */
                    __('The generated form could not be saved: %s', 'ninja-forms'),
                    $result->get_error_message()
                )
            );
        }
        if (empty($result['form_id'])) {
            return new WP_Error(
                'nf_ai_generation_save_failed',
                __('The generated form could not be saved. Please try again.', 'ninja-forms')
            );
        }

        $result['title'] = sanitize_text_field($spec['title']);

        return $result;
    }

    /**
     * Prepare an ability schema for provider structured-output requirements.
     *
     * @param array $schema Ability input schema.
     * @return array
     */
    private function prepareSchema(array $schema): array
    {
        if ('object' === ($schema['type'] ?? '') && ! isset($schema['additionalProperties'])) {
            $schema['additionalProperties'] = false;
        }
        if (! empty($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $name => $property) {
                if (is_array($property)) {
                    $schema['properties'][$name] = $this->prepareSchema($property);
                }
            }
        }
        if (! empty($schema['items']) && is_array($schema['items'])) {
            $schema['items'] = $this->prepareSchema($schema['items']);
        }

        return $schema;
    }

    /**
     * Decode JSON, tolerate code fences, and remove null optional values.
     *
     * @param string $json Provider response.
     * @return array|null
     */
    private function decodeSpec(string $json): ?array
    {
        $json = trim($json);
        if (0 === strpos($json, '```')) {
            $json = preg_replace('/^```[a-z]*\s*/i', '', $json);
            $json = preg_replace('/\s*```$/', '', $json);
        }
        $spec = json_decode($json, true);

        return is_array($spec) ? $this->stripNulls($spec) : null;
    }

    /**
     * Recursively remove null optional values.
     *
     * @param array $data Decoded form definition.
     * @return array
     */
    private function stripNulls(array $data): array
    {
        foreach ($data as $key => $value) {
            if (null === $value) {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = $this->stripNulls($value);
            }
        }

        return $data;
    }

    /**
     * Get the schema-driven generation instruction.
     *
     * @param array $schema Prepared create-form schema.
     * @return string
     */
    private function getSystemInstruction(array $schema): string
    {
        return 'You generate Ninja Forms form definitions. Respond with one JSON object matching the schema, '
            . 'with no prose or markdown. Include a title and every field the user described. Use only schema '
            . 'field types; use liststate for US states, listcountry for countries, listselect for a plain '
            . 'dropdown, and textbox when nothing else fits. Follow every property description. Mark fields '
            . 'required only when explicitly requested. Give each field a snake_case key followed by a '
            . '13-digit millisecond timestamp and reuse that exact key in references. Do not include Submit; '
            . 'it is added automatically. Add only actions that fit the request. Usually add an admin email to '
            . '"{wp:admin_email}" with "{fields_table}". When collecting a submitter email, also add a short '
            . 'confirmation to that field merge tag. Add a specific success message. Add redirect only when '
            . 'requested. Omit optional properties rather than returning null or empty values.'
            . "\n\nJSON Schema:\n" . wp_json_encode($schema);
    }
}
