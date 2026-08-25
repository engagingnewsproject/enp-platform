<?php

namespace NinjaForms\Includes\AI;

/**
 * Persistent conversation, pending exchange, and model-choice storage.
 *
 * Accepted exchanges are stored as independent options. This avoids the
 * lost-update race inherent in read/modify/write of one history option when
 * two browser sessions complete at the same time.
 */
class ConversationStore
{
    public const LEGACY_HISTORY_PREFIX = 'nf_ai_chat_log_';
    public const LEGACY_PROVIDER_PREFIX = 'nf_ai_chat_provider_';
    public const LEGACY_MODEL_PREFIX = 'nf_ai_chat_model_';
    public const LEGACY_UNDO_PREFIX = 'nf_ai_chat_undo_';
    public const CHOICE_PREFIX = 'nf_ai_chat_choice_';
    public const EXCHANGE_PREFIX = 'nf_ai_chat_exchange_';
    public const PENDING_PREFIX = 'nf_ai_chat_pending_';
    public const GENERATION_PREFIX = 'nf_ai_generation_request_';
    public const PENDING_CLEANUP_HOOK = 'nf_ai_chat_cleanup_pending_exchange';
    public const GENERATION_CLEANUP_HOOK = 'nf_ai_cleanup_generation_request';
    public const MAX_STORED = 40;
    public const PENDING_TTL = 900;
    public const GENERATION_TTL = 900;

    /**
     * Get the accepted history for a form in creation order.
     *
     * @param int $formId Form ID.
     * @return array<int,array<string,mixed>>
     */
    public static function getHistory(int $formId): array
    {
        $history = self::getLegacyHistory($formId);
        foreach (self::getRows(self::EXCHANGE_PREFIX, $formId) as $exchange) {
            if (
                'pending_reversion' === ($exchange['transition'] ?? '')
                && ! self::isCommittedReversion($formId, $exchange)
            ) {
                continue;
            }
            if (! empty($exchange['messages']) && is_array($exchange['messages'])) {
                $history = array_merge($history, $exchange['messages']);
            }
        }

        return array_slice($history, -self::MAX_STORED);
    }

    /**
     * Append an already accepted exchange.
     *
     * Used when generation creates a form before a builder client exists.
     *
     * @param int   $formId  Form ID.
     * @param array $messages Conversation rows.
     * @return string|\WP_Error Exchange token, or a storage error.
     */
    public static function appendAccepted(int $formId, array $messages)
    {
        $token = self::newToken();
        $result = self::writeOption(
            self::optionName(self::EXCHANGE_PREFIX, $formId, $token),
            array(
                'created'  => time(),
                'messages' => self::normalizeMessages($messages, $token),
            )
        );
        if (is_wp_error($result)) {
            return $result;
        }

        $result = self::pruneAccepted($formId);
        if (is_wp_error($result)) {
            self::reportMaintenanceFailure($result);
        }

        return $token;
    }

    /**
     * Stage an exchange until the browser confirms exact draft application.
     *
     * @param int   $formId   Form ID.
     * @param array $messages Conversation rows.
     * @return string|\WP_Error Pending exchange token, or a storage error.
     */
    public static function stage(int $formId, array $messages)
    {
        $result = self::cleanupExpiredPending($formId);
        if (is_wp_error($result)) {
            return $result;
        }

        $token = self::newToken();
        $pendingName = self::optionName(self::PENDING_PREFIX, $formId, $token);
        $result = self::writeOption(
            $pendingName,
            array(
                'created'  => time(),
                'messages' => self::normalizeMessages($messages, $token),
            )
        );
        if (is_wp_error($result)) {
            return $result;
        }

        $scheduled = wp_schedule_single_event(
            time() + self::PENDING_TTL,
            self::PENDING_CLEANUP_HOOK,
            array($formId, $token),
            true
        );
        if (is_wp_error($scheduled) || ! $scheduled) {
            $deleted = self::deleteOption($pendingName);
            if (is_wp_error($deleted)) {
                return $deleted;
            }

            return self::storageError(
                'nf_ai_storage_schedule_failed',
                __('The pending AI edit could not be scheduled for safe cleanup.', 'ninja-forms')
            );
        }

        return $token;
    }

