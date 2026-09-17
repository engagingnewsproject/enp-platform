<?php

namespace NinjaForms\Includes\AI;

/**
 * Shared storage lookups for the AI endpoints.
 */
class FormLookup
{
    /**
     * Whether a form row is durable in storage.
     *
     * Both AI endpoints need the same answer before they trust a form ID they
     * were handed, so the existence query lives here rather than in each
     * controller.
     *
     * @param int $formId Form ID.
     * @return bool Whether the form row exists.
     */
    public static function exists(int $formId): bool
    {
        if (! $formId) {
            return false;
        }

        global $wpdb;

        return $formId === (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}nf3_forms WHERE id = %d",
                $formId
            )
        );
    }
}
