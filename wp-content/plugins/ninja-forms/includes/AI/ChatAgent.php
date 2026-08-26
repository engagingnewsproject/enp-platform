<?php

namespace NinjaForms\Includes\AI;

use Throwable;
use WP_AI_Client_Ability_Function_Resolver;
use WP_Error;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * Runs the provider-backed form-editing loop against an in-memory draft.
 */
class ChatAgent
{
    public const MAX_TURNS = 12;

    /**
     * Reminder carried beside the current request.
     *
     * The system instruction is resent on every call, but by then it sits
     * behind every prior turn and tool result, and a model weighs what is
     * nearest the request it is answering. Restating the two rules that cost a
     * user something when they lapse keeps them adjacent to the ask. Sent with
     * the outbound turn only: appending it to stored history would replay it as
     * though the user had demanded it again on every message.
     */
    public const TURN_REMINDER = 'Before acting: if this needs an ability you do not have, or cannot be '
        . 'undone, say so and wait rather than doing something else.';

    /** @var array<int,string> */
    public const ABILITIES = array(
        'ninjaforms/get-form',
        'ninjaforms/list-field-types',
        'ninjaforms/add-field',
        'ninjaforms/update-field',
        'ninjaforms/remove-field',
        'ninjaforms/reorder-fields',
        'ninjaforms/update-form',
        'ninjaforms/list-actions',
        'ninjaforms/add-action',
        'ninjaforms/update-action',
        'ninjaforms/delete-action',
        'ninjaforms/list-calculations',
        'ninjaforms/add-calculation',
        'ninjaforms/update-calculation',
        'ninjaforms/delete-calculation',
    );

    /**
     * Run one user request to a natural-language completion.
     *
     * @param int    $formId       Open form ID.
     * @param string $message      User request.
     * @param array  $history      Accepted prior turns.
     * @param string $providerId   Provider ID.
     * @param string $providerName Provider display name.
     * @param array  $draft        Live browser draft.
     * @param string $modelId      Exact model ID.
     * @return array|WP_Error
     */
    public function run(
        int $formId,
        string $message,
        array $history,
        string $providerId,
        string $providerName,
        array $draft,
        string $modelId
    ) {
        $abilities = array_values(
            array_filter(
                self::ABILITIES,
                static function (string $name): bool {
                    return (bool) wp_get_ability($name);
                }
            )
        );
        if (empty($abilities)) {
            return new WP_Error(
                'nf_ai_chat',
                __('The form editing abilities are unavailable on this site.', 'ninja-forms')
            );
        }

        $draftForm = new DraftForm($formId, $draft);
        $messages = $this->buildMessages($history, $message);

        for ($turn = 0; $turn < self::MAX_TURNS; $turn++) {
            $result = ProviderManager::prompt($messages, $providerId, $modelId)
                ->using_system_instruction($this->getSystemInstruction($formId))
                ->using_abilities(...$abilities)
                ->generate_text_result();
            if (is_wp_error($result)) {
                return ProviderManager::normalizeError($result, $providerName);
            }

            $modelMessage = $result->toMessage();
            $messages[] = MessageReplay::withoutThoughts($modelMessage);
            $calls = $this->getFunctionCalls($modelMessage);
            if (empty($calls)) {
                return $this->response($draftForm, $this->getText($modelMessage));
            }

            $responseParts = array();
            foreach ($calls as $call) {
                $abilityName = WP_AI_Client_Ability_Function_Resolver::function_name_to_ability_name(
                    (string) $call->getName()
                );
                /*
                 * A model may omit the arguments of a no-input tool entirely
                 * rather than sending an empty object; both are legitimate,
                 * and getArgs() returns null for the former.
                 */
                $response = $draftForm->execute($abilityName, $call->getArgs() ?? array());
                if (is_wp_error($response)) {
                    $response = array('error' => $response->get_error_message());
                }
                $responseParts[] = new MessagePart(
                    new FunctionResponse($call->getId(), $call->getName(), $response)
                );
            }
            $messages[] = new UserMessage($responseParts);
        }

        return $this->summarizeAtTurnLimit(
            $draftForm,
            $messages,
            $formId,
            $providerId,
            $modelId
        );
    }

    /**
     * Build provider messages from accepted text history.
     *
     * @param array  $history Accepted turns.
     * @param string $message Current request.
     * @return array
     */
    private function buildMessages(array $history, string $message): array
    {
        $messages = array();
        foreach ($history as $turn) {
            $part = new MessagePart((string) ($turn['content'] ?? ''));
            $messages[] = 'assistant' === ($turn['role'] ?? '')
                ? new ModelMessage(array($part))
                : new UserMessage(array($part));
        }
        $messages[] = new UserMessage(
            array(new MessagePart($message . "\n\n" . self::TURN_REMINDER))
        );

        return $messages;
    }