    /**
     * Move a pending exchange into accepted history.
     *
     * @param int    $formId Form ID.
     * @param string $token  Exchange token.
     * @return bool|\WP_Error Whether the exchange was accepted, or a storage error.
     */
    public static function accept(int $formId, string $token)
    {
        $token = self::sanitizeToken($token);
        if ('' === $token) {
            return false;
        }

        $acceptedName = self::optionName(self::EXCHANGE_PREFIX, $formId, $token);
        $pendingName = self::optionName(self::PENDING_PREFIX, $formId, $token);
        if (is_array(get_option($acceptedName, null))) {
            $result = self::deleteOption($pendingName);
            if (is_wp_error($result)) {
                self::reportMaintenanceFailure($result);
            }

            $result = self::pruneAccepted($formId);
            if (is_wp_error($result)) {
                self::reportMaintenanceFailure($result);
            }

            return true;
        }

        $pending = get_option($pendingName, null);
        if (! is_array($pending) || empty($pending['messages'])) {
            return false;
        }
        if (empty($pending['created']) || (int) $pending['created'] <= time() - self::PENDING_TTL) {
            $result = self::deleteOption($pendingName);

            return is_wp_error($result) ? $result : false;
        }

        $result = self::writeOption($acceptedName, $pending);
        if (is_wp_error($result)) {
            return $result;
        }
        $result = self::deleteOption($pendingName);
        if (is_wp_error($result)) {
            self::reportMaintenanceFailure($result);
        }

        $result = self::pruneAccepted($formId);
        if (is_wp_error($result)) {
            self::reportMaintenanceFailure($result);
        }

        return true;
    }

    /**
     * Discard an exchange that the browser did not apply.
     *
     * @param int    $formId Form ID.
     * @param string $token  Exchange token.
     * @return bool|\WP_Error Whether the pending row is absent, or a storage error.
     */
    public static function discard(int $formId, string $token)
    {
        $token = self::sanitizeToken($token);

        if ('' === $token) {
            return false;
        }

        return self::deleteOption(self::optionName(self::PENDING_PREFIX, $formId, $token));
    }

    /**
     * Record that the client successfully reverted an accepted exchange.
     *
     * @param int    $formId Form ID.
     * @param string $token  Accepted exchange token.
     * @param int    $userId Current user ID.
     * @return bool|\WP_Error Whether a matching accepted exchange exists, or a storage error.
     */
    public static function recordReversion(int $formId, string $token, int $userId)
    {
        $token = self::sanitizeToken($token);
        if ('' === $token) {
            return false;
        }

        $acceptedName = self::optionName(self::EXCHANGE_PREFIX, $formId, $token);
        $accepted = get_option($acceptedName, null);
        if (! is_array($accepted)) {
            return false;
        }
        if (! empty($accepted['reverted']) && ! empty($accepted['reversion_exchange'])) {
            $linked = get_option(
                self::optionName(
                    self::EXCHANGE_PREFIX,
                    $formId,
                    (string) $accepted['reversion_exchange']
                ),
                null
            );

            return is_array($linked);
        }
        $reversionToken = self::findReversionToken($formId, $token);
        if ('' === $reversionToken) {
            $reversionToken = self::newToken();
            $result = self::writeOption(
                self::optionName(self::EXCHANGE_PREFIX, $formId, $reversionToken),
                array(
                    'created'    => time(),
                    'transition' => 'pending_reversion',
                    'reverts'    => $token,
                    'messages'   => self::normalizeMessages(
                        array(
                            array(
                                'role'    => 'user',
                                'content' => __('Undo the last AI change.', 'ninja-forms'),
                                'user_id' => $userId,
                                'time'    => time(),
                                'reverts' => $token,
                            ),
                            array(
                                'role'    => 'assistant',
                                'content' => __('Done — I reverted my last AI change.', 'ninja-forms'),
                                'user_id' => 0,
                                'time'    => time(),
                                'reverts' => $token,
                            ),
                        ),
                        $reversionToken
                    ),
                )
            );
            if (is_wp_error($result)) {
                return $result;
            }
        }

        $accepted['reverted'] = true;
        $accepted['reversion_exchange'] = $reversionToken;
        $result = self::writeOption($acceptedName, $accepted);
        if (is_wp_error($result)) {
            return $result;
        }

        $result = self::pruneAccepted($formId);
        if (is_wp_error($result)) {
            self::reportMaintenanceFailure($result);
        }

        $storedOriginal = get_option($acceptedName, null);
        $storedReversion = get_option(
            self::optionName(self::EXCHANGE_PREFIX, $formId, $reversionToken),
            null
        );
        if (
            ! is_array($storedOriginal)
            || empty($storedOriginal['reverted'])
            || $reversionToken !== ($storedOriginal['reversion_exchange'] ?? '')
            || ! is_array($storedReversion)
        ) {
            return self::storageError(
                'nf_ai_storage_undo_failed',
                __('The AI Undo record could not be verified.', 'ninja-forms')
            );
        }

        return true;
    }

    /**
     * Determine whether an invisible Undo exchange has a committed original.
     *
     * @param int   $formId  Form ID.
     * @param array $exchange Pending reversion exchange.
     * @return bool Whether the original durably points back to this exchange.
     */
    private static function isCommittedReversion(int $formId, array $exchange): bool
    {
        $originalToken = self::sanitizeToken((string) ($exchange['reverts'] ?? ''));
        if ('' === $originalToken || empty($exchange['messages'][0]['exchange'])) {
            return false;
        }
        $reversionToken = self::sanitizeToken(
            (string) $exchange['messages'][0]['exchange']
        );
        $original = get_option(
            self::optionName(self::EXCHANGE_PREFIX, $formId, $originalToken),
            null
        );

        return is_array($original)
            && ! empty($original['reverted'])
            && $reversionToken === ($original['reversion_exchange'] ?? '');
    }

    /**
     * Query the durable lifecycle state of one exchange.
     *
     * Clients use this after an ambiguous transport failure before changing
     * the browser draft in the opposite direction.
     *
     * @param int    $formId Form ID.
     * @param string $token  Exchange token.
     * @return array{accepted:bool,pending:bool,reverted:bool}
     */
    public static function getExchangeState(int $formId, string $token): array
    {
        $token = self::sanitizeToken($token);
        if ('' === $token) {
            return array(
                'accepted' => false,
                'pending'  => false,
                'reverted' => false,
            );
        }

        $accepted = get_option(
            self::optionName(self::EXCHANGE_PREFIX, $formId, $token),
            null
        );
        $pending = get_option(
            self::optionName(self::PENDING_PREFIX, $formId, $token),
            null
        );

        $reverted = false;
        if (
            is_array($accepted)
            && ! empty($accepted['reverted'])
            && ! empty($accepted['reversion_exchange'])
        ) {
            $reversion = get_option(
                self::optionName(
                    self::EXCHANGE_PREFIX,
                    $formId,
                    (string) $accepted['reversion_exchange']
                ),
                null
            );
            $reverted = is_array($reversion)
                && self::isCommittedReversion($formId, $reversion);
        }

        return array(
            'accepted' => is_array($accepted),
            'pending'  => is_array($pending),
            'reverted' => $reverted,
        );
    }

    /**
     * Get a form's exact provider/model pair, including legacy migration.
     *
     * @param int $formId Form ID.
     * @return array{provider:string,model:string}
     */
    public static function getChoice(int $formId): array
    {
        $choice = get_option(self::CHOICE_PREFIX . $formId, array());
        if (! is_array($choice) || empty($choice['provider'])) {
            $choice = array(
                'provider' => get_option(self::LEGACY_PROVIDER_PREFIX . $formId, ''),
                'model'    => get_option(self::LEGACY_MODEL_PREFIX . $formId, ''),
            );
        }

        return array(
            'provider' => sanitize_key((string) ($choice['provider'] ?? '')),
            'model'    => sanitize_text_field((string) ($choice['model'] ?? '')),
        );
    }