    /**
     * Read function-call parts without assuming every part has that shape.
     *
     * @param object $modelMessage Model message DTO.
     * @return array
     */
    private function getFunctionCalls(object $modelMessage): array
    {
        $calls = array();
        foreach ($modelMessage->getParts() as $part) {
            try {
                $call = $part->getFunctionCall();
                if ($call) {
                    $calls[] = $call;
                }
            } catch (Throwable $error) {
                unset($error);
            }
        }

        return $calls;
    }

    /**
     * Concatenate text parts from a model message.
     *
     * @param object $modelMessage Model message DTO.
     * @return string
     */
    private function getText(object $modelMessage): string
    {
        $reply = '';
        foreach ($modelMessage->getParts() as $part) {
            try {
                $reply .= $part->getText();
            } catch (Throwable $error) {
                unset($error);
            }
        }

        return trim($reply);
    }

    /**
     * Shape the final browser response.
     *
     * @param DraftForm $draftForm Draft adapter.
     * @param string    $reply     Assistant reply.
     * @return array
     */
    private function response(DraftForm $draftForm, string $reply): array
    {
        return array(
            'reply'   => $reply,
            'changed' => $draftForm->hasChanged(),
            'form'    => $draftForm->getDraft(),
        );
    }

    /**
     * Ask for a plain-language close after the tool-call budget is exhausted.
     *
     * @param DraftForm $draftForm Draft adapter.
     * @param array     $messages  Provider transcript.
     * @param int       $formId    Form ID.
     * @param string    $providerId Provider ID.
     * @param string    $modelId    Model ID.
     * @return array
     */
    private function summarizeAtTurnLimit(
        DraftForm $draftForm,
        array $messages,
        int $formId,
        string $providerId,
        string $modelId
    ): array {
        $messages[] = new UserMessage(
            array(
                new MessagePart(
                    'Stop making changes now. In one or two plain sentences, tell the user what you changed '
                    . 'and whether anything they asked for is still outstanding.'
                ),
            )
        );
        $summary = ProviderManager::prompt($messages, $providerId, $modelId)
            ->using_system_instruction($this->getSystemInstruction($formId))
            ->generate_text();
        if (! is_wp_error($summary) && '' !== trim((string) $summary)) {
            return $this->response($draftForm, trim((string) $summary));
        }

        $reply = $draftForm->hasChanged()
            // phpcs:ignore Generic.Files.LineLength.TooLong -- Translator-safe string.
            ? __('I applied your changes — please look them over. If anything is missing, just ask me to continue.', 'ninja-forms')
            : __('I could not finish that request in one go. Try asking for it in smaller pieces.', 'ninja-forms');

        return $this->response($draftForm, $reply);
    }

    /**
     * System instruction for the form-editing assistant.
     *
     * @param int $formId Open form ID.
     * @return string
     */
    private function getSystemInstruction(int $formId): string
    {
        return 'You are the Ninja Forms assistant inside the form builder, editing form ID '
            . $formId . ' only. Use the provided functions to read and change the form; never invent field '
            . 'or action IDs. Read the form first when you need them. When several calls do not depend on '
            . 'each other, make them in one response. Do not re-read to verify successful calls. Put new '
            . 'fields before Submit unless the user requests another position. For relative positions, read '
            . 'the form and use the current one-based order of the field the new field must precede. Follow '
            . 'every function-schema rule. Make only requested changes and never delete without an explicit '
            . 'request. Ask a short clarifying question when ambiguous. Your functions are the only things '
            . 'you can do to this form, and they cover part of what the builder can do. When a request needs '
            . 'something you have no function for, say so plainly in your first reply and name what you '
            . 'cannot do. Never carry out something different and present it as what was asked for. Offer an '
            . 'alternative only when you know enough about the goal to know the alternative serves it, '
            . 'describe it as an alternative, and wait for the user to agree before building it. When you do '
            . 'not know the goal, ask. Before a change that would lose submitted data, stop submissions '
            . 'being recorded, or otherwise cannot be undone from the builder, state the consequence in one '
            . 'sentence and wait for the user to agree. Treat an approval as covering only the change the '
            . 'user approved at the time. Never cite an earlier approval as permission for a later change, '
            . 'in this conversation or an earlier one. Earlier turns in this conversation '
            . 'may describe changes that no longer exist, because unpublished edits are discarded when the '
            . 'builder closes without publishing. The form you read is the only record of what the form '
            . 'contains. Never tell the user something is already there because an earlier turn says you '
            . 'added it: read the form and trust what it returns. Finish with one or two plain '
            . 'sentences and no markdown.';
    }
}