    /**
     * Persist an exact provider/model pair in one option.
     *
     * @param int    $formId    Form ID.
     * @param string $providerId Provider ID.
     * @param string $modelId    Model ID.
     * @return bool|\WP_Error Whether the choice was stored, or a storage error.
     */
    public static function setChoice(int $formId, string $providerId, string $modelId)
    {
        $result = self::writeOption(
            self::CHOICE_PREFIX . $formId,
            array(
                'provider' => sanitize_key($providerId),
                'model'    => sanitize_text_field($modelId),
            )
        );
        if (is_wp_error($result)) {
            return $result;
        }

        return self::deleteOptions(
            array(
                self::LEGACY_PROVIDER_PREFIX . $formId,
                self::LEGACY_MODEL_PREFIX . $formId,
            )
        );
    }

    /**
     * Delete only the attached model choice.
     *
     * @param int $formId Form ID.
     * @return bool|\WP_Error Whether all choice rows are absent, or a storage error.
     */
    public static function deleteChoice(int $formId)
    {
        return self::deleteOptions(
            array(
                self::LEGACY_PROVIDER_PREFIX . $formId,
                self::LEGACY_MODEL_PREFIX . $formId,
                self::CHOICE_PREFIX . $formId,
            )
        );
    }

    /**
     * Clear conversation context while retaining the form and model choice.
     *
     * @param int $formId Form ID.
     * @return bool|\WP_Error Whether all context rows are absent, or a storage error.
     */
    public static function clearContext(int $formId)
    {
        $result = self::deleteOption(self::LEGACY_HISTORY_PREFIX . $formId);
        if (is_wp_error($result)) {
            return $result;
        }
        $result = self::deleteRows(self::EXCHANGE_PREFIX, $formId);
        if (is_wp_error($result)) {
            return $result;
        }

        return self::deleteRows(self::PENDING_PREFIX, $formId);
    }

    /**
     * Delete every per-form conversation datum.
     *
     * @param int $formId Form ID.
     * @return bool|\WP_Error Whether all per-form rows are absent, or a storage error.
     */
    public static function deleteForm(int $formId)
    {
        $result = self::clearContext($formId);
        if (is_wp_error($result)) {
            return $result;
        }
        $result = self::deleteChoice($formId);
        if (is_wp_error($result)) {
            return $result;
        }

        $result = self::deleteOption(self::LEGACY_UNDO_PREFIX . $formId);
        if (is_wp_error($result)) {
            return $result;
        }

        return self::deleteGenerationForForm($formId);
    }

    /**
     * Atomically claim one client-generated form-generation request.
     *
     * @param string $token Client request token.
     * @return array{status:string,form_id:int,expired:bool,claimed:bool}|\WP_Error Request state.
     */
    public static function claimGeneration(string $token)
    {
        $token = self::sanitizeToken($token);
        if ('' === $token) {
            return self::storageError(
                'nf_ai_generation_token_invalid',
                __('The form-generation request token is invalid.', 'ninja-forms')
            );
        }
        $name = self::GENERATION_PREFIX . $token;
        $state = array('status' => 'processing', 'form_id' => 0, 'created' => time());
        if (add_option($name, $state, '', false)) {
            self::scheduleGenerationCleanup($token);

            return array(
                'status'  => 'processing',
                'form_id' => 0,
                'expired' => false,
                'claimed' => true,
            );
        }
        $existing = self::getGenerationState($token);
        if (is_wp_error($existing)) {
            return $existing;
        }
        $existing['claimed'] = false;

        return $existing;
    }

    /**
     * Read one durable form-generation request outcome.
     *
     * @param string $token Client request token.
     * @return array{status:string,form_id:int,expired:bool}|\WP_Error Request state.
     */
    public static function getGenerationState(string $token)
    {
        $token = self::sanitizeToken($token);
        if ('' === $token) {
            return self::storageError(
                'nf_ai_generation_token_invalid',
                __('The form-generation request token is invalid.', 'ninja-forms')
            );
        }
        $state = get_option(self::GENERATION_PREFIX . $token, null);
        if (! is_array($state)) {
            return array('status' => 'missing', 'form_id' => 0, 'expired' => false);
        }

        return array(
            'status'  => 'complete' === ($state['status'] ?? '') ? 'complete' : 'processing',
            'form_id' => (int) ($state['form_id'] ?? 0),
            'expired' => 'complete' !== ($state['status'] ?? '')
                && (! isset($state['created']) || (int) $state['created'] <= time() - self::GENERATION_TTL),
        );
    }

    /**
     * Attach the generated form ID to an in-flight request lease.
     *
     * A later retry can remove the form if the PHP request terminates before
     * model attachment, history seeding, and completion all finish.
     *
     * @param string $token  Client request token.
     * @param int    $formId Generated form ID.
     * @return true|\WP_Error Persistence result.
     */
    public static function trackGenerationForm(string $token, int $formId)
    {
        $token = self::sanitizeToken($token);
        if ('' === $token || ! $formId) {
            return self::storageError(
                'nf_ai_generation_tracking_invalid',
                __('The generated form could not be tracked safely.', 'ninja-forms')
            );
        }

        return self::writeOption(
            self::GENERATION_PREFIX . $token,
            array('status' => 'processing', 'form_id' => $formId, 'created' => time())
        );
    }

    /**
     * Persist and verify one request-to-form outcome.
     *
     * @param string $token  Client request token.
     * @param int    $formId Generated form ID.
     * @return true|\WP_Error Completion result.
     */
    public static function completeGeneration(string $token, int $formId)
    {
        $token = self::sanitizeToken($token);
        if ('' === $token || ! $formId) {
            return self::storageError(
                'nf_ai_generation_completion_invalid',
                __('The generated form outcome could not be recorded.', 'ninja-forms')
            );
        }

        return self::writeOption(
            self::GENERATION_PREFIX . $token,
            array('status' => 'complete', 'form_id' => $formId, 'created' => time())
        );
    }

    /**
     * Release a failed generation claim so an explicit retry can run.
     *
     * @param string $token Client request token.
     * @return true|\WP_Error Release result.
     */
    public static function releaseGeneration(string $token)
    {
        $token = self::sanitizeToken($token);

        return '' === $token
            ? true
            : self::deleteOption(self::GENERATION_PREFIX . $token);
    }

    /**
     * Delete completed generation mappings for a deleted form.
     *
     * @param int $formId Deleted form ID.
     * @return true|\WP_Error Cleanup result.
     */
    private static function deleteGenerationForForm(int $formId)
    {
        global $wpdb;

        $like = $wpdb->esc_like(self::GENERATION_PREFIX) . '%';
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
                $like
            ),
            ARRAY_A
        );
        $cutoff = time() - self::GENERATION_TTL;
        foreach ((array) $rows as $row) {
            $state = maybe_unserialize($row['option_value']);
            if (! is_array($state)) {
                continue;
            }
            if ($formId !== (int) ($state['form_id'] ?? 0)) {
                /*
                 * Drain leases the scheduled sweep could not reach — a site
                 * with cron disabled, or an event lost to a crash. Only
                 * unfinished claims that never recorded a form are swept, for
                 * the reason given on expireGeneration(); and a failure here
                 * is reported rather than returned, because this scan is
                 * housekeeping riding along with someone else's form deletion.
                 */
                if (
                    ! (int) ($state['form_id'] ?? 0)
                    && 'complete' !== ($state['status'] ?? '')
                    && (int) ($state['created'] ?? 0) <= $cutoff
                ) {
                    $drained = self::deleteOption($row['option_name']);
                    if (is_wp_error($drained)) {
                        self::reportMaintenanceFailure($drained);
                    }
                }
                continue;
            }
            $result = self::deleteOption($row['option_name']);
            if (is_wp_error($result)) {
                return $result;
            }
        }

        return true;
    }

    /**
     * Delete abandoned pending exchanges for one form.
     *
     * @param int $formId Form ID.
     * @return bool|\WP_Error Whether expired rows were removed, or a storage error.
     */
    public static function cleanupExpiredPending(int $formId)
    {
        $cutoff = time() - self::PENDING_TTL;
        foreach (self::getRows(self::PENDING_PREFIX, $formId, true) as $row) {
            if (empty($row['value']['created']) || (int) $row['value']['created'] <= $cutoff) {
                $result = self::deleteOption($row['name']);
                if (is_wp_error($result)) {
                    return $result;
                }
            }
        }

        return true;
    }

    /**
     * Delete one scheduled pending exchange after its TTL.
     *
     * @param int    $formId Form ID.
     * @param string $token  Pending exchange token.
     * @return void
     */
    public static function expirePending(int $formId, string $token): void
    {
        $result = self::discard($formId, $token);
        if (is_wp_error($result) && defined('WP_DEBUG') && WP_DEBUG) {
            error_log($result->get_error_message());
        }
    }

    /**
     * Schedule cleanup of one generation lease after its TTL.
     *
     * The lease is a permanent option holding TTL-bounded state, and its token
     * lives only in browser memory: a closed tab or a reload guarantees nobody
     * ever presents it again, so a request that dies after the claim would
     * otherwise leave the row behind for good. Scheduling mirrors stage(), but
     * a scheduling failure is only reported, never fatal — this is
     * housekeeping, and refusing to generate a form because a cron event could
     * not be queued would cost the owner far more than an unswept option row.
     *
     * @param string $token Client request token.
     * @return void
     */
    private static function scheduleGenerationCleanup(string $token): void
    {
        $scheduled = wp_schedule_single_event(
            time() + self::GENERATION_TTL,
            self::GENERATION_CLEANUP_HOOK,
            array($token),
            true
        );
        if (is_wp_error($scheduled)) {
            self::reportMaintenanceFailure($scheduled);
        }
    }

    /**
     * Delete one abandoned generation lease after its TTL.
     *
     * Only unfinished leases that never reached form creation are swept. A
     * lease that recorded a form ID is the sole record that a half-built form
     * exists, and the retry path (NF_AJAX_REST_AIFormBuilder) reads it to
     * remove that form; a completed lease is what makes recovery return the
     * generated form instead of building it twice. Deleting either would trade
     * a stray option row for a stray form.
     *
     * @param string $token Client request token.
     * @return void
     */
    public static function expireGeneration(string $token): void
    {
        $token = self::sanitizeToken($token);
        if ('' === $token) {
            return;
        }
        $state = self::getGenerationState($token);
        if (is_wp_error($state)) {
            self::reportMaintenanceFailure($state);
            return;
        }
        if ('processing' !== $state['status'] || empty($state['expired']) || $state['form_id']) {
            return;
        }

        $result = self::deleteOption(self::GENERATION_PREFIX . $token);
        if (is_wp_error($result)) {
            self::reportMaintenanceFailure($result);
        }
    }

    /**
     * Get every current and legacy AI assistant option prefix.
     *
     * @return array<int,string>
     */
    public static function optionPrefixes(): array
    {
        return array(
            self::LEGACY_HISTORY_PREFIX,
            self::LEGACY_PROVIDER_PREFIX,
            self::LEGACY_MODEL_PREFIX,
            self::LEGACY_UNDO_PREFIX,
            self::CHOICE_PREFIX,
            self::EXCHANGE_PREFIX,
            self::PENDING_PREFIX,
            self::GENERATION_PREFIX,
        );
    }

    /**
     * Normalize message rows before persistence.
     *
     * @param array  $messages Message rows.
     * @param string $token    Exchange token.
     * @return array<int,array<string,mixed>>
     */
    private static function normalizeMessages(array $messages, string $token): array
    {
        $normalized = array();
        foreach ($messages as $message) {
            if (! is_array($message) || empty($message['role'])) {
                continue;
            }
            $message['role'] = 'assistant' === $message['role'] ? 'assistant' : 'user';
            $message['content'] = sanitize_textarea_field((string) ($message['content'] ?? ''));
            $message['user_id'] = (int) ($message['user_id'] ?? 0);
            $message['time'] = (int) ($message['time'] ?? time());
            $message['exchange'] = $token;
            $normalized[] = $message;
        }

        return $normalized;
    }

    /**
     * Get legacy history safely.
     *
     * @param int $formId Form ID.
     * @return array
     */
    private static function getLegacyHistory(int $formId): array
    {
        $history = get_option(self::LEGACY_HISTORY_PREFIX . $formId, array());

        return is_array($history) ? $history : array();
    }

    /**
     * Get rows for a per-form option prefix.
     *
     * @param string $prefix      Option prefix.
     * @param int    $formId      Form ID.
     * @param bool   $withNames   Return option names alongside values.
     * @return array
     */
    private static function getRows(string $prefix, int $formId, bool $withNames = false): array
    {
        global $wpdb;

        $like = $wpdb->esc_like($prefix . $formId . '_') . '%';
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} "
                . 'WHERE option_name LIKE %s ORDER BY option_id ASC',
                $like
            ),
            ARRAY_A
        );
        $values = array();
        foreach ((array) $rows as $row) {
            $value = maybe_unserialize($row['option_value']);
            $values[] = $withNames
                ? array('name' => $row['option_name'], 'value' => $value)
                : $value;
        }

        return $values;
    }

    /**
     * Delete every row for a prefix/form pair through the Options API.
     *
     * @param string $prefix Option prefix.
     * @param int    $formId Form ID.
     * @return bool|\WP_Error Whether every row is absent, or a storage error.
     */
    private static function deleteRows(string $prefix, int $formId)
    {
        foreach (self::getRows($prefix, $formId, true) as $row) {
            $result = self::deleteOption($row['name']);
            if (is_wp_error($result)) {
                return $result;
            }
        }

        return true;
    }

    /**
     * Retain only the newest accepted exchanges needed by MAX_STORED turns.
     *
     * @param int $formId Form ID.
     * @return bool|\WP_Error Whether pruning completed, or a storage error.
     */
    private static function pruneAccepted(int $formId)
    {
        $rows = self::getRows(self::EXCHANGE_PREFIX, $formId, true);
        $turns = 0;
        $keep = array();
        for ($index = count($rows) - 1; $index >= 0; $index--) {
            $count = ! empty($rows[$index]['value']['messages'])
                ? count($rows[$index]['value']['messages'])
                : 0;
            /*
             * Stop rather than skip. Continuing past an exchange that would
             * exceed the cap goes on to consider older, smaller ones, which
             * can retain an older exchange after dropping a newer one and
             * leave a hole mid-conversation for the builder to replay.
             * Retention is a contiguous newest-first window.
             */
            if ($turns && $turns + $count > self::MAX_STORED) {
                break;
            }
            $keep[$rows[$index]['name']] = true;
            $turns += $count;
        }
        foreach ($rows as $row) {
            if (! isset($keep[$row['name']]) || empty($row['value']['messages'])) {
                continue;
            }
            if (! empty($row['value']['reversion_exchange'])) {
                $keep[self::optionName(
                    self::EXCHANGE_PREFIX,
                    $formId,
                    (string) $row['value']['reversion_exchange']
                )] = true;
            }
            foreach ($row['value']['messages'] as $message) {
                if (! is_array($message) || empty($message['reverts'])) {
                    continue;
                }
                $keep[self::optionName(
                    self::EXCHANGE_PREFIX,
                    $formId,
                    (string) $message['reverts']
                )] = true;
            }
        }
        foreach ($rows as $row) {
            if (! isset($keep[$row['name']])) {
                $result = self::deleteOption($row['name']);
                if (is_wp_error($result)) {
                    return $result;
                }
            }
        }

        return true;
    }

    /**
     * Find an already-persisted Undo exchange for an accepted exchange.
     *
     * This makes retrying Undo idempotent when writing the Undo exchange
     * succeeded but marking the original exchange reverted did not.
     *
     * @param int    $formId Form ID.
     * @param string $token  Accepted exchange token.
     * @return string Reversion exchange token, or an empty string.
     */
    private static function findReversionToken(int $formId, string $token): string
    {
        foreach (self::getRows(self::EXCHANGE_PREFIX, $formId) as $exchange) {
            if (empty($exchange['messages']) || ! is_array($exchange['messages'])) {
                continue;
            }
            foreach ($exchange['messages'] as $message) {
                if (! is_array($message) || $token !== ($message['reverts'] ?? '')) {
                    continue;
                }

                return self::sanitizeToken((string) ($message['exchange'] ?? ''));
            }
        }

        return '';
    }

    /**
     * Persist and read back an option value.
     *
     * update_option() returns false when a value is unchanged, so the
     * read-back is the source of truth for both new and idempotent writes.
     *
     * @param string $name  Option name.
     * @param mixed  $value Option value.
     * @return bool|\WP_Error Whether the exact value is durable, or a storage error.
     */
    private static function writeOption(string $name, $value)
    {
        update_option($name, $value, false);
        if ($value === get_option($name, null)) {
            return true;
        }

        return self::storageError(
            'nf_ai_storage_write_failed',
            __('The AI conversation could not be saved.', 'ninja-forms')
        );
    }

    /**
     * Delete and verify one option, treating an absent option as success.
     *
     * @param string $name Option name.
     * @return bool|\WP_Error Whether the option is absent, or a storage error.
     */
    private static function deleteOption(string $name)
    {
        delete_option($name);
        $missing = new \stdClass();
        if ($missing === get_option($name, $missing)) {
            return true;
        }

        return self::storageError(
            'nf_ai_storage_delete_failed',
            __('The AI conversation could not be cleared.', 'ninja-forms')
        );
    }

    /**
     * Delete and verify a list of options.
     *
     * @param array<int,string> $names Option names.
     * @return bool|\WP_Error Whether every option is absent, or a storage error.
     */
    private static function deleteOptions(array $names)
    {
        foreach ($names as $name) {
            $result = self::deleteOption($name);
            if (is_wp_error($result)) {
                return $result;
            }
        }

        return true;
    }

    /**
     * Build a normalized storage error.
     *
     * @param string $code    Error code.
     * @param string $message Safe error message.
     * @return \WP_Error
     */
    private static function storageError(string $code, string $message): \WP_Error
    {
        return new \WP_Error($code, $message);
    }

    /**
     * Report cleanup/pruning failures without reversing a durable transition.
     *
     * @param \WP_Error $error Maintenance failure.
     * @return void
     */
    private static function reportMaintenanceFailure(\WP_Error $error): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($error->get_error_message());
        }
    }

    /**
     * Build a tokenized per-form option name.
     *
     * @param string $prefix Option prefix.
     * @param int    $formId Form ID.
     * @param string $token  Exchange token.
     * @return string
     */
    private static function optionName(string $prefix, int $formId, string $token): string
    {
        return $prefix . $formId . '_' . self::sanitizeToken($token);
    }

    /**
     * Generate a URL-safe, unguessable option-name token.
     *
     * @return string
     */
    private static function newToken(): string
    {
        return strtolower(wp_generate_password(32, false, false));
    }

    /**
     * Restrict an exchange token to its generated alphabet.
     *
     * @param string $token Exchange token.
     * @return string
     */
    private static function sanitizeToken(string $token): string
    {
        return preg_match('/^[a-z0-9]{20,64}$/', $token) ? $token : '';
    }
}
